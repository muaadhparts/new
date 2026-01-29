<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\CatalogItemDisplayService;
use App\Domain\Commerce\Services\PriceFormatterService;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\Storage;

/**
 * QuickViewDTO
 *
 * Pre-computed data for catalog item quick view modal.
 * Moves data normalization from blade to DTO.
 */
class QuickViewDTO
{
    public int $catalogItemId;
    public ?string $catalogItemName;
    public ?string $partNumber;
    public ?string $catalogItemUrl;  // Pre-computed URL for catalog item
    public string $mainPhoto;
    public int $merchantUserId;

    // Price data
    public string $priceHtml;
    public ?string $prevPriceHtml;

    // Merchant Item data
    public ?int $merchantItemId;
    public int $minQty;
    public int $stock;
    public bool $inStock;
    public bool $preordered;
    public bool $canBuy;

    // Quality Brand
    public ?object $qualityBrand;

    // Merchant
    public ?object $merchant;

    // Branch
    public ?object $branch;

    // Ratings
    public ?float $avgRating;
    public int $roundedRating;  // For star display (1-5)
    public ?string $formattedRating;
    public ?int $reviewCount;

    // Fitment brands
    public array $fitmentBrands;
    public int $fitmentCount;

    // Merchant galleries (pre-computed)
    public array $merchantGalleries;

    // Original models (for methods not covered by DTO)
    public CatalogItem $catalogItem;
    public ?MerchantItem $merchantItem;

    /**
     * Build DTO from CatalogItem and optional MerchantItem
     */
    public static function fromModels(
        CatalogItem $catalogItem,
        ?MerchantItem $merchantItem = null,
        ?int $forceMerchantId = null
    ): static {
        $dto = new static();

        // Original models
        $dto->catalogItem = $catalogItem;
        $dto->merchantItem = $merchantItem;

        // Basic info
        $dto->catalogItemId = $catalogItem->id;
        $dto->catalogItemName = getLocalizedCatalogItemName($catalogItem);
        $dto->partNumber = $catalogItem->part_number;
        $dto->catalogItemUrl = !empty($catalogItem->part_number)
            ? route('front.part-result', $catalogItem->part_number)
            : null;

        // Merchant user ID (priority: forced > merchantItem > 0)
        $dto->merchantUserId = $forceMerchantId
            ?? ($merchantItem?->user_id ?? 0);

        // Main photo
        $photo = $catalogItem->photo ?? '';
        $dto->mainPhoto = filter_var($photo, FILTER_VALIDATE_URL)
            ? $photo
            : ($photo ? Storage::url($photo) : asset('assets/images/noimage.png'));

        // Price calculation
        $rawPrice = $merchantItem?->price ?? null;
        $rawPrev = $merchantItem?->previous_price ?? null;

        $priceFormatter = app(PriceFormatterService::class);
        if ($forceMerchantId || $merchantItem) {
            $dto->priceHtml = $rawPrice !== null
                ? $priceFormatter->format($rawPrice)
                : '-';
            $dto->prevPriceHtml = $rawPrev !== null && $rawPrev > 0
                ? $priceFormatter->format($rawPrev)
                : null;
        } else {
            $displayService = app(CatalogItemDisplayService::class);
            $dto->priceHtml = $displayService->formatPrice($catalogItem, $rawPrice ?? 0);
            $dto->prevPriceHtml = $rawPrev !== null && $rawPrev > 0 
                ? $priceFormatter->format($rawPrev) 
                : null;
        }

        // MerchantItem data
        $dto->merchantItemId = $merchantItem?->id;
        $dto->minQty = max(1, (int) ($merchantItem?->minimum_qty ?? 1));
        $dto->stock = (int) ($merchantItem?->stock ?? 999);
        $dto->inStock = $dto->stock > 0;
        $dto->preordered = (bool) ($merchantItem?->preordered ?? false);
        $dto->canBuy = $dto->inStock || $dto->preordered;

        // Relations
        $dto->qualityBrand = $merchantItem?->qualityBrand;
        $dto->merchant = $merchantItem?->user;
        $dto->branch = $merchantItem?->merchantBranch;

        // Ratings
        $dto->avgRating = $catalogItem->catalog_reviews_avg_rating ?? null;
        $dto->roundedRating = $dto->avgRating !== null ? (int) round($dto->avgRating) : 0;
        $dto->formattedRating = $dto->avgRating !== null ? number_format($dto->avgRating, 1) : null;
        $dto->reviewCount = null; // Can be loaded if needed

        // Fitment brands
        $catalogItemForFitment = $merchantItem?->catalogItem ?? $catalogItem;
        $fitments = $catalogItemForFitment->fitments ?? collect();
        $uniqueBrands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();

        $dto->fitmentBrands = $uniqueBrands->toArray();
        $dto->fitmentCount = $uniqueBrands->count();

        // Pre-compute merchant galleries
        $dto->merchantGalleries = $catalogItem->merchantPhotosForMerchant($dto->merchantUserId, 4)->toArray();

        return $dto;
    }
}
