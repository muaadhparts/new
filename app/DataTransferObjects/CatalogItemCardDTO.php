<?php

namespace App\DataTransferObjects;

use App\Models\MerchantItem;
use App\Models\CatalogItem;
use Illuminate\Support\Collection;

/**
 * CatalogItemCardDTO
 *
 * Pre-computed data for catalog item card display.
 * Blade views should ONLY read properties - no logic, no queries.
 */
class CatalogItemCardDTO
{
    // CatalogItem
    public int $catalogItemId;
    public string $productName;
    public string $productSlug;
    public ?string $sku;
    public string $photo;
    public string $type;
    public string $productType;
    public ?string $affiliateLink;
    public float $catalogReviewsAvg;
    public int $catalogReviewsCount;

    // MerchantItem
    public ?int $merchantItemId = null;
    public ?int $merchantId = null;
    public float $price;
    public string $priceFormatted;
    public float $previousPrice;
    public string $previousPriceFormatted;
    public int $stock;
    public bool $preordered;
    public int $minQty;

    // Computed
    public bool $inStock;
    public bool $hasVendor;
    public int $offPercentage;
    public string $detailsUrl;
    public bool $isInFavorites;
    public string $favoriteUrl;
    public string $compareUrl;

    // Merchant
    public ?string $merchantName;

    // Brand
    public ?string $brandName;
    public ?string $brandLogo;

    // Quality Brand
    public ?string $qualityBrandName;
    public ?string $qualityBrandLogo;

    // Stock display
    public string $stockText;
    public string $stockClass;
    public string $stockBadgeClass;


    /**
     * Build DTO from MerchantItem with all pre-loaded relations
     */
    public static function fromMerchantItem(
        MerchantItem $merchant,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): self {
        $dto = new self();
        $catalogItem = $merchant->catalogItem;

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->productName = $catalogItem->showName();
        $dto->productSlug = $catalogItem->slug ?? '';
        $dto->sku = $catalogItem->sku;
        $dto->photo = self::resolvePhoto($catalogItem->photo);
        $dto->type = $catalogItem->type ?? 'Physical';
        // product_type is now on merchant_items, not catalog_items
        $dto->productType = $merchant->product_type ?? '';
        // affiliate_link is now on merchant_items, not catalog_items
        $dto->affiliateLink = $merchant->affiliate_link;
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // MerchantItem data
        $dto->merchantItemId = $merchant->id;
        $dto->merchantId = $merchant->user_id;
        $dto->price = (float) $merchant->price;
        $dto->priceFormatted = $merchant->showPrice();
        $dto->previousPrice = (float) ($merchant->previous_price ?? 0);
        $dto->previousPriceFormatted = $dto->previousPrice > 0
            ? CatalogItem::convertPrice($dto->previousPrice)
            : '';
        $dto->stock = (int) ($merchant->stock ?? 0);
        $dto->preordered = (bool) $merchant->preordered;
        $dto->minQty = max(1, (int) ($merchant->minimum_qty ?? 1));

        // Computed values
        $dto->inStock = $dto->stock > 0 || $dto->preordered;
        $dto->hasVendor = $dto->merchantId > 0;
        $dto->offPercentage = self::calculateOffPercentage($dto->previousPrice, $dto->price);
        $dto->detailsUrl = self::buildDetailsUrl($dto->productSlug, $dto->merchantId, $dto->merchantItemId);

        // Favorites
        $dto->isInFavorites = $favoriteMerchantIds->contains($dto->merchantItemId);
        $dto->favoriteUrl = route('merchant.favorite.add', $dto->merchantItemId);
        $dto->compareUrl = route('merchant.compare.add', $dto->merchantItemId);

        // Merchant (from eager-loaded relation) - localized
        $dto->merchantName = $merchant->user ? getLocalizedShopName($merchant->user) : null;

        // Brand (from eager-loaded relation)
        $dto->brandName = $catalogItem->brand?->localized_name;
        $dto->brandLogo = $catalogItem->brand?->photo_url;

        // Quality Brand (from eager-loaded relation)
        $dto->qualityBrandName = $merchant->qualityBrand?->localized_name;
        $dto->qualityBrandLogo = $merchant->qualityBrand?->logo_url;

        // Stock display
        self::setStockDisplay($dto);

        return $dto;
    }

