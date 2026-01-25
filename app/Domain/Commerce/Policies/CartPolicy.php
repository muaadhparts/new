<?php

namespace App\Domain\Commerce\Policies;

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Cart Policy
 *
 * Determines authorization for cart actions.
 */
class CartPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view cart.
     */
    public function view(?User $user): bool
    {
        // Anyone can view cart (session-based for guests)
        return true;
    }

    /**
     * Determine if user can add items to cart.
     */
    public function addItem(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can update cart items.
     */
    public function updateItem(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can remove items from cart.
     */
    public function removeItem(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can clear cart.
     */
    public function clear(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can proceed to checkout.
     */
    public function checkout(User $user): bool
    {
        // Must be authenticated to checkout
        return $user !== null;
    }

    /**
     * Determine if user can apply coupon.
     */
    public function applyCoupon(?User $user): bool
    {
        return true;
    }
}
