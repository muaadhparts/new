<?php

namespace App\Domain\Catalog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\CatalogItem;

/**
 * Process Review Job
 *
 * Processes a new review and updates product rating.
 */
class ProcessReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CatalogReview $review
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Auto-approve if from verified purchaser
        if ($this->isVerifiedPurchaser()) {
            $this->review->update(['status' => 'approved']);
        }

        // Update catalog item rating if approved
        if ($this->review->status === 'approved') {
            $this->updateProductRating();
        }
    }

    /**
     * Check if reviewer is verified purchaser
     */
    protected function isVerifiedPurchaser(): bool
    {
        if (!$this->review->user_id) {
            return false;
        }

        // Check if user has purchased this item
        return \App\Domain\Commerce\Models\Purchase::where('user_id', $this->review->user_id)
            ->where('status', 'delivered')
            ->whereJsonContains('cart', [['catalog_item_id' => $this->review->catalog_item_id]])
            ->exists();
    }

    /**
     * Update product rating
     */
    protected function updateProductRating(): void
    {
        $catalogItem = CatalogItem::find($this->review->catalog_item_id);

        if (!$catalogItem) {
            return;
        }

        $stats = CatalogReview::where('catalog_item_id', $catalogItem->id)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        $catalogItem->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
            'rating_count' => $stats->count ?? 0,
        ]);
    }
}
