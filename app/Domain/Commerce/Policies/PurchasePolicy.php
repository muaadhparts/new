<?php

namespace App\Domain\Commerce\Policies;

use App\Models\User;
use App\Models\Purchase;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Purchase Policy
 *
 * Determines authorization for purchase/order actions.
 */
class PurchasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any purchases
     */
    public function viewAny(User $user): bool
    {
        // Users can view their own purchases list
        return true;
    }

    /**
     * Determine if user can view the purchase
     */
    public function view(User $user, Purchase $purchase): bool
    {
        // User can view their own purchase
        return $user->id === $purchase->user_id;
    }

    /**
     * Determine if user can cancel the purchase
     */
    public function cancel(User $user, Purchase $purchase): bool
    {
        // Must be the owner
        if ($user->id !== $purchase->user_id) {
            return false;
        }

        // Can only cancel pending orders
        return in_array($purchase->status, ['pending', 'processing']);
    }

    /**
     * Determine if user can request refund
     */
    public function requestRefund(User $user, Purchase $purchase): bool
    {
        // Must be the owner
        if ($user->id !== $purchase->user_id) {
            return false;
        }

        // Can only refund completed orders within refund window
        if ($purchase->status !== 'completed') {
            return false;
        }

        // Check refund window (e.g., 14 days)
        $refundWindow = config('commerce.refund_window_days', 14);
        return $purchase->completed_at?->diffInDays(now()) <= $refundWindow;
    }

    /**
     * Determine if user can view tracking
     */
    public function viewTracking(User $user, Purchase $purchase): bool
    {
        return $user->id === $purchase->user_id;
    }

    /**
     * Determine if user can download invoice
     */
    public function downloadInvoice(User $user, Purchase $purchase): bool
    {
        // Must be owner and order must be confirmed
        return $user->id === $purchase->user_id
            && !in_array($purchase->status, ['pending', 'cancelled']);
    }

    /**
     * Determine if user can leave review for purchase items
     */
    public function review(User $user, Purchase $purchase): bool
    {
        // Must be owner and order must be completed
        return $user->id === $purchase->user_id
            && $purchase->status === 'completed';
    }
}
