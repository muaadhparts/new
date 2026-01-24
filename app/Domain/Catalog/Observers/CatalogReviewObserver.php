<?php

namespace App\Domain\Catalog\Observers;

use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\CatalogItem;

/**
 * Catalog Review Observer
 *
 * Handles CatalogReview model lifecycle events.
 */
class CatalogReviewObserver
{
    /**
     * Handle the CatalogReview "creating" event.
     */
    public function creating(CatalogReview $review): void
    {
        // Set default status (pending approval)
        if (!isset($review->status)) {
            $review->status = 0; // Pending
        }

        // Set user if authenticated
        if (empty($review->user_id) && auth()->check()) {
            $review->user_id = auth()->id();
        }
    }

    /**
     * Handle the CatalogReview "created" event.
     */
    public function created(CatalogReview $review): void
    {
        // Update catalog item rating if review is approved
        if ($review->status === 1) {
            $this->updateCatalogItemRating($review->catalog_item_id);
        }
    }

    /**
     * Handle the CatalogReview "updated" event.
     */
    public function updated(CatalogReview $review): void
    {
        // Update rating if status or rating changed
        if ($review->wasChanged(['status', 'rating'])) {
            $this->updateCatalogItemRating($review->catalog_item_id);
        }
    }

    /**
     * Handle the CatalogReview "deleted" event.
     */
    public function deleted(CatalogReview $review): void
    {
        // Recalculate rating after deletion
        $this->updateCatalogItemRating($review->catalog_item_id);
    }

    /**
     * Update catalog item's average rating
     */
    protected function updateCatalogItemRating(int $catalogItemId): void
    {
        $stats = CatalogReview::where('catalog_item_id', $catalogItemId)
            ->where('status', 1) // Only approved reviews
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as reviews_count')
            ->first();

        CatalogItem::where('id', $catalogItemId)->update([
            'average_rating' => round($stats->avg_rating ?? 0, 2),
            'reviews_count' => $stats->reviews_count ?? 0,
        ]);
    }
}
