<?php

namespace App\Services\MerchantCheckout;

use Illuminate\Support\Facades\Session;

/**
 * Session management for Merchant Checkout
 *
 * Unified session key management - no hardcoded strings
 */
class MerchantSessionManager
{
    /**
     * Session key prefixes
     */
    const PREFIX_ADDRESS = 'checkout.merchant.%d.address';
    const PREFIX_SHIPPING = 'checkout.merchant.%d.shipping';
    const PREFIX_PAYMENT = 'checkout.merchant.%d.payment';
    const PREFIX_DISCOUNT = 'checkout.merchant.%d.discount';
    const PREFIX_LOCATION_DRAFT = 'checkout.merchant.%d.location_draft';

    /**
     * Get address step data
     */
    public function getAddressData(int $merchantId): ?array
    {
        return Session::get($this->addressKey($merchantId));
    }

    /**
     * Save address step data
     */
    public function saveAddressData(int $merchantId, array $data): void
    {
        Session::put($this->addressKey($merchantId), $data);
        Session::save();
    }

    /**
     * Get shipping step data
     */
    public function getShippingData(int $merchantId): ?array
    {
        return Session::get($this->shippingKey($merchantId));
    }

    /**
     * Save shipping step data
     */
    public function saveShippingData(int $merchantId, array $data): void
    {
        Session::put($this->shippingKey($merchantId), $data);
        Session::save();
    }

    /**
     * Get payment step data
     */
    public function getPaymentData(int $merchantId): ?array
    {
        return Session::get($this->paymentKey($merchantId));
    }

    /**
     * Save payment step data
     */
    public function savePaymentData(int $merchantId, array $data): void
    {
        Session::put($this->paymentKey($merchantId), $data);
        Session::save();
    }

    /**
     * Get discount data
     */
    public function getDiscountData(int $merchantId): ?array
    {
        return Session::get($this->discountKey($merchantId));
    }

    /**
     * Save discount data
     */
    public function saveDiscountData(int $merchantId, array $data): void
    {
        Session::put($this->discountKey($merchantId), $data);
        Session::save();
    }

    /**
     * Clear discount data
     */
    public function clearDiscountData(int $merchantId): void
    {
        Session::forget($this->discountKey($merchantId));
        Session::save();
    }

    /**
     * Get location draft data
     */
    public function getLocationDraft(int $merchantId): ?array
    {
        return Session::get($this->locationDraftKey($merchantId));
    }

    /**
     * Save location draft data
     */
    public function saveLocationDraft(int $merchantId, array $data): void
    {
        Session::put($this->locationDraftKey($merchantId), $data);
        Session::save();
    }

    /**
     * Get all checkout data for merchant
     */
    public function getAllCheckoutData(int $merchantId): array
    {
        return [
            'address' => $this->getAddressData($merchantId),
            'shipping' => $this->getShippingData($merchantId),
            'payment' => $this->getPaymentData($merchantId),
            'discount' => $this->getDiscountData($merchantId),
            'location_draft' => $this->getLocationDraft($merchantId),
        ];
    }

    /**
     * Check if step is completed
     */
    public function isStepCompleted(int $merchantId, string $step): bool
    {
        return match ($step) {
            'address' => $this->getAddressData($merchantId) !== null,
            'shipping' => $this->getShippingData($merchantId) !== null,
            'payment' => $this->getPaymentData($merchantId) !== null,
            default => false,
        };
    }

    /**
     * Get current step for merchant
     */
    public function getCurrentStep(int $merchantId): string
    {
        if (!$this->isStepCompleted($merchantId, 'address')) {
            return 'address';
        }
        if (!$this->isStepCompleted($merchantId, 'shipping')) {
            return 'shipping';
        }
        return 'payment';
    }

    /**
     * Clear all checkout data for merchant
     */
    public function clearAllCheckoutData(int $merchantId): void
    {
        Session::forget($this->addressKey($merchantId));
        Session::forget($this->shippingKey($merchantId));
        Session::forget($this->paymentKey($merchantId));
        Session::forget($this->discountKey($merchantId));
        Session::forget($this->locationDraftKey($merchantId));
        Session::save();
    }

    /**
     * Store temp purchase for success page
     */
    public function storeTempPurchase($purchase): void
    {
        Session::put('temp_purchase', $purchase);
        Session::save();
    }

    /**
     * Get temp purchase
     */
    public function getTempPurchase()
    {
        return Session::get('temp_purchase');
    }

    /**
     * Clear temp purchase
     */
    public function clearTempPurchase(): void
    {
        Session::forget('temp_purchase');
        Session::save();
    }

    /**
     * Store temp cart for success page
     */
    public function storeTempCart(array $cart): void
    {
        Session::put('temp_cart', $cart);
        Session::save();
    }

    /**
     * Get temp cart
     */
    public function getTempCart(): ?array
    {
        return Session::get('temp_cart');
    }

    /**
     * Clear temp cart
     */
    public function clearTempCart(): void
    {
        Session::forget('temp_cart');
        Session::save();
    }

    /**
     * Session key builders
     */
    protected function addressKey(int $merchantId): string
    {
        return sprintf(self::PREFIX_ADDRESS, $merchantId);
    }

    protected function shippingKey(int $merchantId): string
    {
        return sprintf(self::PREFIX_SHIPPING, $merchantId);
    }

    protected function paymentKey(int $merchantId): string
    {
        return sprintf(self::PREFIX_PAYMENT, $merchantId);
    }

    protected function discountKey(int $merchantId): string
    {
        return sprintf(self::PREFIX_DISCOUNT, $merchantId);
    }

    protected function locationDraftKey(int $merchantId): string
    {
        return sprintf(self::PREFIX_LOCATION_DRAFT, $merchantId);
    }
}
