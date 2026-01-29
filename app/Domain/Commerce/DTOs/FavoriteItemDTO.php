<?php

namespace App\Domain\Commerce\DTOs;

/**
 * FavoriteItemDTO - Data Transfer Object for Favorite Items
 *
 * Single source of truth for favorite item display data.
 * Used in Web Views and API responses.
 *
 * Domain: Commerce
 * Responsibility: Transfer favorite item data from Service to View/API
 *
 * ARCHITECTURE:
 * - DTO Pattern (Data Transfer Object)
 * - Immutable after construction
 * - No business logic
 */
class FavoriteItemDTO
{
    public function __construct(
        public readonly int $favoriteId,
        public readonly int $catalogItemId,
        public readonly string $partNumber,
        public readonly string $name,
        public readonly string $catalogItemUrl,
        public readonly string $photoUrl,
        public readonly string $priceFormatted,
        public readonly ?string $previousPriceFormatted,
        public readonly bool $hasStock,
        public readonly ?int $merchantItemId,
        public readonly ?string $merchantName,
        public readonly ?string $qualityBrandName,
        public readonly ?string $qualityBrandLogo,
    ) {}

    /**
     * Convert DTO to array for API responses
     */
    public function toArray(): array
    {
        return [
            'favoriteId' => $this->favoriteId,
            'catalogItemId' => $this->catalogItemId,
            'partNumber' => $this->partNumber,
            'name' => $this->name,
            'catalogItemUrl' => $this->catalogItemUrl,
            'photoUrl' => $this->photoUrl,
            'priceFormatted' => $this->priceFormatted,
            'previousPriceFormatted' => $this->previousPriceFormatted,
            'hasStock' => $this->hasStock,
            'merchantItemId' => $this->merchantItemId,
            'merchantName' => $this->merchantName,
            'qualityBrandName' => $this->qualityBrandName,
            'qualityBrandLogo' => $this->qualityBrandLogo,
        ];
    }
}
