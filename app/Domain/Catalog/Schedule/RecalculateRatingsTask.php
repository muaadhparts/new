<?php

namespace App\Domain\Catalog\Schedule;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Recalculate Ratings Task
 *
 * Recalculates average ratings for all catalog items.
 */
class RecalculateRatingsTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $ratings = CatalogReview::where('status', 'approved')
            ->select('catalog_item_id')
            ->selectRaw('AVG(rating) as avg_rating')
            ->selectRaw('COUNT(*) as review_count')
            ->groupBy('catalog_item_id')
            ->get();

        $updated = 0;

        foreach ($ratings as $rating) {
            CatalogItem::where('id', $rating->catalog_item_id)
                ->update([
                    'average_rating' => round($rating->avg_rating, 2),
                    'review_count' => $rating->review_count,
                ]);
            $updated++;
        }

        // Reset ratings for items with no reviews
        $itemsWithNoReviews = CatalogItem::whereNotIn('id', $ratings->pluck('catalog_item_id'))
            ->where(function ($query) {
                $query->where('average_rating', '>', 0)
                    ->orWhere('review_count', '>', 0);
            })
            ->update([
                'average_rating' => 0,
                'review_count' => 0,
            ]);

        Log::info('Ratings recalculated', [
            'items_updated' => $updated,
            'items_reset' => $itemsWithNoReviews,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'hourly';
    }
}
