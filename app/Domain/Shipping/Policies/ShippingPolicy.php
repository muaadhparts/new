<?php

namespace App\Domain\Shipping\Policies;

use App\Models\User;
use App\Models\Shipping;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Shipping Policy
 *
 * Determines authorization for shipping method management.
 */
class ShippingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view shipping methods
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can view specific shipping method
     */
    public function view(User $user, Shipping $shipping): bool
    {
        // Platform shipping methods are viewable by all merchants
        if ($shipping->user_id === 0) {
            return $user->role === 'merchant';
        }

        // Custom shipping methods are only viewable by owner
        return $user->id === $shipping->user_id;
    }

    /**
     * Determine if merchant can create custom shipping method
     */
    public function create(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can update shipping method
     */
    public function update(User $user, Shipping $shipping): bool
    {
        // Cannot update platform shipping methods
        if ($shipping->user_id === 0) {
            return false;
        }

        return $user->id === $shipping->user_id;
    }

    /**
     * Determine if merchant can delete shipping method
     */
    public function delete(User $user, Shipping $shipping): bool
    {
        // Cannot delete platform shipping methods
        if ($shipping->user_id === 0) {
            return false;
        }

        return $user->id === $shipping->user_id;
    }

    /**
     * Determine if merchant can enable/disable shipping method
     */
    public function toggle(User $user, Shipping $shipping): bool
    {
        // Platform methods - merchant can choose to use or not
        if ($shipping->user_id === 0) {
            return $user->role === 'merchant';
        }

        // Custom methods - only owner
        return $user->id === $shipping->user_id;
    }
}
