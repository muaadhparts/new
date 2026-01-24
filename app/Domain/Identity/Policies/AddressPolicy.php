<?php

namespace App\Domain\Identity\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Address Policy
 *
 * Determines authorization for user address actions.
 */
class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any addresses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can view the address.
     */
    public function view(User $user, UserAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine if user can create addresses.
     */
    public function create(User $user): bool
    {
        // Check max addresses limit
        $maxAddresses = config('identity.max_addresses', 5);
        return $user->addresses()->count() < $maxAddresses;
    }

    /**
     * Determine if user can update the address.
     */
    public function update(User $user, UserAddress $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine if user can delete the address.
     */
    public function delete(User $user, UserAddress $address): bool
    {
        // Cannot delete if it's the only address
        if ($user->addresses()->count() <= 1) {
            return false;
        }

        return $user->id === $address->user_id;
    }

    /**
     * Determine if user can set as default.
     */
    public function setDefault(User $user, UserAddress $address): bool
    {
        return $user->id === $address->user_id;
    }
}
