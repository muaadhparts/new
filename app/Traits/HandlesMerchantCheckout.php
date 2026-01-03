<?php

namespace App\Traits;

use App\Models\Cart;
use Illuminate\Support\Facades\Session;

/**
 * ====================================================================
 * MERCHANT CHECKOUT TRAIT
 * ====================================================================
 *
 * STRICT POLICY (2025-12):
 * - merchant_id MUST come from Route parameter
 * - NO session-based merchant tracking (checkout_merchant_id is DEPRECATED)
 * - Step data stored as: merchant_step1_{merchant_id}, merchant_step2_{merchant_id}
 *
 * Usage in Payment Controllers:
 *   public function store($merchantId) {
 *       $steps = $this->getCheckoutSteps($merchantId);
 *       $cart = $this->filterCartForMerchant($originalCart, $merchantId);
 *   }
 * ====================================================================
 */
trait HandlesMerchantCheckout
{
    /**
     * DEPRECATED: Do not use this method
     * merchant_id must come from route parameter, not session
     *
     * @deprecated Use route parameter $merchantId instead
     */
    protected function getMerchantCheckoutData()
    {
        \Log::warning('HandlesMerchantCheckout::getMerchantCheckoutData() is DEPRECATED. Use route parameter for merchant_id.');

        // Return empty - merchant_id must come from route
        return [
            'merchant_id' => null,
            'is_merchant_checkout' => false
        ];
    }

    /**
     * Get step1 and step2 data for merchant checkout
     *
     * @param int $merchantId The merchant ID from route
     * @return array
     */
    protected function getCheckoutSteps($merchantId)
    {
        if (!$merchantId) {
            throw new \InvalidArgumentException('merchant_id is required for getCheckoutSteps()');
        }

        return [
            'step1' => Session::get('merchant_step1_' . $merchantId),
            'step2' => Session::get('merchant_step2_' . $merchantId)
        ];
    }

    /**
     * Filter cart for merchant-specific checkout
     *
     * @param mixed $cart Original cart
     * @param int $merchantId The merchant ID from route
     * @return object Filtered cart with only this merchant's items
     */
    protected function filterCartForMerchant($cart, $merchantId)
    {
        if (!$merchantId) {
            throw new \InvalidArgumentException('merchant_id is required for filterCartForMerchant()');
        }

        $merchantCart = new \stdClass();
        $merchantCart->items = [];
        $merchantCart->totalQty = 0;
        $merchantCart->totalPrice = 0;

        foreach ($cart->items as $rowKey => $cartItem) {
            $itemMerchantId = data_get($cartItem, 'item.user_id') ?? data_get($cartItem, 'item.merchant_user_id') ?? 0;
            if ($itemMerchantId == $merchantId) {
                $merchantCart->items[$rowKey] = $cartItem;
                $merchantCart->totalQty += (int)($cartItem['qty'] ?? 1);
                $merchantCart->totalPrice += (float)($cartItem['price'] ?? 0);
            }
        }

        return $merchantCart;
    }

    /**
     * Remove merchant items from cart after successful purchase
     *
     * @param int $merchantId The merchant ID from route
     * @param mixed $originalCart Original cart before filtering
     */
    protected function removeMerchantItemsFromCart($merchantId, $originalCart)
    {
        if (!$merchantId) {
            // No merchant context - clear entire cart
            Session::forget('cart');
            Session::forget('already');
            Session::forget('discount_code');
            Session::forget('discount_code_total');
            Session::forget('discount_code_total1');
            Session::forget('discount_code_percentage');
            return;
        }

        $fullCart = new Cart($originalCart);
        $newCart = new Cart(null);
        $newCart->items = [];
        $newCart->totalQty = 0;
        $newCart->totalPrice = 0;

        // Keep items from other merchants
        foreach ($fullCart->items as $rowKey => $cartItem) {
            $itemMerchantId = data_get($cartItem, 'item.user_id') ?? data_get($cartItem, 'item.merchant_user_id') ?? 0;
            if ($itemMerchantId != $merchantId) {
                $newCart->items[$rowKey] = $cartItem;
                $newCart->totalQty += (int)($cartItem['qty'] ?? 1);
                $newCart->totalPrice += (float)($cartItem['price'] ?? 0);
            }
        }

        // Update cart in session
        if (!empty($newCart->items)) {
            Session::put('cart', $newCart);
        } else {
            Session::forget('cart');
        }

        // Clear merchant-specific session data (checkout steps, discount codes)
        // NOTE: checkout_merchant_id is NO LONGER used - merchant context is in route
        Session::forget('merchant_step1_' . $merchantId);
        Session::forget('merchant_step2_' . $merchantId);
        Session::forget('discount_code_merchant_' . $merchantId);
    }

    /**
     * Determine success URL based on remaining cart items
     *
     * @param int $merchantId The merchant ID from route
     * @param mixed $originalCart Original cart before filtering
     * @return string Success URL
     */
    protected function getSuccessUrl($merchantId, $originalCart)
    {
        if (!$merchantId) {
            return route('front.payment.return');
        }

        // Check if there will be remaining items after this purchase
        $hasRemainingItems = false;
        foreach ($originalCart->items as $rowKey => $cartItem) {
            $itemMerchantId = data_get($cartItem, 'item.user_id') ?? data_get($cartItem, 'item.merchant_user_id') ?? 0;
            if ($itemMerchantId != $merchantId) {
                $hasRemainingItems = true;
                break;
            }
        }

        return $hasRemainingItems ? route('front.cart') : route('front.payment.return');
    }
}
