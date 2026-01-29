<?php

namespace App\Domain\Commerce\Builders;

use App\Domain\Commerce\DTOs\FavoriteItemDTO;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Services\PriceFormatterService;

/**
 * FavoriteItemDTOBuilder - Builds FavoriteItemDTO from FavoriteSeller model
 *
 * Single source of truth for converting FavoriteSeller to display data.
 * Centralizes all business logic for favorite item display.
 *
 * Domain: Commerce
 * Responsibility: Build FavoriteItemDTO with all display data
 *
 * ARCHITECTURE:
 * - Builder Pattern
 * - Single Responsibility Principle
 * - Dependency Injection
 */
class FavoriteItemDTOBuilder
{
    public function __construct(
        private readonly PriceFormatterService $priceFormatter
    ) {}

    /**
     * Build FavoriteItemDTO from FavoriteSeller model
     *
     * @param FavoriteSeller $favorite
     * @return FavoriteItemDTO
     */
    public function build(FavoriteSeller $favorite): FavoriteItemDTO
    {
        $catalogItem = $favorite->catalogItem;
        $effectiveMerchantItem = $favorite->getEffectiveMerchantItem();

        // Get price
        $price = 0;
        $previousPrice = null;
        if ($effectiveMerchantItem) {
            $price = $effectiveMerchantItem->merchantSizePrice();
            if ($effectiveMerchantItem->discount_price > 0 && $effectiveMerchantItem->discount_price < $effectiveMerchantItem->price) {
                $previousPrice = $effectiveMerchantItem->price;
            }
        } elseif ($catalogItem) {
            $price = $catalogItem->lowest_price ?? 0;
            if ($catalogItem->previous_price > 0 && $catalogItem->previous_price > $price) {
                $previousPrice = $catalogItem->previous_price;
            }
        }

        // Get stock status
        $hasStock = true;
        if ($effectiveMerchantItem) {
            $hasStock = ($effectiveMerchantItem->stock ?? 0) > 0;
        } elseif ($catalogItem) {
            $hasStock = $catalogItem->merchantItems()
                ->where('status', 1)
                ->where('stock', '>', 0)
                ->exists();
        }

        // Get merchant info
        $merchantName = null;
        $qualityBrandName = null;
        $qualityBrandLogo = null;
        if ($effectiveMerchantItem) {
            $merchantName = $effectiveMerchantItem->user?->shop_name ?? $effectiveMerchantItem->user?->name;
            $qualityBrandName = $effectiveMerchantItem->qualityBrand?->name;
            $qualityBrandLogo = $effectiveMerchantItem->qualityBrand?->logo
                ? \Illuminate\Support\Facades\Storage::url($effectiveMerchantItem->qualityBrand->logo)
                : null;
        }

        // Build DTO
        return new FavoriteItemDTO(
            favoriteId: $favorite->id,
            catalogItemId: $catalogItem->id,
            partNumber: $catalogItem->part_number,
            name: $this->truncateName($catalogItem->name),
            catalogItemUrl: route('front.part-result', $catalogItem->part_number),
            photoUrl: $catalogItem->photo
                ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo)
                : asset('assets/images/noimage.png'),
            priceFormatted: $this->priceFormatter->format($price),
            previousPriceFormatted: $previousPrice ? $this->priceFormatter->format($previousPrice) : null,
            hasStock: $hasStock,
            merchantItemId: $effectiveMerchantItem?->id,
            merchantName: $merchantName,
            qualityBrandName: $qualityBrandName,
            qualityBrandLogo: $qualityBrandLogo,
        );
    }

    /**
     * Truncate name to 35 characters
     */
    private function truncateName(string $name): string
    {
        if (mb_strlen($name, 'UTF-8') > 35) {
            return mb_substr($name, 0, 35, 'UTF-8') . '...';
        }
        return $name;
    }
}
