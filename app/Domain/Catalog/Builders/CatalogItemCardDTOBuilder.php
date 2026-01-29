<?php

namespace App\Domain\Catalog\Builders;

use App\Domain\Catalog\DTOs\CatalogItemCardDTO;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\CatalogItemDisplayService;
use App\Domain\Commerce\Services\PriceFormatterService;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Collection;

/**
 * CatalogItemCardDTOBuilder
 *
 * Builder pattern for constructing CatalogItemCardDTO instances.
 * Centralizes all DTO building logic with proper dependency injection.
 *
 * ARCHITECTURE:
 * - Builder Pattern
 * - Constructor Dependency Injection
 * - Single Responsibility: Build DTOs only
 *
 * BENEFITS:
 * - Testable (can mock dependencies)
 * - Reusable across Web/API/Mobile
 * - Single source of truth for DTO construction
 */
class CatalogItemCardDTOBuilder
{
    public function __construct(
        private PriceFormatterService $priceFormatter,
        private CatalogItemDisplayService $catalogDisplayService
    ) {}

    /**
     * Build DTO from MerchantItem with all pre-loaded relations
     */
    public function fromMerchantItem(
        MerchantItem $merchant,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): CatalogItemCardDTO {
        $dto = new CatalogItemCardDTO();
        $catalogItem = $merchant->catalogItem;

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $this->catalogDisplayService->getLocalizedName($catalogItem);
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = $this->resolvePhoto($catalogItem->photo);
        $dto->itemType = $merchant->item_type ?? '';
        $dto->affiliateLink = $merchant->affiliate_link;
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // MerchantItem data
        $dto->merchantItemId = $merchant->id;
        $dto->merchantId = $merchant->user_id;
        $dto->price = (float) $merchant->price;
        $dto->priceFormatted = $this->priceFormatter->format($merchant->price);
        $dto->previousPrice = (float) ($merchant->previous_price ?? 0);
        $dto->previousPriceFormatted = $dto->previousPrice > 0
            ? $this->priceFormatter->format($dto->previousPrice)
            : '';
        $dto->stock = (int) ($merchant->stock ?? 0);
        $dto->preordered = (bool) $merchant->preordered;
        $dto->minQty = max(1, (int) ($merchant->minimum_qty ?? 1));

        // Computed values
        $dto->inStock = $dto->stock > 0 || $dto->preordered;
        $dto->hasMerchant = true;

        // Discount calculation
        if ($dto->previousPrice > 0 && $dto->previousPrice > $dto->price) {
            $dto->offPercentage = (int) round((($dto->previousPrice - $dto->price) / $dto->previousPrice) * 100);
            $dto->offPercentageFormatted = $dto->offPercentage . '%';
        } else {
            $dto->offPercentage = 0;
            $dto->offPercentageFormatted = null;
        }

        // URLs
        $dto->detailsUrl = $catalogItem->part_number 
            ? route('front.part-result', $catalogItem->part_number)
            : 'javascript:;';
        $dto->isInFavorites = $favoriteMerchantIds->contains($merchant->id);
        $dto->favoriteUrl = route('user-favorite-toggle', $merchant->id);

        // Merchant
        $dto->merchantName = $merchant->user?->name;

        // Branch
        $dto->branchId = $merchant->merchant_branch_id;
        $dto->branchName = $merchant->branch?->name;

        // Vehicle Fitment Brands
        $this->buildFitmentData($dto, $catalogItem);

        // Quality Brand
        $dto->qualityBrandName = $merchant->qualityBrand?->name;
        $dto->qualityBrandLogo = $merchant->qualityBrand?->logo 
            ? asset('assets/images/quality_brands/' . $merchant->qualityBrand->logo)
            : null;

        // Stock display
        $this->buildStockDisplay($dto);

        // Offers count
        $dto->offersCount = (int) ($catalogItem->active_merchant_items_count ?? $catalogItem->offers_count ?? 1);
        $dto->hasMultipleOffers = $dto->offersCount > 1;

        return $dto;
    }

