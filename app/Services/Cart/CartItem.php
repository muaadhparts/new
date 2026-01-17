<?php

namespace App\Services\Cart;

use App\Models\MerchantItem;
use App\Models\CatalogItem;
use JsonSerializable;

/**
 * CartItem - Data Transfer Object for a single cart item
 *
 * This is an immutable value object representing a cart item.
 * All cart operations should work with this DTO.
 */
class CartItem implements JsonSerializable
{
    // Identifiers
    public readonly string $key;
    public readonly int $merchantItemId;
    public readonly int $merchantId;
    public readonly int $catalogItemId;
    public readonly ?int $brandQualityId;

    // Product snapshot (frozen at add time)
    public readonly string $name;
    public readonly string $nameAr;
    public readonly string $photo;
    public readonly string $slug;
    public readonly string $partNumber;

    // Merchant info
    public readonly string $merchantName;
    public readonly string $merchantNameAr;

    // Pricing (base currency - SAR)
    public readonly float $unitPrice;
    public readonly float $sizePrice;
    public readonly float $colorPrice;
    public readonly float $previousPrice;

    // Quantity & Stock
    public int $qty;
    public readonly int $minQty;
    public readonly int $stock;
    public readonly bool $preordered;

    // Variants
    public readonly ?string $size;
    public readonly ?string $color;
    public readonly ?string $keys;
    public readonly ?string $values;

    // Wholesale discount
    public readonly array $wholeSellQty;
    public readonly array $wholeSellDiscount;

    // Timestamps
    public readonly string $addedAt;

    /**
     * Create a new CartItem from MerchantItem
     */
    public static function fromMerchantItem(
        MerchantItem $merchantItem,
        int $qty = 1,
        ?string $size = null,
        ?string $color = null,
        ?string $keys = null,
        ?string $values = null
    ): self {
        $catalogItem = $merchantItem->catalogItem;

        // Calculate size price
        $sizePrice = self::calculateSizePrice($merchantItem, $size);

        // Calculate color price
        $colorPrice = self::calculateColorPrice($merchantItem, $color);

        // Get unit price (base + commission)
        $unitPrice = $merchantItem->merchantSizePrice();

        return new self(
            merchantItemId: $merchantItem->id,
            merchantId: $merchantItem->user_id,
            catalogItemId: $merchantItem->catalog_item_id,
            brandQualityId: $merchantItem->brand_quality_id,
            name: $catalogItem->name ?? '',
            nameAr: $catalogItem->label_ar ?? $catalogItem->name ?? '',
            photo: $catalogItem->photo ?? '',
            slug: $catalogItem->slug ?? '',
            partNumber: $catalogItem->part_number ?? '',
            merchantName: getLocalizedShopName($merchantItem->user),
            merchantNameAr: $merchantItem->user->shop_name_ar ?? '',
            unitPrice: $unitPrice,
            sizePrice: $sizePrice,
            colorPrice: $colorPrice,
            previousPrice: (float) ($merchantItem->previous_price ?? 0),
            qty: $qty,
            minQty: max(1, (int) ($merchantItem->minimum_qty ?? 1)),
            stock: self::getEffectiveStock($merchantItem, $size),
            preordered: (bool) ($merchantItem->preordered ?? false),
            size: $size,
            color: $color ? ltrim($color, '#') : null,
            keys: $keys,
            values: $values,
            wholeSellQty: self::toArray($merchantItem->whole_sell_qty),
            wholeSellDiscount: self::toArray($merchantItem->whole_sell_discount),
            addedAt: now()->toDateTimeString()
        );
    }

