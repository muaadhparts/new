<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * CatalogItemMerchantService
 *
 * Unified service for all merchant-related operations on CatalogItem.
 * Handles merchant item retrieval, stock, and merchant-specific attributes.
 */
class CatalogItemMerchantService
{
    /**
     * Get active merchant item for a catalog item
     */
    public function getActiveMerchantItem(CatalogItem $item, ?int $userId = null): ?MerchantItem
    {
        // Use eager-loaded relation if available
        if ($item->relationLoaded('merchantItems')) {
            return $item->merchantItems
                ->where('status', 1)
                ->when($userId, fn($collection) => $collection->where('user_id', $userId))
                ->first();
        }

        // Otherwise query database
        return $item->activeMerchantItems()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->first();
    }

    /**
     * Get best merchant item (lowest price, in stock first)
     */
    public function getBestMerchantItem(CatalogItem $item): ?MerchantItem
    {
        return $item->merchantItems()
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price', 'asc')
            ->first();
    }

    /**
     * Get merchant item ID for a specific merchant
     */
    public function getMerchantItemId(CatalogItem $item, int $userId): ?int
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->id;
    }

    /**
     * Check if catalog item has active merchants
     */
    public function hasActiveMerchants(CatalogItem $item): bool
    {
        if ($item->relationLoaded('merchantItems')) {
            return $item->merchantItems->where('status', 1)->isNotEmpty();
        }

        return $item->activeMerchantItems()->exists();
    }

    /**
     * Get count of active merchants
     */
    public function getActiveMerchantsCount(CatalogItem $item): int
    {
        if ($item->relationLoaded('merchantItems')) {
            return $item->merchantItems->where('status', 1)->count();
        }

        return $item->activeMerchantItems()->count();
    }

    /**
     * Get stock quantity for a merchant
     */
    public function getStock(CatalogItem $item, ?int $userId = null): int
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem ? (int) $merchantItem->stock : 0;
    }

    /**
     * Check if catalog item has no stock at all
     */
    public function hasNoStock(CatalogItem $item): bool
    {
        return $item->merchantItems()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->doesntExist();
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(CatalogItem $item, ?int $userId = null): bool
    {
        if ($userId) {
            $merchantItem = $this->getActiveMerchantItem($item, $userId);
            return !$merchantItem || (string) $merchantItem->stock === "0";
        }

        // Check if any active merchant has stock
        $hasStock = $item->merchantItems()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->exists();

        return !$hasStock;
    }

    /**
     * Get item condition
     */
    public function getItemCondition(CatalogItem $item, ?int $userId = null): int
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem ? (int) $merchantItem->item_condition : 0;
    }

    /**
     * Get minimum quantity
     */
    public function getMinimumQty(CatalogItem $item, ?int $userId = null): ?int
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->minimum_qty;
    }

    /**
     * Get stock check setting
     */
    public function getStockCheck(CatalogItem $item, ?int $userId = null): int
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem ? (int) $merchantItem->stock_check : 0;
    }

    /**
     * Get merchant-specific details
     */
    public function getDetails(CatalogItem $item, ?int $userId = null): ?string
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->details;
    }

    /**
     * Get merchant-specific policy
     */
    public function getPolicy(CatalogItem $item, ?int $userId = null): ?string
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->policy;
    }

    /**
     * Get merchant-specific ship info
     */
    public function getShip(CatalogItem $item, ?int $userId = null)
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->ship;
    }

    /**
     * Get merchant-specific previous price
     */
    public function getPreviousPrice(CatalogItem $item, ?int $userId = null): ?float
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        return $merchantItem?->previous_price ? (float) $merchantItem->previous_price : null;
    }

    /**
     * Get merchant info for display
     */
    public function getMerchantInfo(CatalogItem $item, ?int $userId = null): ?array
    {
        $merchantItem = $this->getActiveMerchantItem($item, $userId);
        
        if (!$merchantItem) {
            return null;
        }

        return [
            'id' => $merchantItem->user_id,
            'name' => $merchantItem->user->shop_name ?? $merchantItem->user->name,
            'shop_name_ar' => $merchantItem->user->shop_name_ar,
            'email' => $merchantItem->user->email,
            'logo' => $merchantItem->user->merchant_logo,
            'quality_brand' => [
                'id' => $merchantItem->qualityBrand?->id,
                'name' => $merchantItem->qualityBrand?->name_en,
                'name_ar' => $merchantItem->qualityBrand?->name_ar,
                'logo' => $merchantItem->qualityBrand?->logo,
            ],
            'branch' => [
                'id' => $merchantItem->merchantBranch?->id,
                'name' => $merchantItem->merchantBranch?->branch_name,
                'warehouse' => $merchantItem->merchantBranch?->warehouse_name,
                'city' => $merchantItem->merchantBranch?->city?->name,
            ],
        ];
    }
}
