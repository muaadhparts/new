<?php

namespace App\Domain\Commerce\Contracts;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;

/**
 * CheckoutInterface - Contract for checkout operations
 *
 * All checkout flows MUST go through this interface.
 */
interface CheckoutInterface
{
    /**
     * Get checkout data for display
     */
    public function getCheckoutData(int $branchId, User $user): array;

    /**
     * Get available shipping methods
     */
    public function getShippingMethods(int $branchId, int $destinationCityId): array;

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(int $branchId): array;

    /**
     * Validate checkout data
     */
    public function validateCheckout(array $data): array;

    /**
     * Create purchase from checkout
     */
    public function createPurchase(array $checkoutData, User $user): Purchase;
}
