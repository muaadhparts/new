<?php

namespace App\Http\Controllers\Front;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\CheckoutPriceService;
use Illuminate\Http\Request;
use Session;

/**
 * ============================================================================
 * COUPON CONTROLLER
 * ============================================================================
 *
 * Handles coupon application and removal for checkout.
 *
 * Key Features:
 * 1. Supports both regular checkout and vendor-specific checkout
 * 2. Validates coupon by: code, status, dates, usage limit, category
 * 3. Calculates discount only on eligible products (matching vendor & category)
 * 4. Stores coupon data in session with proper keys
 *
 * Session Keys:
 * - Regular Checkout: coupon, coupon_code, coupon_id, coupon_percentage, already
 * - Vendor Checkout:  coupon_vendor_{id}, coupon_code_vendor_{id}, etc.
 *
 * ============================================================================
 */
class CouponController extends FrontBaseController
{
    protected CheckoutPriceService $priceService;

    public function __construct()
    {
        parent::__construct();
        $this->priceService = app(CheckoutPriceService::class);
    }
    /**
     * Apply coupon (AJAX endpoint)
     * Route: GET /carts/coupon/check
     *
     * Returns:
     * - 0: Coupon not found or invalid
     * - 2: Coupon already used in this session
     * - 3: Discount exceeds eligible amount
     * - Array: Success [newTotal, code, discountAmount, couponId, percentage, 1, rawTotal]
     */
    public function couponcheck(Request $request)
    {
        $code = trim($request->code ?? '');
        $requestTotal = (float)($request->total ?? 0);

        // Validate code
        if (empty($code)) {
            return response()->json(0);
        }

        // Find coupon
        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            return response()->json(0);
        }