    /**
     * Create CartItem from array (session restore)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            merchantItemId: (int) $data['merchant_item_id'],
            merchantId: (int) $data['merchant_id'],
            catalogItemId: (int) $data['catalog_item_id'],
            brandQualityId: isset($data['brand_quality_id']) ? (int) $data['brand_quality_id'] : null,
            name: $data['name'] ?? '',
            nameAr: $data['name_ar'] ?? '',
            photo: $data['photo'] ?? '',
            slug: $data['slug'] ?? '',
            partNumber: $data['part_number'] ?? '',
            merchantName: $data['merchant_name'] ?? '',
            merchantNameAr: $data['merchant_name_ar'] ?? '',
            unitPrice: (float) ($data['unit_price'] ?? 0),
            sizePrice: (float) ($data['size_price'] ?? 0),
            colorPrice: (float) ($data['color_price'] ?? 0),
            previousPrice: (float) ($data['previous_price'] ?? 0),
            qty: (int) ($data['qty'] ?? 1),
            minQty: (int) ($data['min_qty'] ?? 1),
            stock: (int) ($data['stock'] ?? 0),
            preordered: (bool) ($data['preordered'] ?? false),
            size: $data['size'] ?? null,
            color: $data['color'] ?? null,
            keys: $data['keys'] ?? null,
            values: $data['values'] ?? null,
            wholeSellQty: $data['whole_sell_qty'] ?? [],
            wholeSellDiscount: $data['whole_sell_discount'] ?? [],
            addedAt: $data['added_at'] ?? now()->toDateTimeString()
        );
    }

    public function __construct(
        int $merchantItemId,
        int $merchantId,
        int $catalogItemId,
        ?int $brandQualityId,
        string $name,
        string $nameAr,
        string $photo,
        string $slug,
        string $partNumber,
        string $merchantName,
        string $merchantNameAr,
        float $unitPrice,
        float $sizePrice,
        float $colorPrice,
        float $previousPrice,
        int $qty,
        int $minQty,
        int $stock,
        bool $preordered,
        ?string $size,
        ?string $color,
        ?string $keys,
        ?string $values,
        array $wholeSellQty,
        array $wholeSellDiscount,
        string $addedAt
    ) {
        $this->merchantItemId = $merchantItemId;
        $this->merchantId = $merchantId;
        $this->catalogItemId = $catalogItemId;
        $this->brandQualityId = $brandQualityId;
        $this->name = $name;
        $this->nameAr = $nameAr;
        $this->photo = $photo;
        $this->slug = $slug;
        $this->partNumber = $partNumber;
        $this->merchantName = $merchantName;
        $this->merchantNameAr = $merchantNameAr;
        $this->unitPrice = $unitPrice;
        $this->sizePrice = $sizePrice;
        $this->colorPrice = $colorPrice;
        $this->previousPrice = $previousPrice;
        $this->qty = $qty;
        $this->minQty = $minQty;
        $this->stock = $stock;
        $this->preordered = $preordered;
        $this->size = $size;
        $this->color = $color;
        $this->keys = $keys;
        $this->values = $values;
        $this->wholeSellQty = $wholeSellQty;
        $this->wholeSellDiscount = $wholeSellDiscount;
        $this->addedAt = $addedAt;

        // Generate key
        $this->key = self::generateKey($merchantItemId, $size, $color);
    }

    /**
     * Generate unique cart key (SINGLE SOURCE OF TRUTH)
     *
     * Format: s{session_hash}_m{merchant_item_id}_{size}_{color}
     *
     * Components:
     * - s{hash}: First 8 chars of session ID hash (ties to session)
     * - m{id}: Merchant item ID
     * - size: Size value or underscore
     * - color: Color hex or underscore
     *
     * Example: s8a3f2b1c_m1234_M_FF0000
     *
     * This is the ONLY method that generates cart keys.
     * No fallbacks. No alternative formats.
     */
    public static function generateKey(int $merchantItemId, ?string $size = null, ?string $color = null): string
    {
        $sessionHash = self::getSessionHash();
        $sizeKey = $size ? preg_replace('/[^a-zA-Z0-9]/', '', $size) : '_';
        $colorKey = $color ? ltrim($color, '#') : '_';

        return "s{$sessionHash}_m{$merchantItemId}_{$sizeKey}_{$colorKey}";
    }

