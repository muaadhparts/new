<?php

namespace App\Http\Controllers\Front;

use App\Models\DiscountCode;
use App\Models\CatalogItem;
use App\Services\CheckoutPriceService;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Session;

/**
 * ============================================================================
 * DISCOUNT CODE CONTROLLER
 * ============================================================================
 *
 * Handles discount code application and removal for checkout.
 *
 * Key Features:
 * 1. Supports both regular checkout and merchant-specific checkout
 * 2. Validates discount code by: code, status, dates, usage limit, category
 * 3. Calculates discount only on eligible items (matching merchant & category)
 * 4. Stores discount code data in session with proper keys
 *
 * Session Keys:
 * - Regular Checkout: discount_code, discount_code_value, discount_code_id, discount_percentage, already
 * - Merchant Checkout:  discount_code_merchant_{id}, discount_code_value_merchant_{id}, etc.
 *
 * ============================================================================
 */
class DiscountCodeController extends FrontBaseController
{
    protected CheckoutPriceService $priceService;
    protected MerchantCartManager $cartManager;

    public function __construct()
    {
        parent::__construct();
        $this->priceService = app(CheckoutPriceService::class);
        $this->cartManager = app(MerchantCartManager::class);
    }

    /**
     * Apply discount code (AJAX endpoint)
     * Route: GET /carts/discount-code/check
     *
     * Returns:
     * - 0: Discount code not found or invalid
     * - 2: Discount code already used in this session
     * - 3: Discount exceeds eligible amount
     * - Array: Success [newTotal, code, discountAmount, discountCodeId, percentage, 1, rawTotal]
     */
    public function discountCodeCheck(Request $request, $merchantId = null)
    {
        $code = trim($request->code ?? '');
        $requestTotal = (float)($request->total ?? 0);

        // Validate code
        if (empty($code)) {
            return response()->json(0);
        }

        // Find discount code
        $discountCode = DiscountCode::where('code', $code)->first();
        if (!$discountCode) {
            return response()->json(0);
        }

        // Get cart items using new MerchantCartManager
        $cartItems = $this->cartManager->getItems();
        if (empty($cartItems)) {
            return response()->json(0);
        }

        // Determine checkout type - merchantId from route (policy: no session fallback)
        $checkoutMerchantId = $merchantId ? (int)$merchantId : null;
        $isMerchantCheckout = !empty($checkoutMerchantId);

        // Validate discount code ownership for merchant checkout
        if ($isMerchantCheckout && $discountCode->user_id) {
            if ((int)$discountCode->user_id !== (int)$checkoutMerchantId) {
                return response()->json(0);
            }
        }

        // Validate discount code (status, dates, times)
        $validation = $this->validateDiscountCode($discountCode);
        if (!$validation['valid']) {
            return response()->json(0);
        }

        // Check if already used in this session
        $alreadyKey = $isMerchantCheckout ? 'already_merchant_' . $checkoutMerchantId : 'already';
        if (Session::get($alreadyKey) === $code) {
            return response()->json(2);
        }

        // Calculate eligible amount
        $eligible = $this->calculateEligibleTotal($cartItems, $discountCode, $checkoutMerchantId);
        if ($eligible['total'] <= 0) {
            return response()->json(0);
        }

        // Calculate discount
        $discountData = $this->calculateDiscount($discountCode, $eligible['total']);
        if ($discountData['amount'] >= $requestTotal) {
            return response()->json(3);
        }

        // Calculate new total
        $newTotal = $requestTotal - $discountData['amount'];

        // Save to session (SAR values for internal use)
        $this->saveDiscountCodeToSession(
            $code,
            $discountCode,
            $discountData['amount'],
            $discountData['percentage'],
            $isMerchantCheckout,
            $checkoutMerchantId
        );

        // Convert prices before returning (single source of truth)
        $convertedDiscount = $this->priceService->convert($discountData['amount']);
        $convertedNewTotal = $this->priceService->convert($newTotal);

        // Return response with converted values
        return response()->json([
            0 => $this->priceService->formatPrice($convertedNewTotal),
            1 => $code,
            2 => $convertedDiscount,
            3 => $discountCode->id,
            4 => $discountData['percentage'],
            5 => 1,
            6 => $convertedNewTotal
        ]);
    }

