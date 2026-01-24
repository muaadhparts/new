<?php

namespace App\Domain\Commerce\DTOs;

/**
 * CartItemDTO - Typed representation of a cart item
 *
 * Ensures type safety for cart items stored in session.
 * All cart item data should be validated through this DTO.
 */
class CartItemDTO
{
    // Identifiers
    public string $key;
    public int $merchantItemId;
    public int $merchantId;
    public int $branchId;
    public string $branchName;
    public int $catalogItemId;

    // Product snapshot
    public string $name;
    public string $nameAr;
    public string $photo;
    public string $slug;
    public string $partNumber;

    // Brand (OEM brand - from fitments)
    public ?int $brandId;
    public string $brandName;
    public string $brandNameAr;
    public string $brandLogo;

    // All fitment brands
    public array $fitmentBrands = [];
    public int $fitmentCount = 0;

    // Quality Brand
    public ?int $qualityBrandId;
    public string $qualityBrandName;
    public string $qualityBrandNameAr;
    public string $qualityBrandLogo;

    // Merchant info
    public string $merchantName;
    public string $merchantNameAr;

    // Pricing
    public float $unitPrice;
    public float $effectivePrice;
    public float $totalPrice;

    // Quantity
    public int $qty;
    public int $minQty;
    public int $stock;
    public bool $preordered;

    // Wholesale
    public array $wholeSellQty = [];
    public array $wholeSellDiscount = [];

    // Shipping
    public float $weight;

    // Timestamp
    public string $addedAt;

    /**
     * Create DTO from array (session data)
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        // Identifiers
        $dto->key = $data['key'] ?? '';
        $dto->merchantItemId = (int) ($data['merchant_item_id'] ?? 0);
        $dto->merchantId = (int) ($data['merchant_id'] ?? 0);
        $dto->branchId = (int) ($data['branch_id'] ?? 0);
        $dto->branchName = $data['branch_name'] ?? '';
        $dto->catalogItemId = (int) ($data['catalog_item_id'] ?? 0);

        // Product snapshot
        $dto->name = $data['name'] ?? '';
        $dto->nameAr = $data['name_ar'] ?? $dto->name;
        $dto->photo = $data['photo'] ?? '';
        $dto->slug = $data['slug'] ?? '';
        $dto->partNumber = $data['part_number'] ?? '';

        // Brand
        $dto->brandId = isset($data['brand_id']) ? (int) $data['brand_id'] : null;
        $dto->brandName = $data['brand_name'] ?? '';
        $dto->brandNameAr = $data['brand_name_ar'] ?? '';
        $dto->brandLogo = $data['brand_logo'] ?? '';

        // Fitment brands
        $dto->fitmentBrands = $data['fitment_brands'] ?? [];
        $dto->fitmentCount = (int) ($data['fitment_count'] ?? 0);

        // Quality Brand
        $dto->qualityBrandId = isset($data['quality_brand_id']) ? (int) $data['quality_brand_id'] : null;
        $dto->qualityBrandName = $data['quality_brand_name'] ?? '';
        $dto->qualityBrandNameAr = $data['quality_brand_name_ar'] ?? '';
        $dto->qualityBrandLogo = $data['quality_brand_logo'] ?? '';

        // Merchant info
        $dto->merchantName = $data['merchant_name'] ?? '';
        $dto->merchantNameAr = $data['merchant_name_ar'] ?? '';

        // Pricing
        $dto->unitPrice = (float) ($data['unit_price'] ?? 0);
        $dto->effectivePrice = (float) ($data['effective_price'] ?? 0);
        $dto->totalPrice = (float) ($data['total_price'] ?? 0);

        // Quantity
        $dto->qty = (int) ($data['qty'] ?? 1);
        $dto->minQty = max(1, (int) ($data['min_qty'] ?? 1));
        $dto->stock = (int) ($data['stock'] ?? 0);
        $dto->preordered = (bool) ($data['preordered'] ?? false);

        // Wholesale
        $dto->wholeSellQty = $data['whole_sell_qty'] ?? [];
        $dto->wholeSellDiscount = $data['whole_sell_discount'] ?? [];

        // Shipping
        $dto->weight = (float) ($data['weight'] ?? 0);

        // Timestamp
        $dto->addedAt = $data['added_at'] ?? now()->toDateTimeString();

        return $dto;
    }

    /**
     * Convert DTO to array (for session storage)
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'merchant_item_id' => $this->merchantItemId,
            'merchant_id' => $this->merchantId,
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'catalog_item_id' => $this->catalogItemId,

            'name' => $this->name,
            'name_ar' => $this->nameAr,
            'photo' => $this->photo,
            'slug' => $this->slug,
            'part_number' => $this->partNumber,

            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
            'brand_name_ar' => $this->brandNameAr,
            'brand_logo' => $this->brandLogo,

            'fitment_brands' => $this->fitmentBrands,
            'fitment_count' => $this->fitmentCount,

            'quality_brand_id' => $this->qualityBrandId,
            'quality_brand_name' => $this->qualityBrandName,
            'quality_brand_name_ar' => $this->qualityBrandNameAr,
            'quality_brand_logo' => $this->qualityBrandLogo,

            'merchant_name' => $this->merchantName,
            'merchant_name_ar' => $this->merchantNameAr,

            'unit_price' => $this->unitPrice,
            'effective_price' => $this->effectivePrice,
            'total_price' => $this->totalPrice,

            'qty' => $this->qty,
            'min_qty' => $this->minQty,
            'stock' => $this->stock,
            'preordered' => $this->preordered,

            'whole_sell_qty' => $this->wholeSellQty,
            'whole_sell_discount' => $this->wholeSellDiscount,

            'weight' => $this->weight,

            'added_at' => $this->addedAt,
        ];
    }

    /**
     * Get localized name based on current locale
     */
    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' && $this->nameAr
            ? $this->nameAr
            : $this->name;
    }

    /**
     * Get localized brand name
     */
    public function getLocalizedBrandName(): string
    {
        return app()->getLocale() === 'ar' && $this->brandNameAr
            ? $this->brandNameAr
            : $this->brandName;
    }

    /**
     * Get localized quality brand name
     */
    public function getLocalizedQualityBrandName(): string
    {
        return app()->getLocale() === 'ar' && $this->qualityBrandNameAr
            ? $this->qualityBrandNameAr
            : $this->qualityBrandName;
    }

    /**
     * Check if item is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0 || $this->preordered;
    }

    /**
     * Check if item has wholesale pricing
     */
    public function hasWholesale(): bool
    {
        return !empty($this->wholeSellQty) && !empty($this->wholeSellDiscount);
    }
}
