<?php

namespace App\Domain\Shipping\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Shipping Address Policy
 *
 * Determines authorization for shipping address actions.
 */
class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can use address for shipping.
     */
    public function useForShipping(User $user, UserAddress $address): bool
    {
        // Must own the address
        if ($user->id !== $address->user_id) {
            return false;
        }

        // Address must be complete
        return $address->city_id !== null
            && $address->address !== null;
    }

    /**
     * Determine if address can receive COD.
     */
    public function receiveCod(User $user, UserAddress $address): bool
    {
        // Must own the address
        if ($user->id !== $address->user_id) {
            return false;
        }

        // Check if city supports COD
        $city = $address->city;
        return $city && $city->cod_available;
    }
}
