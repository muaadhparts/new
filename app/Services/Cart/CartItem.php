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
     *
     * FAIL-FAST: All required data must exist. No fallbacks.
     *
     * @throws \InvalidArgumentException if required data is missing
     */
    public static function fromMerchantItem(
        MerchantItem $merchantItem,
        int $qty = 1,
        ?string $size = null,
        ?string $color = null,
        ?string $keys = null,
        ?string $values = null
    ): self {
        // ============ VALIDATE MERCHANT ITEM ============
        if (!$merchantItem->id || $merchantItem->id <= 0) {
            throw new \InvalidArgumentException(
                "MerchantItem has invalid id: {$merchantItem->id}"
            );
        }

        if (!$merchantItem->user_id || $merchantItem->user_id <= 0) {
            throw new \InvalidArgumentException(
                "MerchantItem {$merchantItem->id} has invalid user_id (merchant_id): {$merchantItem->user_id}"
            );
        }

        if (!$merchantItem->catalog_item_id || $merchantItem->catalog_item_id <= 0) {
            throw new \InvalidArgumentException(
                "MerchantItem {$merchantItem->id} has invalid catalog_item_id: {$merchantItem->catalog_item_id}"
            );
        }

        // ============ VALIDATE CATALOG ITEM ============
        $catalogItem = $merchantItem->catalogItem;
        if (!$catalogItem) {
            throw new \InvalidArgumentException(
                "MerchantItem {$merchantItem->id} has no associated CatalogItem. " .
                "catalog_item_id={$merchantItem->catalog_item_id}"
            );
        }

        if (empty(trim($catalogItem->name ?? ''))) {
            throw new \InvalidArgumentException(
                "CatalogItem {$catalogItem->id} has empty name. " .
                "merchant_item_id={$merchantItem->id}"
            );
        }

        // ============ VALIDATE MERCHANT ============
        $merchant = $merchantItem->user;
        if (!$merchant) {
            throw new \InvalidArgumentException(
                "MerchantItem {$merchantItem->id} has no associated Merchant (User). " .
                "user_id={$merchantItem->user_id}"
            );
        }

        // ============ VALIDATE PRICE ============
        $unitPrice = $merchantItem->merchantSizePrice();
        if ($unitPrice <= 0) {
            throw new \InvalidArgumentException(
                "MerchantItem {$merchantItem->id} has invalid price: {$unitPrice}. " .
                "Price must be > 0."
            );
        }

        // ============ VALIDATE QTY ============
        if ($qty <= 0) {
            throw new \InvalidArgumentException(
                "Invalid qty: {$qty}. Must be > 0. merchant_item_id={$merchantItem->id}"
            );
        }

        // Calculate size price
        $sizePrice = self::calculateSizePrice($merchantItem, $size);

        // Calculate color price
        $colorPrice = self::calculateColorPrice($merchantItem, $color);

        return new self(
            merchantItemId: $merchantItem->id,
            merchantId: $merchantItem->user_id,
            catalogItemId: $merchantItem->catalog_item_id,
            brandQualityId: $merchantItem->brand_quality_id ?: null,
            name: $catalogItem->name,
            nameAr: $catalogItem->label_ar ?: $catalogItem->name,
            photo: $catalogItem->photo ?: '',
            slug: $catalogItem->slug ?: '',
            partNumber: $catalogItem->part_number ?: '',
            merchantName: getLocalizedShopName($merchant),
            merchantNameAr: $merchant->shop_name_ar ?: '',
            unitPrice: $unitPrice,
            sizePrice: $sizePrice,
            colorPrice: $colorPrice,
            previousPrice: (float) ($merchantItem->previous_price ?: 0),
            qty: $qty,
            minQty: max(1, (int) ($merchantItem->minimum_qty ?: 1)),
            stock: self::getEffectiveStock($merchantItem, $size),
            preordered: (bool) ($merchantItem->preordered ?? false),
            size: $size,
            color: $color ? ltrim($color, '#') : null,
            keys: $keys,
            values: $values,
            wholeSellQty: self::parseToArray($merchantItem->whole_sell_qty),
            wholeSellDiscount: self::parseToArray($merchantItem->whole_sell_discount),
            addedAt: now()->toDateTimeString()
        );
    }

    /**
     * Create CartItem from array (session restore)
     *
     * FAIL-FAST: All required fields must exist. No fallbacks. No defaults.
     * Missing data = Exception immediately.
     *
     * @throws \InvalidArgumentException if required fields are missing
     */
    public static function fromArray(array $data): self
    {
        // ============ REQUIRED FIELDS - MUST EXIST ============
        $requiredFields = [
            'merchant_item_id',
            'merchant_id',
            'catalog_item_id',
            'name',
            'unit_price',
            'qty',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException(
                    "CartItem missing required field: '{$field}'. " .
                    "Data source is corrupted or using old format. Keys present: " . implode(', ', array_keys($data))
                );
            }
        }

        // ============ VALIDATE CRITICAL VALUES ============
        $merchantItemId = (int) $data['merchant_item_id'];
        if ($merchantItemId <= 0) {
            throw new \InvalidArgumentException(
                "CartItem has invalid merchant_item_id: {$merchantItemId}. Must be > 0."
            );
        }

        $merchantId = (int) $data['merchant_id'];
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "CartItem has invalid merchant_id: {$merchantId}. Must be > 0."
            );
        }

        $catalogItemId = (int) $data['catalog_item_id'];
        if ($catalogItemId <= 0) {
            throw new \InvalidArgumentException(
                "CartItem has invalid catalog_item_id: {$catalogItemId}. Must be > 0."
            );
        }

        $unitPrice = (float) $data['unit_price'];
        if ($unitPrice <= 0) {
            throw new \InvalidArgumentException(
                "CartItem has invalid unit_price: {$unitPrice}. Must be > 0. " .
                "merchant_item_id={$merchantItemId}"
            );
        }

        $qty = (int) $data['qty'];
        if ($qty <= 0) {
            throw new \InvalidArgumentException(
                "CartItem has invalid qty: {$qty}. Must be > 0. " .
                "merchant_item_id={$merchantItemId}"
            );
        }

        $name = (string) $data['name'];
        if (empty(trim($name))) {
            throw new \InvalidArgumentException(
                "CartItem has empty name. merchant_item_id={$merchantItemId}"
            );
        }

        // ============ OPTIONAL FIELDS - Use null, not fallback values ============
        return new self(
            merchantItemId: $merchantItemId,
            merchantId: $merchantId,
            catalogItemId: $catalogItemId,
            brandQualityId: isset($data['brand_quality_id']) && $data['brand_quality_id'] > 0
                ? (int) $data['brand_quality_id']
                : null,
            name: $name,
            nameAr: $data['name_ar'] ?? $name, // Fallback to name is OK for display
            photo: $data['photo'] ?? '', // Empty photo is valid
            slug: $data['slug'] ?? '', // Empty slug is valid
            partNumber: $data['part_number'] ?? '', // Empty part_number is valid
            merchantName: $data['merchant_name'] ?? '', // Empty is valid
            merchantNameAr: $data['merchant_name_ar'] ?? '', // Empty is valid
            unitPrice: $unitPrice,
            sizePrice: (float) ($data['size_price'] ?? 0), // 0 is valid (no size price)
            colorPrice: (float) ($data['color_price'] ?? 0), // 0 is valid (no color price)
            previousPrice: (float) ($data['previous_price'] ?? 0), // 0 is valid (no previous price)
            qty: $qty,
            minQty: max(1, (int) ($data['min_qty'] ?? 1)), // min 1 is logical default
            stock: (int) ($data['stock'] ?? 0), // 0 stock is valid (preorder)
            preordered: (bool) ($data['preordered'] ?? false),
            size: $data['size'] ?? null, // null is valid (no size)
            color: $data['color'] ?? null, // null is valid (no color)
            keys: $data['keys'] ?? null, // null is valid
            values: $data['values'] ?? null, // null is valid
            wholeSellQty: $data['whole_sell_qty'] ?? [], // Empty is valid
            wholeSellDiscount: $data['whole_sell_discount'] ?? [], // Empty is valid
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
     * FAIL-FAST: merchant_id must exist in each item. No fallbacks.
     *
     * @param array<string, array> $items All cart items
     * @param int $merchantId Merchant to filter by
     * @return array{qty: int, subtotal: float, discount: float, total: float}
     * @throws \InvalidArgumentException if merchantId is invalid or items have missing merchant_id
     */
    public static function calculateMerchantTotals(array $items, int $merchantId): array
    {
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid merchantId: {$merchantId}. Must be > 0."
            );
        }

        $merchantItems = [];
        foreach ($items as $key => $item) {
            if (!isset($item['merchant_id'])) {
                throw new \InvalidArgumentException(
                    "Cart item '{$key}' missing required field: merchant_id. " .
                    "Keys present: " . implode(', ', array_keys($item))
                );
            }

            $itemMerchantId = (int) $item['merchant_id'];
            if ($itemMerchantId <= 0) {
                throw new \InvalidArgumentException(
                    "Cart item '{$key}' has invalid merchant_id: {$itemMerchantId}. Must be > 0."
                );
            }

            if ($itemMerchantId === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return self::calculateTotals($merchantItems);
    }

    // ===================== Helper Methods =====================

    private static function parseToArray($value): array
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

        $sizes = self::parseToArray($mp->size);
        $prices = self::parseToArray($mp->size_price);
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

        $colors = self::parseToArray($mp->color_all);
        $prices = self::parseToArray($mp->color_price);
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
            $sizes = self::parseToArray($mp->size);
            $qtys = self::parseToArray($mp->size_qty);
            $idx = array_search(trim($size), array_map('trim', $sizes), true);

            if ($idx !== false && isset($qtys[$idx])) {
                return (int) $qtys[$idx];
            }
        }

        return (int) ($mp->stock ?? 0);
    }

    /**
     * Get product dimensions for shipping calculations
     * NO FALLBACKS - throws if MerchantItem not found
     *
     * @param int $merchantItemId
     * @return array{weight: float|null, length: float|null, width: float|null, height: float|null, has_weight: bool, has_dimensions: bool}
     * @throws \InvalidArgumentException if MerchantItem not found
     */
    public static function getCatalogItemDimensions(int $merchantItemId): array
    {
        $mp = MerchantItem::with('catalogItem')->find($merchantItemId);

        if (!$mp) {
            throw new \InvalidArgumentException("MerchantItem {$merchantItemId} not found");
        }

        if (!$mp->catalogItem) {
            throw new \InvalidArgumentException("MerchantItem {$merchantItemId} has no associated CatalogItem");
        }

        $catalogItem = $mp->catalogItem;

        // Priority: merchant_items -> catalog_items (NO fallbacks to hardcoded values)
        $weight = $mp->weight ?? $catalogItem->weight ?? null;
        $length = $mp->length ?? $catalogItem->length ?? null;
        $width = $mp->width ?? $catalogItem->width ?? null;
        $height = $mp->height ?? $catalogItem->height ?? null;

        $hasWeight = $weight !== null && $weight > 0;
        $hasDimensions = $length !== null && $width !== null && $height !== null;

        return [
            'weight' => $hasWeight ? (float) $weight : null,
            'length' => $length !== null ? (float) $length : null,
            'width' => $width !== null ? (float) $width : null,
            'height' => $height !== null ? (float) $height : null,
            'has_weight' => $hasWeight,
            'has_dimensions' => $hasDimensions,
        ];
    }
}
