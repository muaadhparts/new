<?php

namespace App\Domain\Catalog\DTOs;

/**
 * CatalogItemCardDTO - Pure Data Transfer Object
 *
 * Pre-computed data for catalog item card display.
 * Blade views should ONLY read properties - no logic, no queries.
 *
 * ARCHITECTURE:
 * - Pure Data Object (no methods, no logic)
 * - Built by CatalogItemCardDTOBuilder
 * - Immutable after construction
 *
 * @see \App\Domain\Catalog\Builders\CatalogItemCardDTOBuilder
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
    public ?string $offPercentageFormatted;
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
}