    /**
     * Build DTO from CatalogItem model (legacy support)
     */
    public static function fromCatalogItem(
        CatalogItem $catalogItem,
        ?MerchantItem $merchant,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): self {
        if ($merchant) {
            return self::fromMerchantItem($merchant, $favoriteCatalogItemIds, $favoriteMerchantIds);
        }

        $dto = new self();

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->productName = $catalogItem->showName();
        $dto->productSlug = $catalogItem->slug ?? '';
        $dto->sku = $catalogItem->sku;
        $dto->photo = self::resolvePhoto($catalogItem->photo);
        $dto->type = $catalogItem->type ?? 'Physical';
        // product_type is now on merchant_items - no type without merchant
        $dto->productType = '';
        // affiliate_link is now on merchant_items - no link without merchant
        $dto->affiliateLink = null;
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // No merchant
        $dto->merchantItemId = null;
        $dto->merchantId = null;
        $dto->price = 0;
        $dto->priceFormatted = $catalogItem->showPrice();
        $dto->previousPrice = 0;
        $dto->previousPriceFormatted = '';
        $dto->stock = 0;
        $dto->preordered = false;
        $dto->minQty = 1;

        // Computed
        $dto->inStock = false;
        $dto->hasVendor = false;
        $dto->offPercentage = 0;
        $dto->detailsUrl = route('front.catalog-item.legacy', $dto->productSlug);

        // Favorites (catalog item level)
        $dto->isInFavorites = $favoriteCatalogItemIds->contains($dto->catalogItemId);
        $dto->favoriteUrl = route('user-favorite-add', $dto->catalogItemId);
        $dto->compareUrl = route('catalog-item.compare.add', $dto->catalogItemId);

        // No merchant/brand info without merchant
        $dto->merchantName = null;
        $dto->brandName = $catalogItem->brand?->localized_name;
        $dto->brandLogo = $catalogItem->brand?->photo_url;
        $dto->qualityBrandName = null;
        $dto->qualityBrandLogo = null;

        // Stock display
        self::setStockDisplay($dto);

        return $dto;
    }

    private static function resolvePhoto(?string $photo): string
    {
        if (!$photo) {
            return asset('assets/images/noimage.png');
        }

        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        return \Illuminate\Support\Facades\Storage::url($photo);
    }

    private static function calculateOffPercentage(float $previousPrice, float $currentPrice): int
    {
        if ($previousPrice <= 0 || $currentPrice <= 0) {
            return 0;
        }

        return (int) round((($previousPrice - $currentPrice) * 100) / $previousPrice);
    }

    private static function buildDetailsUrl(string $slug, ?int $merchantId, ?int $merchantItemId): string
    {
        if (!$slug) {
            return '#';
        }

        if ($merchantId && $merchantItemId) {
            return route('front.catalog-item', [
                'slug' => $slug,
                'merchant_id' => $merchantId,
                'merchant_item_id' => $merchantItemId
            ]);
        }

        return route('front.catalog-item.legacy', $slug);
    }

    private static function setStockDisplay(self $dto): void
    {
        if ($dto->stock === 0 && !$dto->preordered) {
            $dto->stockText = __('Out Of Stock');
            $dto->stockClass = 'text-danger';
            $dto->stockBadgeClass = 'bg-danger';
        } elseif ($dto->stock > 0) {
            $dto->stockText = $dto->stock . ' ' . __('Available');
            $dto->stockClass = 'text-primary';
            $dto->stockBadgeClass = 'bg-primary';
        } else {
            $dto->stockText = __('Unlimited');
            $dto->stockClass = 'text-success';
            $dto->stockBadgeClass = 'bg-success';
        }
    }
}
