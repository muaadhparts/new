<?php

namespace App\Domain\Merchant\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Merchant Item Policy
 *
 * Determines authorization for merchant item/inventory actions.
 */
class MerchantItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view any items
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can view the item
     */
    public function view(User $user, MerchantItem $item): bool
    {
        return $user->id === $item->merchant_id;
    }

    /**
     * Determine if merchant can create items
     */
    public function create(User $user): bool
    {
        // Must be active merchant
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can update the item
     */
    public function update(User $user, MerchantItem $item): bool
    {
        return $user->id === $item->merchant_id;
    }

    /**
     * Determine if merchant can delete the item
     */
    public function delete(User $user, MerchantItem $item): bool
    {
        // Must own the item
        if ($user->id !== $item->merchant_id) {
            return false;
        }

        // Cannot delete items with pending orders
        // This should be checked at service level
        return true;
    }

    /**
     * Determine if merchant can update stock
     */
    public function updateStock(User $user, MerchantItem $item): bool
    {
        return $user->id === $item->merchant_id;
    }

    /**
     * Determine if merchant can update price
     */
    public function updatePrice(User $user, MerchantItem $item): bool
    {
        return $user->id === $item->merchant_id;
    }

    /**
     * Determine if merchant can toggle status
     */
    public function toggleStatus(User $user, MerchantItem $item): bool
    {
        return $user->id === $item->merchant_id;
    }

    /**
     * Determine if merchant can bulk update items
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can export items
     */
    public function export(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }
}
