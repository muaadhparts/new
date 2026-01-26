<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Models\Brand;

/**
 * BrandCardDTO - Pre-computed data for brand card display
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class BrandCardDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $photoUrl,
        public readonly string $detailsUrl,
        public readonly int $catalogsCount,
        public readonly int $itemsCount,
    ) {}

    /**
     * Build DTO from Brand model
     */
    public static function fromModel(Brand $brand): self
    {
        return new self(
            id: $brand->id,
            name: $brand->localized_name ?? $brand->name,
            slug: $brand->slug ?? '',
            photoUrl: $brand->photo_url ?? asset('assets/images/noimage.png'),
            detailsUrl: route('catlogs.index', $brand->name ?? $brand->slug ?? $brand->id),
            catalogsCount: $brand->catalogs_count ?? 0,
            itemsCount: $brand->catalog_items_count ?? 0,
        );
    }

    /**
     * Build collection of DTOs from Brand collection
     */
    public static function fromCollection($brands): array
    {
        return $brands->map(fn($brand) => self::fromModel($brand))->toArray();
    }
}
