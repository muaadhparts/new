<?php

namespace App\Domain\Catalog\Policies;

use App\Models\User;
use App\Models\CatalogItem;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Catalog Item Policy
 *
 * Determines authorization for catalog item actions.
 * Note: CatalogItems are managed by operators, not merchants.
 * Merchants manage MerchantItems linked to CatalogItems.
 */
class CatalogItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any items
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can browse catalog
        return true;
    }

    /**
     * Determine if user can view the item
     */
    public function view(?User $user, CatalogItem $item): bool
    {
        // Anyone can view active items
        if ($item->status === 1) {
            return true;
        }

        // Operators can view all items
        // This would typically be handled by operator auth
        return false;
    }

    /**
     * Determine if user can add to favorites
     */
    public function favorite(User $user, CatalogItem $item): bool
    {
        // Must be logged in
        return $user !== null;
    }

    /**
     * Determine if user can add to cart
     */
    public function addToCart(?User $user, CatalogItem $item): bool
    {
        // Item must be active
        if ($item->status !== 1) {
            return false;
        }

        // Must have available offers
        return $item->merchantItems()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->exists();
    }

    /**
     * Determine if user can compare items
     */
    public function compare(?User $user): bool
    {
        // Anyone can compare
        return true;
    }

    /**
     * Determine if user can request price alert
     */
    public function priceAlert(User $user, CatalogItem $item): bool
    {
        return $user !== null;
    }

    /**
     * Determine if user can request stock alert
     */
    public function stockAlert(User $user, CatalogItem $item): bool
    {
        return $user !== null;
    }
}
