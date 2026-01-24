<?php

namespace App\Domain\Commerce\Traits;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Enums\PurchaseStatus;

/**
 * Has Purchases Trait
 *
 * Provides purchase history functionality for user models.
 */
trait HasPurchases
{
    /**
     * Get purchases relationship
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'user_id');
    }

    /**
     * Get active purchases
     */
    public function activePurchases()
    {
        return $this->purchases()
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped']);
    }

    /**
     * Get completed purchases
     */
    public function completedPurchases()
    {
        return $this->purchases()->where('status', 'delivered');
    }

    /**
     * Get cancelled purchases
     */
    public function cancelledPurchases()
    {
        return $this->purchases()->where('status', 'cancelled');
    }

    /**
     * Get total orders count
     */
    public function getTotalOrdersCount(): int
    {
        return $this->purchases()->count();
    }

    /**
     * Get total spent amount
     */
    public function getTotalSpent(): float
    {
        return (float) $this->purchases()
            ->where('status', 'delivered')
            ->sum('total');
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValue(): float
    {
        $completed = $this->completedPurchases();
        $count = $completed->count();

        if ($count === 0) {
            return 0;
        }

        return $completed->sum('total') / $count;
    }

    /**
     * Check if has any purchases
     */
    public function hasPurchases(): bool
    {
        return $this->purchases()->exists();
    }

    /**
     * Check if is first purchase
     */
    public function isFirstPurchase(): bool
    {
        return !$this->hasPurchases();
    }

    /**
     * Get last purchase
     */
    public function getLastPurchase(): ?Purchase
    {
        return $this->purchases()->latest()->first();
    }

    /**
     * Get last purchase date
     */
    public function getLastPurchaseDate(): ?\Carbon\Carbon
    {
        return $this->getLastPurchase()?->created_at;
    }

    /**
     * Check if purchased item
     */
    public function hasPurchasedItem(int $itemId): bool
    {
        return $this->purchases()
            ->where('status', 'delivered')
            ->whereJsonContains('cart', [['merchant_item_id' => $itemId]])
            ->exists();
    }

    /**
     * Get recent purchases
     */
    public function getRecentPurchases(int $limit = 5)
    {
        return $this->purchases()->latest()->limit($limit)->get();
    }

    /**
     * Check if can review item
     */
    public function canReviewItem(int $itemId): bool
    {
        return $this->hasPurchasedItem($itemId);
    }
}
