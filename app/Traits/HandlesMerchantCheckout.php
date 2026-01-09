<?php

namespace App\Traits;

use App\Models\Cart;
use App\Models\DeliveryCourier;
use App\Models\CourierServiceArea;
use Illuminate\Support\Facades\Session;

/**
 * ====================================================================
 * MERCHANT CHECKOUT TRAIT
 * ====================================================================
 *
 * STRICT POLICY (2025-12):
 * - merchant_id MUST come from Route parameter
 * - NO session-based merchant tracking (checkout_merchant_id removed)
 * - Step data stored as: merchant_step1_{merchant_id}, merchant_step2_{merchant_id}
 *
 * Usage in Payment Controllers:
 *   public function store(Request $request, $merchantId) {
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

    /**
     * Create DeliveryCourier record for local courier delivery
     *
     * Called after purchase creation when delivery_type is 'local_courier'
     *
     * UPDATED 2026-01-09:
     * - Properly extracts service_area_id from step2
     * - Sets delivery_fee from courier_fee
     * - Calculates COD amount correctly
     *
     * @param \App\Models\Purchase $purchase The created purchase
     * @param int $merchantId The merchant ID from route
     * @param array $step2 Step 2 data containing delivery info
     * @param string $paymentMethod 'online' or 'cod'
     * @return \App\Models\DeliveryCourier|null Created DeliveryCourier or null if not applicable
     */
    protected function createDeliveryCourier($purchase, $merchantId, $step2, $paymentMethod = 'online')
    {
        // Convert object to array if needed
        if (is_object($step2)) {
            $step2 = (array) $step2;
        }

        // Get delivery type from step2
        $deliveryType = $step2['delivery_type'] ?? null;

        // Only create record for local courier delivery
        if ($deliveryType !== 'local_courier') {
            return null;
        }

        $courierId = (int)($step2['courier_id'] ?? 0);
        $merchantLocationId = (int)($step2['merchant_location_id'] ?? 0) ?: null;
        $serviceAreaId = (int)($step2['selected_service_area_id'] ?? $step2['service_area_id'] ?? 0) ?: null;
        $courierFee = (float)($step2['courier_fee'] ?? 0);
        $customerCityId = (int)($step2['customer_city_id'] ?? 0);

        // For courier delivery, courier_id is required
        if (!$courierId) {
            \Log::warning('createDeliveryCourier: No courier_id provided', [
                'purchase_id' => $purchase->id,
                'step2' => $step2,
            ]);
            return null;
        }

        // If service_area_id not provided, try to find it
        if (!$serviceAreaId && $customerCityId) {
            $serviceArea = CourierServiceArea::where('courier_id', $courierId)
                ->where('city_id', $customerCityId)
                ->first();
            if ($serviceArea) {
                $serviceAreaId = $serviceArea->id;
                // Also get courier fee from service area if not set
                if (!$courierFee) {
                    $courierFee = (float)$serviceArea->price;
                }
            }
        }

        // Determine COD amount (for COD orders, this is the total amount to collect)
        $codAmount = ($paymentMethod === 'cod') ? $purchase->pay_amount : 0;

        // Create the DeliveryCourier record
        $deliveryCourier = DeliveryCourier::create([
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
            'courier_id' => $courierId,
            'merchant_location_id' => $merchantLocationId,
            'service_area_id' => $serviceAreaId,
            'status' => 'pending',
            'delivery_fee' => $courierFee,
            'cod_amount' => $codAmount,
            'purchase_amount' => $purchase->pay_amount,
            'payment_method' => $paymentMethod,
            'fee_status' => 'pending',
            'settlement_status' => 'pending',
        ]);

        \Log::info('DeliveryCourier created', [
            'delivery_courier_id' => $deliveryCourier->id,
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
            'delivery_type' => $deliveryType,
            'courier_id' => $courierId,
            'service_area_id' => $serviceAreaId,
            'courier_fee' => $courierFee,
            'cod_amount' => $codAmount,
            'payment_method' => $paymentMethod,
        ]);

        return $deliveryCourier;
    }
}
