<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Models\Catalog;

/**
 * CatalogCardDTO - Pre-computed data for catalog (vehicle) card display
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class CatalogCardDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $photoUrl,
        public readonly string $largeImageUrl,
        public readonly string $detailsUrl,
        public readonly ?string $brandName,
        public readonly ?string $brandLogo,
        public readonly ?string $brandSlug,
        public readonly ?string $yearRange,
        public readonly int $sectionsCount,
        public readonly int $itemsCount,
    ) {}

    /**
     * Build DTO from Catalog model
     */
    public static function fromModel(Catalog $catalog): self
    {
        $brand = $catalog->brand;

        return new self(
            id: $catalog->id,
            name: $catalog->localized_name ?? $catalog->name,
            slug: $catalog->slug ?? '',
            photoUrl: $catalog->photo_url ?? asset('assets/images/noimage.png'),
            largeImageUrl: $catalog->largeImagePath
                ? \Illuminate\Support\Facades\Storage::url($catalog->largeImagePath)
                : asset('assets/images/noimage.png'),
            detailsUrl: route('front.catalog', [
                'brand' => $brand?->slug ?? 'unknown',
                'catalog' => $catalog->slug ?? $catalog->id
            ]),
            brandName: $brand?->localized_name ?? $brand?->name,
            brandLogo: $brand?->photo_url,
            brandSlug: $brand?->slug,
            yearRange: self::formatYearRange($catalog->year_begin, $catalog->year_end),
            sectionsCount: $catalog->sections_count ?? 0,
            itemsCount: $catalog->items_count ?? $catalog->catalog_items_count ?? 0,
        );
    }

    /**
     * Format year range for display
     */
    private static function formatYearRange($begin, $end): ?string
    {
        if (!$begin && !$end) {
            return null;
        }

        if ($begin && $end) {
            return "{$begin} - {$end}";
        }

        if ($begin) {
            return app()->getLocale() === 'ar'
                ? "{$begin} - " . __('Present')
                : "{$begin} - Present";
        }

        return (string) $end;
    }

    /**
     * Build collection of DTOs from Catalog collection
     */
    public static function fromCollection($catalogs): array
    {
        return $catalogs->map(fn($catalog) => self::fromModel($catalog))->toArray();
    }
}