    /**
     * Build DTO from CatalogItem without merchant (no offers)
     */
    public function fromCatalogItem(
        CatalogItem $catalogItem,
        Collection $favoriteCatalogItemIds
    ): CatalogItemCardDTO {
        $dto = new CatalogItemCardDTO();

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $this->catalogDisplayService->getLocalizedName($catalogItem);
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = $this->resolvePhoto($catalogItem->photo);
        $dto->itemType = '';
        $dto->affiliateLink = null;
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // No merchant
        $dto->merchantItemId = null;
        $dto->merchantId = null;
        $dto->price = 0;
        $dto->priceFormatted = $this->priceFormatter->format(0);
        $dto->previousPrice = 0;
        $dto->previousPriceFormatted = '';
        $dto->stock = 0;
        $dto->preordered = false;
        $dto->minQty = 1;

        // Computed
        $dto->inStock = false;
        $dto->hasMerchant = false;
        $dto->offPercentage = 0;
        $dto->offPercentageFormatted = null;

        // URLs
        $dto->detailsUrl = $catalogItem->part_number 
            ? route('front.part-result', $catalogItem->part_number)
            : 'javascript:;';
        $dto->isInFavorites = false; // No merchant, can't favorite
        $dto->favoriteUrl = null; // No merchant item to favorite

        // Merchant
        $dto->merchantName = null;

        // Branch
        $dto->branchId = null;
        $dto->branchName = null;

        // Vehicle Fitment Brands
        $this->buildFitmentData($dto, $catalogItem);

        // Quality Brand
        $dto->qualityBrandName = null;
        $dto->qualityBrandLogo = null;

        // Stock display
        $this->buildStockDisplay($dto);

        // Offers count
        $dto->offersCount = 0;
        $dto->hasMultipleOffers = false;

        return $dto;
    }

    /**
     * Build DTO from CatalogItem with best merchant
     */
    public function fromCatalogItemFirst(
        CatalogItem $catalogItem,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): CatalogItemCardDTO {
        $dto = new CatalogItemCardDTO();

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $this->catalogDisplayService->getLocalizedName($catalogItem);
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = $this->resolvePhoto($catalogItem->photo);
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // Get best merchant (lowest price)
        $bestMerchant = $catalogItem->merchantItems
            ->where('status', 1)
            ->sortBy('price')
            ->first();

        if ($bestMerchant) {
            $dto->merchantItemId = $bestMerchant->id;
            $dto->merchantId = $bestMerchant->user_id;
            $dto->price = (float) $bestMerchant->price;
            $dto->priceFormatted = $this->priceFormatter->format($bestMerchant->price);
            $dto->previousPrice = (float) ($bestMerchant->previous_price ?? 0);
            $dto->previousPriceFormatted = $dto->previousPrice > 0
                ? $this->priceFormatter->format($dto->previousPrice)
                : '';
            $dto->stock = (int) ($bestMerchant->stock ?? 0);
            $dto->preordered = (bool) $bestMerchant->preordered;
            $dto->minQty = max(1, (int) ($bestMerchant->minimum_qty ?? 1));
            $dto->itemType = $bestMerchant->item_type ?? '';
            $dto->affiliateLink = $bestMerchant->affiliate_link;
            $dto->merchantName = $bestMerchant->user?->name;
            $dto->branchId = $bestMerchant->merchant_branch_id;
            $dto->branchName = $bestMerchant->branch?->name;
            $dto->qualityBrandName = $bestMerchant->qualityBrand?->name;
            $dto->qualityBrandLogo = $bestMerchant->qualityBrand?->logo 
                ? asset('assets/images/quality_brands/' . $bestMerchant->qualityBrand->logo)
                : null;
            $dto->inStock = $dto->stock > 0 || $dto->preordered;
            $dto->hasMerchant = true;

            // Discount
            if ($dto->previousPrice > 0 && $dto->previousPrice > $dto->price) {
                $dto->offPercentage = (int) round((($dto->previousPrice - $dto->price) / $dto->previousPrice) * 100);
                $dto->offPercentageFormatted = $dto->offPercentage . '%';
            } else {
                $dto->offPercentage = 0;
                $dto->offPercentageFormatted = null;
            }
        } else {
            // No merchant available
            $dto->merchantItemId = null;
            $dto->merchantId = null;
            $dto->price = (float) ($catalogItem->lowest_price ?? 0);
            $dto->priceFormatted = $dto->price > 0
                ? $this->priceFormatter->format($dto->price)
                : __('No offers');
            $dto->previousPrice = 0;
            $dto->previousPriceFormatted = '';
            $dto->stock = 0;
            $dto->preordered = false;
            $dto->minQty = 1;
            $dto->itemType = '';
            $dto->affiliateLink = null;
            $dto->merchantName = null;
            $dto->branchId = null;
            $dto->branchName = null;
            $dto->qualityBrandName = null;
            $dto->qualityBrandLogo = null;
            $dto->inStock = false;
            $dto->hasMerchant = false;
            $dto->offPercentage = 0;
            $dto->offPercentageFormatted = null;
        }

        // URLs
        $dto->detailsUrl = $catalogItem->part_number 
            ? route('front.part-result', $catalogItem->part_number)
            : 'javascript:;';
        $dto->isInFavorites = $dto->merchantItemId && $favoriteMerchantIds->contains($dto->merchantItemId);
        $dto->favoriteUrl = $dto->merchantItemId 
            ? route('user-favorite-toggle', $dto->merchantItemId)
            : null;

        // Vehicle Fitment Brands
        $this->buildFitmentData($dto, $catalogItem);

        // Stock display
        $this->buildStockDisplay($dto);

        // Offers count (support both active_merchant_items_count and offers_count)
        $dto->offersCount = (int) ($catalogItem->active_merchant_items_count ?? $catalogItem->offers_count ?? 0);
        $dto->hasMultipleOffers = $dto->offersCount > 1;

        return $dto;
    }

