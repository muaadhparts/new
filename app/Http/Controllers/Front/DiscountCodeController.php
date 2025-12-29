<?php

namespace App\Http\Controllers\Front;

use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\Product;
use App\Services\CheckoutPriceService;
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
 * 1. Supports both regular checkout and vendor-specific checkout
 * 2. Validates discount code by: code, status, dates, usage limit, category
 * 3. Calculates discount only on eligible products (matching vendor & category)
 * 4. Stores discount code data in session with proper keys
 *
 * Session Keys:
 * - Regular Checkout: discount_code, discount_code_value, discount_code_id, discount_percentage, already
 * - Vendor Checkout:  discount_code_vendor_{id}, discount_code_value_vendor_{id}, etc.
 *
 * ============================================================================
 */
class DiscountCodeController extends FrontBaseController
{
    protected CheckoutPriceService $priceService;

    public function __construct()
    {
        parent::__construct();
        $this->priceService = app(CheckoutPriceService::class);
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
    public function discountCodeCheck(Request $request, $vendorId = null)
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

        // Get cart
        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return response()->json(0);
        }

        // Determine checkout type - vendorId from route (policy: no session fallback)
        $checkoutVendorId = $vendorId ? (int)$vendorId : null;
        $isVendorCheckout = !empty($checkoutVendorId);

        // Validate discount code ownership for vendor checkout
        if ($isVendorCheckout && $discountCode->user_id) {
            if ((int)$discountCode->user_id !== (int)$checkoutVendorId) {
                return response()->json(0);
            }
        }

        // Validate discount code (status, dates, times)
        $validation = $this->validateDiscountCode($discountCode);
        if (!$validation['valid']) {
            return response()->json(0);
        }

        // Check if already used in this session
        $alreadyKey = $isVendorCheckout ? 'already_vendor_' . $checkoutVendorId : 'already';
        if (Session::get($alreadyKey) === $code) {
            return response()->json(2);
        }

        // Calculate eligible amount
        $eligible = $this->calculateEligibleTotal($cart, $discountCode, $checkoutVendorId);
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
            $isVendorCheckout,
            $checkoutVendorId
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
    public function removeDiscountCode(Request $request, $vendorId = null)
    {
        // vendorId from route (policy: no session fallback)
        $isVendorCheckout = !empty($vendorId);

        // Get current discount amount before removing
        $discountAmount = 0;
        if ($isVendorCheckout && $vendorId) {
            $discountAmount = Session::get('discount_code_vendor_' . $vendorId, 0);
            // Clear vendor-specific session keys
            Session::forget([
                'already_vendor_' . $vendorId,
                'discount_code_vendor_' . $vendorId,
                'discount_code_value_vendor_' . $vendorId,
                'discount_code_id_vendor_' . $vendorId,
                'discount_percentage_vendor_' . $vendorId,
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
     * Calculate eligible total (products matching vendor & category)
     */
    private function calculateEligibleTotal($cart, $discountCode, $checkoutVendorId = null)
    {
        $eligibleTotal = 0;
        $eligibleItems = [];

        foreach ($cart->items as $key => $item) {
            $itemVendorId = $this->getItemVendorId($item);
            $productId = $this->getItemProductId($item);

            // Skip if vendor checkout and item doesn't belong to checkout vendor
            if ($checkoutVendorId && $itemVendorId != (int)$checkoutVendorId) {
                continue;
            }

            // Skip if discount code is vendor-specific and item doesn't belong to discount code vendor
            if ($discountCode->user_id && $itemVendorId != (int)$discountCode->user_id) {
                continue;
            }

            // Get product for category check
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            // Check category match
            if (!$this->productMatchesDiscountCodeCategory($product, $discountCode)) {
                continue;
            }

            // Add to eligible
            $eligibleTotal += (float)($item['price'] ?? 0);
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
    private function saveDiscountCodeToSession($code, $discountCode, $discountAmount, $percentage, $isVendorCheckout, $vendorId)
    {
        if ($isVendorCheckout && $vendorId) {
            // Vendor checkout - use vendor-specific keys
            Session::put('already_vendor_' . $vendorId, $code);
            Session::put('discount_code_vendor_' . $vendorId, $discountAmount);
            Session::put('discount_code_value_vendor_' . $vendorId, $code);
            Session::put('discount_code_id_vendor_' . $vendorId, $discountCode->id);
            Session::put('discount_percentage_vendor_' . $vendorId, $percentage);
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
     * Extract vendor_id from cart item
     */
    private function getItemVendorId($item)
    {
        if (isset($item['user_id']) && $item['user_id']) {
            return (int)$item['user_id'];
        }

        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                return (int)($itemData->vendor_user_id ?? $itemData->user_id ?? 0);
            }
            if (is_array($itemData)) {
                return (int)($itemData['vendor_user_id'] ?? $itemData['user_id'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Extract product_id from cart item
     */
    private function getItemProductId($item)
    {
        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                return (int)($itemData->id ?? 0);
            }
            if (is_array($itemData)) {
                return (int)($itemData['id'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Check if product matches discount code category
     */
    private function productMatchesDiscountCodeCategory($product, $discountCode)
    {
        if ($discountCode->apply_to == 'category') {
            return $product->category_id == $discountCode->category;
        } elseif ($discountCode->apply_to == 'sub_category') {
            return $product->subcategory_id == $discountCode->sub_category;
        } elseif ($discountCode->apply_to == 'child_category') {
            return $product->childcategory_id == $discountCode->child_category;
        }
        return false;
    }
}
