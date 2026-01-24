<?php

namespace App\Domain\Identity\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * User Policy
 *
 * Determines authorization for user management actions.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view their profile
     */
    public function viewProfile(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can update their profile
     */
    public function updateProfile(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can change password
     */
    public function changePassword(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can update notification settings
     */
    public function updateNotifications(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can view addresses
     */
    public function viewAddresses(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can manage addresses
     */
    public function manageAddresses(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can view order history
     */
    public function viewOrders(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can view favorites
     */
    public function viewFavorites(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    /**
     * Determine if user can delete their account
     */
    public function deleteAccount(User $authUser, User $user): bool
    {
        // Users can request to delete their own account
        if ($authUser->id !== $user->id) {
            return false;
        }

        // Merchants cannot delete account if they have pending orders
        if ($user->role === 'merchant') {
            $hasPendingOrders = $user->merchantPurchases()
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            return !$hasPendingOrders;
        }

        // Regular users - check for pending orders
        $hasPendingOrders = $user->purchases()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        return !$hasPendingOrders;
    }

    /**
     * Determine if user can become a merchant
     */
    public function becomeMerchant(User $authUser, User $user): bool
    {
        // Must be same user
        if ($authUser->id !== $user->id) {
            return false;
        }

        // Must not already be a merchant
        return $user->role !== 'merchant';
    }
}