    /**
     * Get session hash for cart key
     * Uses first 8 characters of MD5 hash of session ID
     *
     * @throws \RuntimeException if no active session
     */
    private static function getSessionHash(): string
    {
        $sessionId = session()->getId();

        if (empty($sessionId)) {
            throw new \RuntimeException(
                'Cart operations require an active session. ' .
                'Ensure web middleware is applied or session is started.'
            );
        }

        return substr(md5($sessionId), 0, 8);
    }

    /**
     * Validate that a key belongs to current session
     */
    public static function isValidKeyForSession(string $key): bool
    {
        $sessionHash = self::getSessionHash();
        return str_starts_with($key, "s{$sessionHash}_");
    }

    /**
     * Extract merchant_item_id from cart key
     */
    public static function getMerchantItemIdFromKey(string $key): ?int
    {
        // Format: s{hash}_m{id}_{size}_{color}
        if (preg_match('/^s[a-f0-9]{8}_m(\d+)_/', $key, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Get effective unit price (base + size + color)
     */
    public function getEffectiveUnitPrice(): float
    {
        return $this->unitPrice + $this->sizePrice + $this->colorPrice;
    }

    /**
     * Calculate wholesale discount percentage for current qty
     */
    public function getDiscountPercent(): float
    {
        if (empty($this->wholeSellQty) || empty($this->wholeSellDiscount)) {
            return 0.0;
        }

        $discount = 0.0;
        foreach ($this->wholeSellQty as $i => $threshold) {
            if ($this->qty >= (int) $threshold && isset($this->wholeSellDiscount[$i])) {
                $discount = (float) $this->wholeSellDiscount[$i];
            }
        }

        return $discount;
    }

    /**
     * Get discounted unit price
     */
    public function getDiscountedUnitPrice(): float
    {
        $effectivePrice = $this->getEffectiveUnitPrice();
        $discountPercent = $this->getDiscountPercent();

        if ($discountPercent > 0) {
            return $effectivePrice * (1 - $discountPercent / 100);
        }

        return $effectivePrice;
    }

    /**
     * Get total price for this item (qty * discounted unit price)
     */
    public function getTotalPrice(): float
    {
        return $this->getDiscountedUnitPrice() * $this->qty;
    }

    /**
     * Get localized name based on current locale
     */
    public function getLocalizedName(): string
    {
        $isAr = app()->getLocale() === 'ar';
        return $isAr && !empty($this->nameAr) ? $this->nameAr : $this->name;
    }

    /**
     * Check if can increase quantity
     */
    public function canIncrease(int $by = 1): bool
    {
        if ($this->preordered) {
            return true;
        }

        return $this->stock <= 0 || ($this->qty + $by) <= $this->stock;
    }

    /**
     * Check if can decrease quantity
     */
    public function canDecrease(int $by = 1): bool
    {
        return ($this->qty - $by) >= $this->minQty;
    }

    /**
     * Update quantity (returns new instance)
     */
    public function withQty(int $qty): self
    {
        $clone = clone $this;
        $clone->qty = max($this->minQty, $qty);
        return $clone;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'merchant_item_id' => $this->merchantItemId,
            'merchant_id' => $this->merchantId,
            'catalog_item_id' => $this->catalogItemId,
            'brand_quality_id' => $this->brandQualityId,
            'name' => $this->name,
            'name_ar' => $this->nameAr,
            'photo' => $this->photo,
            'slug' => $this->slug,
            'part_number' => $this->partNumber,
            'merchant_name' => $this->merchantName,
            'merchant_name_ar' => $this->merchantNameAr,
            'unit_price' => $this->unitPrice,
            'size_price' => $this->sizePrice,
            'color_price' => $this->colorPrice,
            'previous_price' => $this->previousPrice,
            'qty' => $this->qty,
            'min_qty' => $this->minQty,
            'stock' => $this->stock,
            'preordered' => $this->preordered,
            'size' => $this->size,
            'color' => $this->color,
            'keys' => $this->keys,
            'values' => $this->values,
            'whole_sell_qty' => $this->wholeSellQty,
            'whole_sell_discount' => $this->wholeSellDiscount,
            'added_at' => $this->addedAt,
            // Computed values for convenience
            'effective_unit_price' => $this->getEffectiveUnitPrice(),
            'discount_percent' => $this->getDiscountPercent(),
            'discounted_unit_price' => $this->getDiscountedUnitPrice(),
            'total_price' => $this->getTotalPrice(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // ===================== Static Calculation Methods =====================

    /**
     * Calculate totals from an array of cart item arrays
     *
     * SINGLE SOURCE OF TRUTH for totals calculation.
     * Uses CartItem instances to ensure consistent pricing logic.
     *
     * @param array<string, array> $items Array of item arrays (from storage)
     * @return array{qty: int, subtotal: float, discount: float, total: float}
     */
    public static function calculateTotals(array $items): array
    {
        $totals = [
            'qty' => 0,
            'subtotal' => 0.0,
            'discount' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as $itemArray) {
            // Reconstruct CartItem to use its calculation methods
            $cartItem = self::fromArray($itemArray);

            $totals['qty'] += $cartItem->qty;
            $totals['subtotal'] += $cartItem->getEffectiveUnitPrice() * $cartItem->qty;
            $totals['total'] += $cartItem->getTotalPrice();
        }

        // Discount is the difference between subtotal and total
        $totals['discount'] = $totals['subtotal'] - $totals['total'];

        return $totals;
    }

    /**
     * Calculate totals for items belonging to a specific merchant
     *
     * @param array<string, array> $items All cart items
     * @param int $merchantId Merchant to filter by
     * @return array{qty: int, subtotal: float, discount: float, total: float}
     */
    public static function calculateMerchantTotals(array $items, int $merchantId): array
    {
        $merchantItems = array_filter($items, function ($item) use ($merchantId) {
            return (int) ($item['merchant_id'] ?? 0) === $merchantId;
        });

        return self::calculateTotals($merchantItems);
    }

    // ===================== Helper Methods =====================

    private static function toArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') return array_map('trim', explode(',', $value));
        return [];
    }

    private static function calculateSizePrice(MerchantItem $mp, ?string $size): float
    {
        if (!$size || empty($mp->size) || empty($mp->size_price)) {
            return 0.0;
        }

        $sizes = self::toArray($mp->size);
        $prices = self::toArray($mp->size_price);
        $idx = array_search(trim($size), array_map('trim', $sizes), true);

        if ($idx !== false && isset($prices[$idx])) {
            return (float) $prices[$idx];
        }

        return 0.0;
    }

    private static function calculateColorPrice(MerchantItem $mp, ?string $color): float
    {
        if (!$color || empty($mp->color_all) || empty($mp->color_price)) {
            return 0.0;
        }

        $colors = self::toArray($mp->color_all);
        $prices = self::toArray($mp->color_price);
        $color = ltrim($color, '#');

        foreach ($colors as $i => $c) {
            if (ltrim($c, '#') === $color && isset($prices[$i])) {
                return (float) $prices[$i];
            }
        }

        return 0.0;
    }

    public static function getEffectiveStock(MerchantItem $mp, ?string $size = null): int
    {
        if ($size && !empty($mp->size) && !empty($mp->size_qty)) {
            $sizes = self::toArray($mp->size);
            $qtys = self::toArray($mp->size_qty);
            $idx = array_search(trim($size), array_map('trim', $sizes), true);

            if ($idx !== false && isset($qtys[$idx])) {
                return (int) $qtys[$idx];
            }
        }

        return (int) ($mp->stock ?? 0);
    }
}
