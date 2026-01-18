<?php

namespace App\Services\MerchantCheckout;

use Illuminate\Support\Facades\Session;

/**
 * Session management for Branch Checkout
 *
 * Unified session key management - no hardcoded strings
 * NOTE: Session keys use branch_id (not merchant_id) for branch-scoped checkout
 */
class MerchantSessionManager
{
    /**
     * Session key prefixes (Branch-Scoped)
     */
    const PREFIX_ADDRESS = 'checkout.branch.%d.address';
    const PREFIX_SHIPPING = 'checkout.branch.%d.shipping';
    const PREFIX_PAYMENT = 'checkout.branch.%d.payment';
    const PREFIX_DISCOUNT = 'checkout.branch.%d.discount';
    const PREFIX_LOCATION_DRAFT = 'checkout.branch.%d.location_draft';

    // ══════════════════════════════════════════════════════════════
    // Branch-Scoped Methods (الأساسية)
    // ══════════════════════════════════════════════════════════════

    /**
     * Get address step data (branch-scoped)
     */
    public function getAddressData(int $branchId): ?array
    {
        return Session::get($this->addressKey($branchId));
    }

    /**
     * Save address step data (branch-scoped)
     */
    public function saveAddressData(int $branchId, array $data): void
    {
        Session::put($this->addressKey($branchId), $data);
        Session::save();
    }

    /**
     * Get shipping step data (branch-scoped)
     */
    public function getShippingData(int $branchId): ?array
    {
        return Session::get($this->shippingKey($branchId));
    }

    /**
     * Save shipping step data (branch-scoped)
     */
    public function saveShippingData(int $branchId, array $data): void
    {
        Session::put($this->shippingKey($branchId), $data);
        Session::save();
    }

    /**
     * Get payment step data (branch-scoped)
     */
    public function getPaymentData(int $branchId): ?array
    {
        return Session::get($this->paymentKey($branchId));
    }

    /**
     * Save payment step data (branch-scoped)
     */
    public function savePaymentData(int $branchId, array $data): void
    {
        Session::put($this->paymentKey($branchId), $data);
        Session::save();
    }

    /**
     * Get discount data (branch-scoped)
     */
    public function getDiscountData(int $branchId): ?array
    {
        return Session::get($this->discountKey($branchId));
    }

    /**
     * Save discount data (branch-scoped)
     */
    public function saveDiscountData(int $branchId, array $data): void
    {
        Session::put($this->discountKey($branchId), $data);
        Session::save();
    }

    /**
     * Clear discount data (branch-scoped)
     */
    public function clearDiscountData(int $branchId): void
    {
        Session::forget($this->discountKey($branchId));
        Session::save();
    }

    /**
     * Get location draft data (branch-scoped)
     */
    public function getLocationDraft(int $branchId): ?array
    {
        return Session::get($this->locationDraftKey($branchId));
    }

    /**
     * Save location draft data (branch-scoped)
     */
    public function saveLocationDraft(int $branchId, array $data): void
    {
        Session::put($this->locationDraftKey($branchId), $data);
        Session::save();
    }

    /**
     * Get all checkout data for branch
     */
    public function getAllCheckoutData(int $branchId): array
    {
        return [
            'address' => $this->getAddressData($branchId),
            'shipping' => $this->getShippingData($branchId),
            'payment' => $this->getPaymentData($branchId),
            'discount' => $this->getDiscountData($branchId),
            'location_draft' => $this->getLocationDraft($branchId),
        ];
    }

    /**
     * Check if step is completed (branch-scoped)
     */
    public function isStepCompleted(int $branchId, string $step): bool
    {
        return match ($step) {
            'address' => $this->getAddressData($branchId) !== null,
            'shipping' => $this->getShippingData($branchId) !== null,
            'payment' => $this->getPaymentData($branchId) !== null,
            default => false,
        };
    }

    /**
     * Get current step for branch
     */
    public function getCurrentStep(int $branchId): string
    {
        if (!$this->isStepCompleted($branchId, 'address')) {
            return 'address';
        }
        if (!$this->isStepCompleted($branchId, 'shipping')) {
            return 'shipping';
        }
        return 'payment';
    }

    /**
     * Clear all checkout data for branch
     */
    public function clearAllCheckoutData(int $branchId): void
    {
        Session::forget($this->addressKey($branchId));
        Session::forget($this->shippingKey($branchId));
        Session::forget($this->paymentKey($branchId));
        Session::forget($this->discountKey($branchId));
        Session::forget($this->locationDraftKey($branchId));
        Session::save();
    }

    // ══════════════════════════════════════════════════════════════
    // Temp Storage (Global - not branch-scoped)
    // ══════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════
    // Session Key Builders
    // ══════════════════════════════════════════════════════════════

    protected function addressKey(int $branchId): string
    {
        return sprintf(self::PREFIX_ADDRESS, $branchId);
    }

    protected function shippingKey(int $branchId): string
    {
        return sprintf(self::PREFIX_SHIPPING, $branchId);
    }

    protected function paymentKey(int $branchId): string
    {
        return sprintf(self::PREFIX_PAYMENT, $branchId);
    }

    protected function discountKey(int $branchId): string
    {
        return sprintf(self::PREFIX_DISCOUNT, $branchId);
    }

    protected function locationDraftKey(int $branchId): string
    {
        return sprintf(self::PREFIX_LOCATION_DRAFT, $branchId);
    }
}
