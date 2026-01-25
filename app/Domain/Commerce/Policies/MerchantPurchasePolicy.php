<?php

namespace App\Domain\Commerce\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Commerce\Models\MerchantPurchase;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Merchant Purchase Policy
 *
 * Determines authorization for merchant-specific purchase actions.
 */
class MerchantPurchasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view any of their purchases
     */
    public function viewAny(User $user): bool
    {
        // Must be a merchant
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can view the purchase
     */
    public function view(User $user, MerchantPurchase $merchantPurchase): bool
    {
        return $user->id === $merchantPurchase->merchant_id;
    }

    /**
     * Determine if merchant can update order status
     */
    public function updateStatus(User $user, MerchantPurchase $merchantPurchase): bool
    {
        // Must be the merchant
        if ($user->id !== $merchantPurchase->merchant_id) {
            return false;
        }

        // Cannot update cancelled orders
        return $merchantPurchase->status !== 'cancelled';
    }

    /**
     * Determine if merchant can mark as shipped
     */
    public function markShipped(User $user, MerchantPurchase $merchantPurchase): bool
    {
        if ($user->id !== $merchantPurchase->merchant_id) {
            return false;
        }

        // Can only ship processing orders
        return $merchantPurchase->status === 'processing';
    }

    /**
     * Determine if merchant can cancel their part
     */
    public function cancel(User $user, MerchantPurchase $merchantPurchase): bool
    {
        if ($user->id !== $merchantPurchase->merchant_id) {
            return false;
        }

        // Can only cancel pending/processing orders
        return in_array($merchantPurchase->status, ['pending', 'processing']);
    }

    /**
     * Determine if merchant can issue refund
     */
    public function refund(User $user, MerchantPurchase $merchantPurchase): bool
    {
        if ($user->id !== $merchantPurchase->merchant_id) {
            return false;
        }

        // Can refund completed orders
        return in_array($merchantPurchase->status, ['completed', 'delivered']);
    }

    /**
     * Determine if merchant can print invoice
     */
    public function printInvoice(User $user, MerchantPurchase $merchantPurchase): bool
    {
        return $user->id === $merchantPurchase->merchant_id;
    }

    /**
     * Determine if merchant can export orders
     */
    public function export(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }
}
