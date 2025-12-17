<?php

namespace App\DataTransferObjects;

use App\Models\MerchantProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * ProductCardDTO
 *
 * Pre-computed data for product card display.
 * Blade views should ONLY read properties - no logic, no queries.
 */
class ProductCardDTO
{
    // Product
    public int $productId;
    public string $productName;
    public string $productSlug;
    public ?string $sku;
    public string $photo;
    public string $type;
    public string $productType;
    public ?string $affiliateLink;
    public float $ratingsAvg;
    public int $ratingsCount;

    // Merchant
    public ?int $merchantId;
    public ?int $vendorId;
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
    public bool $isInWishlist;
    public string $wishlistUrl;
    public string $compareUrl;

    // Vendor
    public ?string $vendorName;

    // Brand
    public ?string $brandName;

    // Quality Brand
    public ?string $qualityBrandName;
    public ?string $qualityBrandLogo;

    // Stock display
    public string $stockText;
    public string $stockClass;
    public string $stockBadgeClass;

    /**
     * Build DTO from MerchantProduct with all pre-loaded relations
     */
    public static function fromMerchantProduct(
        MerchantProduct $merchant,
        Collection $wishlistProductIds,
        Collection $wishlistMerchantIds
    ): self {
        $dto = new self();
        $product = $merchant->product;

        // Product data
        $dto->productId = $product->id;
        $dto->productName = $product->showName();
        $dto->productSlug = $product->slug ?? '';
        $dto->sku = $product->sku;
        $dto->photo = self::resolvePhoto($product->photo);
        $dto->type = $product->type ?? 'Physical';
        $dto->productType = $product->product_type ?? '';
        $dto->affiliateLink = $product->affiliate_link;
        $dto->ratingsAvg = (float) ($product->ratings_avg_rating ?? 0);
        $dto->ratingsCount = (int) ($product->ratings_count ?? 0);

        // Merchant data
        $dto->merchantId = $merchant->id;
        $dto->vendorId = $merchant->user_id;
        $dto->price = (float) $merchant->price;
        $dto->priceFormatted = $merchant->showPrice();
        $dto->previousPrice = (float) ($merchant->previous_price ?? 0);
        $dto->previousPriceFormatted = $dto->previousPrice > 0
            ? \App\Models\Product::convertPrice($dto->previousPrice)
            : '';
        $dto->stock = (int) ($merchant->stock ?? 0);
        $dto->preordered = (bool) $merchant->preordered;
        $dto->minQty = max(1, (int) ($merchant->minimum_qty ?? 1));

        // Computed values
        $dto->inStock = $dto->stock > 0 || $dto->preordered;
        $dto->hasVendor = $dto->vendorId > 0;
        $dto->offPercentage = self::calculateOffPercentage($dto->previousPrice, $dto->price);
        $dto->detailsUrl = self::buildDetailsUrl($dto->productSlug, $dto->vendorId, $dto->merchantId);

        // Wishlist
        $dto->isInWishlist = $wishlistMerchantIds->contains($dto->merchantId);
        $dto->wishlistUrl = route('merchant.wishlist.add', $dto->merchantId);
        $dto->compareUrl = route('merchant.compare.add', $dto->merchantId);

        // Vendor (from eager-loaded relation)
        $dto->vendorName = $merchant->user?->shop_name;

        // Brand (from eager-loaded relation)
        $dto->brandName = $product->brand?->localized_name;

        // Quality Brand (from eager-loaded relation)
        $dto->qualityBrandName = $merchant->qualityBrand?->localized_name;
        $dto->qualityBrandLogo = $merchant->qualityBrand?->logo_url;

        // Stock display
        self::setStockDisplay($dto);

        return $dto;
    }

    /**
     * Build DTO from Product model (legacy support)
     */
    public static function fromProduct(
        Product $product,
        ?MerchantProduct $merchant,
        Collection $wishlistProductIds,
        Collection $wishlistMerchantIds
    ): self {
        if ($merchant) {
            return self::fromMerchantProduct($merchant, $wishlistProductIds, $wishlistMerchantIds);
        }

        $dto = new self();

        // Product data
        $dto->productId = $product->id;
        $dto->productName = $product->showName();
        $dto->productSlug = $product->slug ?? '';
        $dto->sku = $product->sku;
        $dto->photo = self::resolvePhoto($product->photo);
        $dto->type = $product->type ?? 'Physical';
        $dto->productType = $product->product_type ?? '';
        $dto->affiliateLink = $product->affiliate_link;
        $dto->ratingsAvg = (float) ($product->ratings_avg_rating ?? 0);
        $dto->ratingsCount = (int) ($product->ratings_count ?? 0);

        // No merchant
        $dto->merchantId = null;
        $dto->vendorId = null;
        $dto->price = 0;
        $dto->priceFormatted = $product->showPrice();
        $dto->previousPrice = 0;
        $dto->previousPriceFormatted = '';
        $dto->stock = 0;
        $dto->preordered = false;
        $dto->minQty = 1;

        // Computed
        $dto->inStock = false;
        $dto->hasVendor = false;
        $dto->offPercentage = 0;
        $dto->detailsUrl = route('front.product.legacy', $dto->productSlug);

        // Wishlist (product-level)
        $dto->isInWishlist = $wishlistProductIds->contains($dto->productId);
        $dto->wishlistUrl = route('user-wishlist-add', $dto->productId);
        $dto->compareUrl = route('product.compare.add', $dto->productId);

        // No vendor/brand info without merchant
        $dto->vendorName = null;
        $dto->brandName = $product->brand?->localized_name;
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

    private static function buildDetailsUrl(string $slug, ?int $vendorId, ?int $merchantId): string
    {
        if (!$slug) {
            return '#';
        }

        if ($vendorId && $merchantId) {
            return route('front.product', [
                'slug' => $slug,
                'vendor_id' => $vendorId,
                'merchant_product_id' => $merchantId
            ]);
        }

        return route('front.product.legacy', $slug);
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
