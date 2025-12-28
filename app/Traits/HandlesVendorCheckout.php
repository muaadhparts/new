<?php

namespace App\Traits;

use App\Models\Cart;
use Illuminate\Support\Facades\Session;

/**
 * ====================================================================
 * VENDOR CHECKOUT TRAIT
 * ====================================================================
 *
 * STRICT POLICY (2025-12):
 * - vendor_id MUST come from Route parameter
 * - NO session-based vendor tracking (checkout_vendor_id is DEPRECATED)
 * - Step data stored as: vendor_step1_{vendor_id}, vendor_step2_{vendor_id}
 *
 * Usage in Payment Controllers:
 *   public function store($vendorId) {
 *       $steps = $this->getCheckoutSteps($vendorId);
 *       $cart = $this->filterCartForVendor($originalCart, $vendorId);
 *   }
 * ====================================================================
 */
trait HandlesVendorCheckout
{
    /**
     * DEPRECATED: Do not use this method
     * vendor_id must come from route parameter, not session
     *
     * @deprecated Use route parameter $vendorId instead
     */
    protected function getVendorCheckoutData()
    {
        \Log::warning('HandlesVendorCheckout::getVendorCheckoutData() is DEPRECATED. Use route parameter for vendor_id.');

        // Return empty - vendor_id must come from route
        return [
            'vendor_id' => null,
            'is_vendor_checkout' => false
        ];
    }

    /**
     * Get step1 and step2 data for vendor checkout
     *
     * @param int $vendorId The vendor ID from route
     * @return array
     */
    protected function getCheckoutSteps($vendorId)
    {
        if (!$vendorId) {
            throw new \InvalidArgumentException('vendor_id is required for getCheckoutSteps()');
        }

        return [
            'step1' => Session::get('vendor_step1_' . $vendorId),
            'step2' => Session::get('vendor_step2_' . $vendorId)
        ];
    }

    /**
     * Filter cart for vendor-specific checkout
     *
     * @param mixed $cart Original cart
     * @param int $vendorId The vendor ID from route
     * @return object Filtered cart with only this vendor's products
     */
    protected function filterCartForVendor($cart, $vendorId)
    {
        if (!$vendorId) {
            throw new \InvalidArgumentException('vendor_id is required for filterCartForVendor()');
        }

        $vendorCart = new \stdClass();
        $vendorCart->items = [];
        $vendorCart->totalQty = 0;
        $vendorCart->totalPrice = 0;

        foreach ($cart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId == $vendorId) {
                $vendorCart->items[$rowKey] = $product;
                $vendorCart->totalQty += (int)($product['qty'] ?? 1);
                $vendorCart->totalPrice += (float)($product['price'] ?? 0);
            }
        }

        return $vendorCart;
    }

    /**
     * Remove vendor products from cart after successful order
     *
     * @param int $vendorId The vendor ID from route
     * @param mixed $originalCart Original cart before filtering
     */
    protected function removeVendorProductsFromCart($vendorId, $originalCart)
    {
        if (!$vendorId) {
            // No vendor context - clear entire cart
            Session::forget('cart');
            Session::forget('already');
            Session::forget('coupon');
            Session::forget('coupon_total');
            Session::forget('coupon_total1');
            Session::forget('coupon_percentage');
            return;
        }

        $fullCart = new Cart($originalCart);
        $newCart = new Cart(null);
        $newCart->items = [];
        $newCart->totalQty = 0;
        $newCart->totalPrice = 0;

        // Keep products from other vendors
        foreach ($fullCart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId != $vendorId) {
                $newCart->items[$rowKey] = $product;
                $newCart->totalQty += (int)($product['qty'] ?? 1);
                $newCart->totalPrice += (float)($product['price'] ?? 0);
            }
        }

        // Update cart in session
        if (!empty($newCart->items)) {
            Session::put('cart', $newCart);
        } else {
            Session::forget('cart');
        }

        // Clear vendor-specific session data (checkout steps, coupons)
        // NOTE: checkout_vendor_id is NO LONGER used - vendor context is in route
        Session::forget('vendor_step1_' . $vendorId);
        Session::forget('vendor_step2_' . $vendorId);
        Session::forget('coupon_vendor_' . $vendorId);
    }

    /**
     * Determine success URL based on remaining cart items
     *
     * @param int $vendorId The vendor ID from route
     * @param mixed $originalCart Original cart before filtering
     * @return string Success URL
     */
    protected function getSuccessUrl($vendorId, $originalCart)
    {
        if (!$vendorId) {
            return route('front.payment.return');
        }

        // Check if there will be remaining items after this order
        $hasRemainingItems = false;
        foreach ($originalCart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId != $vendorId) {
                $hasRemainingItems = true;
                break;
            }
        }

        return $hasRemainingItems ? route('front.cart') : route('front.payment.return');
    }
}
