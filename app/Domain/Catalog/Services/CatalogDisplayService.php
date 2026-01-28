<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Collection;

/**
 * CatalogDisplayService - Centralized formatting for catalog display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class CatalogDisplayService
{
    // =========================================================================
    // RATING FORMATTING
    // =========================================================================

    /**
     * Format rating for display
     * Returns null if no reviews, otherwise formatted number
     */
    public function formatRating(?float $avgRating, int $reviewsCount): ?string
    {
        if ($reviewsCount <= 0) {
            return null;
        }

        return number_format($avgRating ?? 0, 1);
    }

    /**
     * Get rating display data
     */
    public function getRatingDisplay(?float $avgRating, int $reviewsCount): array
    {
        return [
            'rating_formatted' => $this->formatRating($avgRating, $reviewsCount),
            'reviews_count' => $reviewsCount,
            'has_reviews' => $reviewsCount > 0,
        ];
    }

    // =========================================================================
    // FITMENT BRANDS FORMATTING
    // =========================================================================

    /**
     * Extract fitment brands from catalog item for display
     */
    public function extractFitmentBrands(CatalogItem $catalogItem): array
    {
        if (!$catalogItem->fitments || $catalogItem->fitments->count() === 0) {
            return [];
        }

        return $catalogItem->fitments
            ->map(fn($f) => $f->brand)
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn($brand) => [
                'id' => $brand->id,
                'name' => $brand->localized_name,
                'logo' => $brand->photo_url,
                'slug' => $brand->slug,
            ])
            ->toArray();
    }

    // =========================================================================
    // CATALOG ITEM DISPLAY DATA
    // =========================================================================

    /**
     * Build display data for part result page
     */
    public function forPartResult(CatalogItem $catalogItem): array
    {
        $avgRating = $catalogItem->catalog_reviews_avg_rating ?? null;
        $reviewsCount = $catalogItem->catalog_reviews_count ?? 0;

        return [
            'id' => $catalogItem->id,
            'part_number' => $catalogItem->part_number,
            'name' => $catalogItem->localized_name,
            'photo_url' => app(CatalogItemDisplayService::class)->getPhotoUrl($catalogItem),
            'rating_formatted' => $this->formatRating($avgRating, $reviewsCount),
            'reviews_count' => $reviewsCount,
            'has_reviews' => $reviewsCount > 0,
            'fitment_brands' => $this->extractFitmentBrands($catalogItem),
        ];
    }

    /**
     * Build display data for alternatives list
     */
    public function formatAlternatives(Collection $alternatives): Collection
    {
        return $alternatives->map(function (CatalogItem $item) {
            return [
                'id' => $item->id,
                'part_number' => $item->part_number,
                'name' => $item->localized_name ?: '-',
                'photo_url' => $item->photo_url,
                'offers_count' => $item->offers_count ?? 0,
                'lowest_price_formatted' => $item->lowest_price_formatted ?? null,
            ];
        });
    }

    // =========================================================================
    // PRICE FORMATTING
    // =========================================================================

    /**
     * Format price using monetaryUnit service
     */
    public function formatPrice(float $amount): string
    {
        return monetaryUnit()->format($amount);
    }
}
