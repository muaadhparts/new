<?php

namespace App\Domain\Identity\Policies;

use App\Models\User;
use App\Models\Courier;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Courier Policy
 *
 * Determines authorization for courier-related actions.
 * Note: This is for merchants managing their couriers.
 */
class CourierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view their couriers
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can view specific courier
     */
    public function view(User $user, Courier $courier): bool
    {
        return $user->id === $courier->merchant_id;
    }

    /**
     * Determine if merchant can create courier
     */
    public function create(User $user): bool
    {
        if ($user->role !== 'merchant' || $user->status !== 1) {
            return false;
        }

        // Check courier limit
        $maxCouriers = config('merchant.max_couriers', 20);
        $currentCount = Courier::where('merchant_id', $user->id)->count();

        return $currentCount < $maxCouriers;
    }

    /**
     * Determine if merchant can update courier
     */
    public function update(User $user, Courier $courier): bool
    {
        return $user->id === $courier->merchant_id;
    }

    /**
     * Determine if merchant can delete courier
     */
    public function delete(User $user, Courier $courier): bool
    {
        if ($user->id !== $courier->merchant_id) {
            return false;
        }

        // Cannot delete courier with active deliveries
        // This should be checked at service level
        return true;
    }

    /**
     * Determine if merchant can toggle courier status
     */
    public function toggleStatus(User $user, Courier $courier): bool
    {
        return $user->id === $courier->merchant_id;
    }

    /**
     * Determine if merchant can assign delivery to courier
     */
    public function assignDelivery(User $user, Courier $courier): bool
    {
        // Must own the courier
        if ($user->id !== $courier->merchant_id) {
            return false;
        }

        // Courier must be active
        return $courier->status === 1;
    }

    /**
     * Determine if merchant can view courier performance
     */
    public function viewPerformance(User $user, Courier $courier): bool
    {
        return $user->id === $courier->merchant_id;
    }
}