    /**
     * Build collection of DTOs
     */
    public function buildCollection(
        Collection $items,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds,
        string $sourceType = 'merchant_item'
    ): array {
        return $items->map(function ($item) use ($favoriteCatalogItemIds, $favoriteMerchantIds, $sourceType) {
            return match ($sourceType) {
                'merchant_item' => $this->fromMerchantItem($item, $favoriteCatalogItemIds, $favoriteMerchantIds),
                'catalog_item' => $this->fromCatalogItem($item, $favoriteCatalogItemIds),
                'catalog_item_first' => $this->fromCatalogItemFirst($item, $favoriteCatalogItemIds, $favoriteMerchantIds),
                default => throw new \InvalidArgumentException("Unknown source type: {$sourceType}"),
            };
        })->toArray();
    }

    /**
     * Resolve photo URL
     */
    private function resolvePhoto(?string $photo): string
    {
        if ($photo) {
            return asset('assets/images/catalog_items/' . $photo);
        }
        return asset('assets/images/noimage.png');
    }

    /**
     * Build fitment data for DTO
     */
    private function buildFitmentData(CatalogItemCardDTO $dto, CatalogItem $catalogItem): void
    {
        if ($catalogItem->relationLoaded('fitments') && $catalogItem->fitments->isNotEmpty()) {
            $dto->fitmentBrands = $catalogItem->fitments->map(function ($fitment) {
                return [
                    'id' => $fitment->brand_id,
                    'name' => $fitment->brand?->name ?? '',
                    'logo' => $fitment->brand?->logo 
                        ? asset('assets/images/brands/' . $fitment->brand->logo)
                        : null,
                    'slug' => $fitment->brand?->slug ?? '',
                ];
            })->toArray();

            $dto->fitmentCount = count($dto->fitmentBrands);
            $dto->hasSingleBrand = $dto->fitmentCount === 1;
            $dto->fitsMultipleBrands = $dto->fitmentCount > 1;
        } else {
            $dto->fitmentBrands = [];
            $dto->fitmentCount = 0;
            $dto->hasSingleBrand = false;
            $dto->fitsMultipleBrands = false;
        }
    }

    /**
     * Build stock display data
     */
    private function buildStockDisplay(CatalogItemCardDTO $dto): void
    {
        if ($dto->preordered) {
            $dto->stockText = __('Pre-order');
            $dto->stockClass = 'text-info';
            $dto->stockBadgeClass = 'badge-info';
        } elseif ($dto->stock > 0) {
            $dto->stockText = __('In Stock');
            $dto->stockClass = 'text-success';
            $dto->stockBadgeClass = 'badge-success';
        } else {
            $dto->stockText = __('Out of Stock');
            $dto->stockClass = 'text-danger';
            $dto->stockBadgeClass = 'badge-danger';
        }
    }
}
