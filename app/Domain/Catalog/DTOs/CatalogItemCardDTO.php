<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Catalog\Models\CatalogItem;
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
    public string $catalogItemName;
    public string $catalogItemSlug;
    public ?string $part_number;
    public string $photo;
    public string $itemType;
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
    public bool $hasMerchant;
    public int $offPercentage;
    public string $detailsUrl;
    public bool $isInFavorites;
    public string $favoriteUrl;

    // Merchant
    public ?string $merchantName;

    // Branch
    public ?int $branchId = null;
    public ?string $branchName = null;

    // Vehicle Fitment Brands (from catalog_item_fitments)
    // A part can fit MULTIPLE vehicle brands - we store ALL of them
    public array $fitmentBrands = [];      // Array of ['id', 'name', 'logo', 'slug']
    public int $fitmentCount = 0;          // Number of brands this part fits
    public bool $hasSingleBrand = false;   // True if exactly 1 brand
    public bool $fitsMultipleBrands = false; // True if 2+ brands

    // Quality Brand
    public ?string $qualityBrandName;
    public ?string $qualityBrandLogo;

    // Stock display
    public string $stockText;
    public string $stockClass;
    public string $stockBadgeClass;

    // Offers Count (total active merchant items for this catalog item)
    public int $offersCount = 1;
    public bool $hasMultipleOffers = false;


    /**
     * Build DTO from MerchantItem with all pre-loaded relations
     */
    public static function fromMerchantItem(
        MerchantItem $merchant,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): static {
        $dto = new static();
        $catalogItem = $merchant->catalogItem;

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $catalogItem->showName();
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = self::resolvePhoto($catalogItem->photo);
        // item_type is now on merchant_items, not catalog_items
        $dto->itemType = $merchant->item_type ?? '';
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
        $dto->hasMerchant = $dto->merchantId > 0;
        $dto->offPercentage = self::calculateOffPercentage($dto->previousPrice, $dto->price);
        $dto->detailsUrl = self::buildDetailsUrl($dto->catalogItemSlug, $dto->merchantId, $dto->merchantItemId, $dto->part_number);

        // Favorites
        $dto->isInFavorites = $favoriteMerchantIds->contains($dto->merchantItemId);
        $dto->favoriteUrl = route('merchant.favorite.add', $dto->merchantItemId);

        // Merchant (from eager-loaded relation) - localized
        $dto->merchantName = $merchant->user ? getLocalizedShopName($merchant->user) : null;

        // Branch (from eager-loaded relation)
        $dto->branchId = $merchant->merchant_branch_id;
        $dto->branchName = $merchant->merchantBranch?->warehouse_name;

        // Vehicle Fitment Brands (from catalog_item_fitments)
        // A part can fit MULTIPLE brands - we store ALL of them
        self::setFitmentBrands($dto, $catalogItem);

        // Quality Brand (from eager-loaded relation)
        $dto->qualityBrandName = $merchant->qualityBrand?->localized_name;
        $dto->qualityBrandLogo = $merchant->qualityBrand?->logo_url;

        // Stock display
        self::setStockDisplay($dto);

        // Offers count (how many merchants/branches sell this item)
        self::setOffersCount($dto, $catalogItem);

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
    ): static {
        if ($merchant) {
            return static::fromMerchantItem($merchant, $favoriteCatalogItemIds, $favoriteMerchantIds);
        }

        $dto = new static();

        // CatalogItem data
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $catalogItem->showName();
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = self::resolvePhoto($catalogItem->photo);
        // item_type is now on merchant_items - no type without merchant
        $dto->itemType = '';
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
        $dto->hasMerchant = false;
        $dto->offPercentage = 0;
        $dto->detailsUrl = '#';

        // Favorites (catalog item level - no merchant, so no actions available)
        $dto->isInFavorites = $favoriteCatalogItemIds->contains($dto->catalogItemId);
        $dto->favoriteUrl = '#';

        // No merchant info without merchant
        $dto->merchantName = null;
        $dto->qualityBrandName = null;
        $dto->qualityBrandLogo = null;

        // Vehicle Fitment Brands (from catalog_item_fitments)
        self::setFitmentBrands($dto, $catalogItem);

        // Stock display
        self::setStockDisplay($dto);

        // Offers count
        self::setOffersCount($dto, $catalogItem);

        return $dto;
    }

    /**
     * Build DTO from CatalogItem with pre-computed offers data (NEW - CatalogItem-first)
     *
     * This is the CORRECT approach for catalog pages:
     * - One card per CatalogItem
     * - Shows lowest price from all offers
     * - Shows offers_count for "X offers" button
     * - Best merchant loaded for display (lowest price with stock)
     *
     * @param CatalogItem $catalogItem CatalogItem with offers_count and lowest_price attributes
     * @param MerchantItem|null $bestMerchant The best (lowest price) merchant item
     * @param Collection $favoriteCatalogItemIds
     * @param Collection $favoriteMerchantIds
     */
    public static function fromCatalogItemFirst(
        CatalogItem $catalogItem,
        ?MerchantItem $bestMerchant,
        Collection $favoriteCatalogItemIds,
        Collection $favoriteMerchantIds
    ): static {
        $dto = new static();

        // CatalogItem data (from catalog_items table)
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = $catalogItem->showName();
        $dto->catalogItemSlug = $catalogItem->slug ?? '';
        $dto->part_number = $catalogItem->part_number;
        $dto->photo = self::resolvePhoto($catalogItem->photo);
        $dto->catalogReviewsAvg = (float) ($catalogItem->catalog_reviews_avg_rating ?? 0);
        $dto->catalogReviewsCount = (int) ($catalogItem->catalog_reviews_count ?? 0);

        // Offers data (from subqueries)
        $dto->offersCount = (int) ($catalogItem->offers_count ?? 1);
        $dto->hasMultipleOffers = $dto->offersCount > 1;

        // Vehicle Fitment Brands (from catalog_item_fitments)
        self::setFitmentBrands($dto, $catalogItem);

        // If we have a best merchant, use its data for display
        if ($bestMerchant) {
            $dto->merchantItemId = $bestMerchant->id;
            $dto->merchantId = $bestMerchant->user_id;
            $dto->price = (float) $bestMerchant->price;
            $dto->priceFormatted = $bestMerchant->showPrice();
            $dto->previousPrice = (float) ($bestMerchant->previous_price ?? 0);
            $dto->previousPriceFormatted = $dto->previousPrice > 0
                ? CatalogItem::convertPrice($dto->previousPrice)
                : '';
            $dto->stock = (int) ($bestMerchant->stock ?? 0);
            $dto->preordered = (bool) $bestMerchant->preordered;
            $dto->minQty = max(1, (int) ($bestMerchant->minimum_qty ?? 1));

            // Computed values
            $dto->inStock = $dto->stock > 0 || $dto->preordered;
            $dto->hasMerchant = true;
            $dto->offPercentage = self::calculateOffPercentage($dto->previousPrice, $dto->price);
            $dto->detailsUrl = self::buildDetailsUrl($dto->catalogItemSlug, $dto->merchantId, $dto->merchantItemId, $dto->part_number);

            // Favorites (based on merchant item)
            $dto->isInFavorites = $favoriteMerchantIds->contains($dto->merchantItemId);
            $dto->favoriteUrl = route('merchant.favorite.add', $dto->merchantItemId);
    
            // Merchant info (from eager-loaded relation)
            $dto->merchantName = $bestMerchant->user ? getLocalizedShopName($bestMerchant->user) : null;

            // Branch
            $dto->branchId = $bestMerchant->merchant_branch_id;
            $dto->branchName = $bestMerchant->merchantBranch?->warehouse_name
                ?? $bestMerchant->merchantBranch?->branch_name;

            // Quality Brand
            $dto->qualityBrandName = $bestMerchant->qualityBrand?->localized_name;
            $dto->qualityBrandLogo = $bestMerchant->qualityBrand?->logo_url;

            // item_type and affiliate_link (from merchant_items)
            $dto->itemType = $bestMerchant->item_type ?? '';
            $dto->affiliateLink = $bestMerchant->affiliate_link;
        } else {
            // No merchant - use lowest_price from subquery if available
            $dto->merchantItemId = null;
            $dto->merchantId = null;
            $dto->price = (float) ($catalogItem->lowest_price ?? 0);
            $dto->priceFormatted = $dto->price > 0
                ? CatalogItem::convertPrice($dto->price)
                : __('No offers');
            $dto->previousPrice = 0;
            $dto->previousPriceFormatted = '';
            $dto->stock = 0;
            $dto->preordered = false;
            $dto->minQty = 1;

            // Computed
            $dto->inStock = false;
            $dto->hasMerchant = false;
            $dto->offPercentage = 0;
            $dto->detailsUrl = '#';

            // Favorites (catalog item level)
            $dto->isInFavorites = $favoriteCatalogItemIds->contains($dto->catalogItemId);
            $dto->favoriteUrl = '#';
    
            // No merchant info
            $dto->merchantName = null;
            $dto->branchId = null;
            $dto->branchName = null;
            $dto->qualityBrandName = null;
            $dto->qualityBrandLogo = null;
            $dto->itemType = '';
            $dto->affiliateLink = null;
        }

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

    /**
     * Build details URL for catalog item
     *
     * NEW: Uses part_number based URL (CatalogItem-first approach)
     */
    private static function buildDetailsUrl(string $slug, ?int $merchantId, ?int $merchantItemId, ?string $partNumber = null): string
    {
        // NEW: Use part_number based URL if available
        if ($partNumber) {
            return route('front.part-result', $partNumber);
        }

        // Fallback to slug-based URL (should not happen in normal flow)
        if ($slug) {
            return route('front.part-result', $slug);
        }

        return '#';
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

    /**
     * Set fitment brands from catalog_item_fitments
     * A part can fit MULTIPLE vehicle brands - we store ALL of them
     */
    private static function setFitmentBrands(self $dto, CatalogItem $catalogItem): void
    {
        $fitments = $catalogItem->fitments;

        if (!$fitments || $fitments->isEmpty()) {
            $dto->fitmentBrands = [];
            $dto->fitmentCount = 0;
            $dto->hasSingleBrand = false;
            $dto->fitsMultipleBrands = false;
            return;
        }

        // Get unique brands from fitments
        $uniqueBrands = $fitments
            ->map(fn($f) => $f->brand)
            ->filter()
            ->unique('id')
            ->values();

        $dto->fitmentBrands = $uniqueBrands->map(fn($brand) => [
            'id' => $brand->id,
            'name' => $brand->localized_name,
            'logo' => $brand->photo_url,
            'slug' => $brand->slug,
        ])->toArray();

        $dto->fitmentCount = count($dto->fitmentBrands);
        $dto->hasSingleBrand = $dto->fitmentCount === 1;
        $dto->fitsMultipleBrands = $dto->fitmentCount > 1;
    }

    /**
     * Set offers count from catalog item's merchant items
     * Counts active merchant items with status = 1 and active merchants
     */
    private static function setOffersCount(self $dto, CatalogItem $catalogItem): void
    {
        // If merchantItems relation is loaded, count from it
        if ($catalogItem->relationLoaded('merchantItems')) {
            $dto->offersCount = $catalogItem->merchantItems
                ->filter(fn($mi) => $mi->status == 1)
                ->count();
        } else {
            // Count active merchant items (single query)
            $dto->offersCount = MerchantItem::where('catalog_item_id', $catalogItem->id)
                ->where('status', 1)
                ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
                ->count();
        }

        $dto->hasMultipleOffers = $dto->offersCount > 1;
    }
}