        // Get cart
        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return response()->json(0);
        }

        // Determine checkout type
        $checkoutVendorId = Session::get('checkout_vendor_id');
        $isVendorCheckout = !empty($checkoutVendorId);

        // Validate coupon ownership for vendor checkout
        if ($isVendorCheckout && $coupon->user_id) {
            if ((int)$coupon->user_id !== (int)$checkoutVendorId) {
                return response()->json(0);
            }
        }

        // Validate coupon (status, dates, times)
        $validation = $this->validateCoupon($coupon);
        if (!$validation['valid']) {
            return response()->json(0);
        }

        // Check if already used in this session
        $alreadyKey = $isVendorCheckout ? 'already_vendor_' . $checkoutVendorId : 'already';
        if (Session::get($alreadyKey) === $code) {
            return response()->json(2);
        }

        // Calculate eligible amount
        $eligible = $this->calculateEligibleTotal($cart, $coupon, $checkoutVendorId);
        if ($eligible['total'] <= 0) {
            return response()->json(0);
        }

        // Calculate discount
        $discountData = $this->calculateDiscount($coupon, $eligible['total']);
        if ($discountData['amount'] >= $requestTotal) {
            return response()->json(3);
        }

        // Calculate new total
        $newTotal = $requestTotal - $discountData['amount'];

        // Save to session (SAR values for internal use)
        $this->saveCouponToSession(
            $code,
            $coupon,
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
            3 => $coupon->id,
            4 => $discountData['percentage'],
            5 => 1,
            6 => $convertedNewTotal
        ]);
    }

    /**
     * Remove coupon (AJAX endpoint)
     * Route: POST /carts/coupon/remove
     */
    public function removeCoupon(Request $request)
    {
        $vendorId = $request->vendor_id ?? Session::get('checkout_vendor_id');
        $isVendorCheckout = !empty($vendorId);

        // Get current coupon amount before removing
        $couponAmount = 0;
        if ($isVendorCheckout && $vendorId) {
            $couponAmount = Session::get('coupon_vendor_' . $vendorId, 0);
            // Clear vendor-specific session keys
            Session::forget([
                'already_vendor_' . $vendorId,
                'coupon_vendor_' . $vendorId,
                'coupon_code_vendor_' . $vendorId,
                'coupon_id_vendor_' . $vendorId,
                'coupon_percentage_vendor_' . $vendorId,
            ]);
        } else {
            $couponAmount = Session::get('coupon', 0);
            // Clear regular session keys
            Session::forget([
                'already',
                'coupon',
                'coupon_code',
                'coupon_id',
                'coupon_percentage',
                'coupon_total',
                'coupon_total1',
            ]);
        }

        // Get subtotal before coupon from step2 if available
        $step2 = Session::get('step2');
        $subtotalBeforeCoupon = $step2['subtotal_before_coupon'] ?? 0;

        // If we have step2 data, recalculate and update it
        if ($step2 && is_array($step2)) {
            $step2['coupon_amount'] = 0;
            $step2['coupon_code'] = '';
            $step2['coupon_id'] = null;
            $step2['coupon_percentage'] = '';
            $step2['coupon_applied'] = false;
            $step2['final_total'] = $subtotalBeforeCoupon;
            $step2['total'] = $subtotalBeforeCoupon;
            Session::put('step2', $step2);
        }

        // Convert before returning (single source of truth)
        $convertedSubtotal = $this->priceService->convert($subtotalBeforeCoupon);

        return response()->json([
            'success' => true,
            'message' => __('Coupon removed successfully'),
            'subtotal_before_coupon' => $convertedSubtotal
        ]);
    }

    /**
     * Validate coupon (status, dates, usage limit)
     */
    private function validateCoupon($coupon)
    {
        // Check status
        if ($coupon->status != 1) {
            return ['valid' => false, 'error' => 'inactive'];
        }

        // Check usage limit
        if ($coupon->times !== null && $coupon->times == "0") {
            return ['valid' => false, 'error' => 'exhausted'];
        }

        // Check dates
        $today = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime($coupon->start_date));
        $endDate = date('Y-m-d', strtotime($coupon->end_date));

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
    private function calculateEligibleTotal($cart, $coupon, $checkoutVendorId = null)
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

            // Skip if coupon is vendor-specific and item doesn't belong to coupon vendor
            if ($coupon->user_id && $itemVendorId != (int)$coupon->user_id) {
                continue;
            }

            // Get product for category check
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            // Check category match
            if (!$this->productMatchesCouponCategory($product, $coupon)) {
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
    private function calculateDiscount($coupon, $eligibleAmount)
    {
        $curr = $this->curr;

        if ($coupon->type == 0) {
            // Percentage discount
            $percent = (int)$coupon->price;
            $amount = ($eligibleAmount * $percent) / 100;
            return [
                'amount' => round($amount, 2),
                'percentage' => $percent . '%'
            ];
        } else {
            // Fixed amount discount
            $amount = round($coupon->price * $curr->value, 2);
            return [
                'amount' => min($amount, $eligibleAmount), // Don't exceed eligible
                'percentage' => 0
            ];
        }
    }

    /**
     * Save coupon data to session
     */
    private function saveCouponToSession($code, $coupon, $discountAmount, $percentage, $isVendorCheckout, $vendorId)
    {
        if ($isVendorCheckout && $vendorId) {
            // Vendor checkout - use vendor-specific keys
            Session::put('already_vendor_' . $vendorId, $code);
            Session::put('coupon_vendor_' . $vendorId, $discountAmount);
            Session::put('coupon_code_vendor_' . $vendorId, $code);
            Session::put('coupon_id_vendor_' . $vendorId, $coupon->id);
            Session::put('coupon_percentage_vendor_' . $vendorId, $percentage);
        } else {
            // Regular checkout - use standard keys
            Session::put('already', $code);
            Session::put('coupon', $discountAmount);
            Session::put('coupon_code', $code);
            Session::put('coupon_id', $coupon->id);
            Session::put('coupon_percentage', $percentage);
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
     * Check if product matches coupon category
     */
    private function productMatchesCouponCategory($product, $coupon)
    {
        if ($coupon->coupon_type == 'category') {
            return $product->category_id == $coupon->category;
        } elseif ($coupon->coupon_type == 'sub_category') {
            return $product->subcategory_id == $coupon->sub_category;
        } elseif ($coupon->coupon_type == 'child_category') {
            return $product->childcategory_id == $coupon->child_category;
        }
        return false;
    }

    /**
     * Legacy coupon endpoint (for backward compatibility)
     * Route: GET /carts/coupon
     */
    public function coupon()
    {
        return $this->couponcheck(request());
    }
}