    /**
     * Remove discount code (AJAX endpoint)
     * Route: POST /carts/discount-code/remove
     */
    public function removeDiscountCode(Request $request, $merchantId = null)
    {
        // merchantId from route (policy: no session fallback)
        $isMerchantCheckout = !empty($merchantId);

        // Get current discount amount before removing
        $discountAmount = 0;
        if ($isMerchantCheckout && $merchantId) {
            $discountAmount = Session::get('discount_code_merchant_' . $merchantId, 0);
            // Clear merchant-specific session keys
            Session::forget([
                'already_merchant_' . $merchantId,
                'discount_code_merchant_' . $merchantId,
                'discount_code_value_merchant_' . $merchantId,
                'discount_code_id_merchant_' . $merchantId,
                'discount_percentage_merchant_' . $merchantId,
            ]);
        } else {
            $discountAmount = Session::get('discount_code', 0);
            // Clear regular session keys
            Session::forget([
                'already',
                'discount_code',
                'discount_code_value',
                'discount_code_id',
                'discount_percentage',
                'discount_code_total',
                'discount_code_total1',
            ]);
        }

        // Get subtotal before discount from step2 if available
        $step2 = Session::get('step2');
        $subtotalBeforeDiscount = $step2['subtotal_before_discount'] ?? 0;

        // If we have step2 data, recalculate and update it
        if ($step2 && is_array($step2)) {
            $step2['discount_amount'] = 0;
            $step2['discount_code'] = '';
            $step2['discount_code_id'] = null;
            $step2['discount_percentage'] = '';
            $step2['discount_applied'] = false;
            $step2['final_total'] = $subtotalBeforeDiscount;
            $step2['total'] = $subtotalBeforeDiscount;
            Session::put('step2', $step2);
        }

        // Convert before returning (single source of truth)
        $convertedSubtotal = $this->priceService->convert($subtotalBeforeDiscount);

        return response()->json([
            'success' => true,
            'message' => __('Discount code removed successfully'),
            'subtotal_before_discount' => $convertedSubtotal
        ]);
    }

    /**
     * Validate discount code (status, dates, usage limit)
     */
    private function validateDiscountCode($discountCode)
    {
        // Check status
        if ($discountCode->status != 1) {
            return ['valid' => false, 'error' => 'inactive'];
        }

        // Check usage limit
        if ($discountCode->times !== null && $discountCode->times == "0") {
            return ['valid' => false, 'error' => 'exhausted'];
        }

        // Check dates
        $today = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime($discountCode->start_date));
        $endDate = date('Y-m-d', strtotime($discountCode->end_date));

        if ($startDate > $today) {
            return ['valid' => false, 'error' => 'not_started'];
        }

        if ($endDate < $today) {
            return ['valid' => false, 'error' => 'expired'];
        }

        return ['valid' => true];
    }

    /**
     * Calculate eligible total (items matching merchant & category).
     * Uses new cart format from MerchantCartManager
     */
    private function calculateEligibleTotal(array $cartItems, $discountCode, $checkoutMerchantId = null)
    {
        $eligibleTotal = 0;
        $eligibleItems = [];

        foreach ($cartItems as $key => $item) {
            // New cart format: merchant_id is directly available
            $itemMerchantId = (int)($item['merchant_id'] ?? 0);
            $catalogItemId = (int)($item['catalog_item_id'] ?? 0);

            // Skip if merchant checkout and item doesn't belong to checkout merchant
            if ($checkoutMerchantId && $itemMerchantId != (int)$checkoutMerchantId) {
                continue;
            }

            // Skip if discount code is merchant-specific and item doesn't belong to discount code merchant
            if ($discountCode->user_id && $itemMerchantId != (int)$discountCode->user_id) {
                continue;
            }

            // Get catalog item for category check
            $catalogItem = CatalogItem::find($catalogItemId);
            if (!$catalogItem) {
                continue;
            }

            // Check category match
            if (!$this->catalogItemMatchesDiscountCodeCategory($catalogItem, $discountCode)) {
                continue;
            }

            // Add to eligible - new format uses total_price for item total
            $eligibleTotal += (float)($item['total_price'] ?? 0);
            $eligibleItems[] = $key;
        }

        return [
            'total' => $eligibleTotal,
            'items' => $eligibleItems
        ];
    }

    /**
     * Calculate discount amount and percentage string
     */
    private function calculateDiscount($discountCode, $eligibleAmount)
    {
        $curr = $this->curr;

        if ($discountCode->type == 0) {
            // Percentage discount
            $percent = (int)$discountCode->price;
            $amount = ($eligibleAmount * $percent) / 100;
            return [
                'amount' => round($amount, 2),
                'percentage' => $percent . '%'
            ];
        } else {
            // Fixed amount discount
            $amount = round($discountCode->price * $curr->value, 2);
            return [
                'amount' => min($amount, $eligibleAmount), // Don't exceed eligible
                'percentage' => 0
            ];
        }
    }

    /**
     * Save discount code data to session
     */
    private function saveDiscountCodeToSession($code, $discountCode, $discountAmount, $percentage, $isMerchantCheckout, $merchantId)
    {
        if ($isMerchantCheckout && $merchantId) {
            // Merchant checkout - use merchant-specific keys
            Session::put('already_merchant_' . $merchantId, $code);
            Session::put('discount_code_merchant_' . $merchantId, $discountAmount);
            Session::put('discount_code_value_merchant_' . $merchantId, $code);
            Session::put('discount_code_id_merchant_' . $merchantId, $discountCode->id);
            Session::put('discount_percentage_merchant_' . $merchantId, $percentage);
        } else {
            // Regular checkout - use standard keys
            Session::put('already', $code);
            Session::put('discount_code', $discountAmount);
            Session::put('discount_code_value', $code);
            Session::put('discount_code_id', $discountCode->id);
            Session::put('discount_percentage', $percentage);
        }
    }

    /**
     * Check if catalog item matches discount code category
     * تم إلغاء منطق الفئات - جميع المنتجات مؤهلة
     */
    private function catalogItemMatchesDiscountCodeCategory($catalogItem, $discountCode)
    {
        // تم إلغاء الخصم حسب الفئات - جميع المنتجات مؤهلة
        return true;
    }
}
