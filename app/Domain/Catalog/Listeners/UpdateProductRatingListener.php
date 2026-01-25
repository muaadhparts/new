<?php

namespace App\Domain\Catalog\Listeners;

use App\Domain\Catalog\Events\ProductReviewedEvent;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Update Product Rating Listener
 *
 * Updates product average rating when a new review is added.
 */
class UpdateProductRatingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(ProductReviewedEvent $event): void
    {
        $catalogItem = CatalogItem::find($event->catalogItemId);

        if (!$catalogItem) {
            Log::warning('UpdateProductRating: CatalogItem not found', [
                'catalog_item_id' => $event->catalogItemId,
            ]);
            return;
        }

        $this->recalculateRating($catalogItem);
    }

    /**
     * Recalculate product rating
     */
    protected function recalculateRating(CatalogItem $item): void
    {
        $stats = CatalogReview::where('catalog_item_id', $item->id)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        $item->update([
            'avg_rating' => round($stats->avg_rating ?? 0, 1),
            'review_count' => $stats->review_count ?? 0,
        ]);

        Log::info('Product rating updated', [
            'catalog_item_id' => $item->id,
            'avg_rating' => $stats->avg_rating,
            'review_count' => $stats->review_count,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductReviewedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to update product rating', [
            'catalog_item_id' => $event->catalogItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
