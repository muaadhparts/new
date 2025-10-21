<?php

namespace App\Traits;

use App\Models\Cart;
use Illuminate\Support\Facades\Session;

trait HandlesVendorCheckout
{
    /**
     * Get vendor checkout session data
     */
    protected function getVendorCheckoutData()
    {
        $vendorId = Session::get('checkout_vendor_id');
        $isVendorCheckout = !empty($vendorId);

        return [
            'vendor_id' => $vendorId,
            'is_vendor_checkout' => $isVendorCheckout
        ];
    }

    /**
     * Get step1 and step2 data based on checkout type
     */
    protected function getCheckoutSteps($vendorId = null, $isVendorCheckout = false)
    {
        if ($isVendorCheckout && $vendorId) {
            return [
                'step1' => Session::get('vendor_step1_' . $vendorId),
                'step2' => Session::get('vendor_step2_' . $vendorId)
            ];
        }

        return [
            'step1' => Session::get('step1'),
            'step2' => Session::get('step2')
        ];
    }

    /**
     * Filter cart for vendor-specific checkout
     */
    protected function filterCartForVendor($cart, $vendorId)
    {
        if (!$vendorId) {
            return $cart;
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
     * Remove vendor products from cart after order
     */
    protected function removeVendorProductsFromCart($vendorId, $originalCart)
    {
        if (!$vendorId) {
            // Normal checkout - clear all cart data
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

        // Clear vendor-specific session data
        Session::forget('vendor_step1_' . $vendorId);
        Session::forget('vendor_step2_' . $vendorId);
        Session::forget('checkout_vendor_id');
        Session::forget('coupon_vendor_' . $vendorId);
    }

    /**
     * Determine success URL based on remaining cart items
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
