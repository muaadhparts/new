<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * MerchantItemStockService - Centralized stock management for merchant items
 *
 * Handles all stock operations including reservations, releases, and reporting.
 */
class MerchantItemStockService
{
    /**
     * Check if item has sufficient stock
     */
    public function hasSufficientStock(MerchantItem $item, int $quantity): bool
    {
        // If stock check is disabled, always return true
        if ($item->stock_check === 0) {
            return true;
        }

        // If item is preordered, stock check is bypassed
        if ($item->preordered) {
            return true;
        }

        return $item->stock >= $quantity;
    }

    /**
     * Reserve stock for purchase
     */
    public function reserveStock(MerchantItem $item, int $quantity): bool
    {
        if (!$this->hasSufficientStock($item, $quantity)) {
            return false;
        }

        // Don't decrement stock for preordered items
        if ($item->preordered) {
            return true;
        }

        return $item->decrement('stock', $quantity) > 0;
    }

    /**
     * Release reserved stock (e.g., cancelled order)
     */
    public function releaseStock(MerchantItem $item, int $quantity): void
    {
        // Don't increment stock for preordered items
        if ($item->preordered) {
            return;
        }

        $item->increment('stock', $quantity);
    }

    /**
     * Update stock
     */
    public function updateStock(MerchantItem $item, int $newStock): void
    {
        $item->update(['stock' => $newStock]);
    }

    /**
     * Get low stock items for merchant
     */
    public function getLowStockItems(int $merchantId, int $threshold = 5): Collection
    {
        return MerchantItem::where('user_id', $merchantId)
            ->where('status', 1)
            ->where('preordered', 0)
            ->where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->with(['catalogItem'])
            ->get();
    }

    /**
     * Get out of stock items for merchant
     */
    public function getOutOfStockItems(int $merchantId): Collection
    {
        return MerchantItem::where('user_id', $merchantId)
            ->where('status', 1)
            ->where('preordered', 0)
            ->where('stock', '<=', 0)
            ->with(['catalogItem'])
            ->get();
    }

    /**
     * Bulk update stock
     */
    public function bulkUpdateStock(array $updates): void
    {
        DB::transaction(function () use ($updates) {
            foreach ($updates as $itemId => $stock) {
                MerchantItem::where('id', $itemId)->update(['stock' => $stock]);
            }
        });
    }

    /**
     * Get stock summary for merchant
     */
    public function getStockSummary(int $merchantId): array
    {
        $items = MerchantItem::where('user_id', $merchantId)
            ->where('status', 1)
            ->get();

        $inStock = $items->where('stock', '>', 0)->where('preordered', 0);
        $outOfStock = $items->where('stock', '<=', 0)->where('preordered', 0);
        $lowStock = $items->where('stock', '>', 0)->where('stock', '<=', 5)->where('preordered', 0);
        $preordered = $items->where('preordered', 1);

        return [
            'total_items' => $items->count(),
            'in_stock' => $inStock->count(),
            'out_of_stock' => $outOfStock->count(),
            'low_stock' => $lowStock->count(),
            'preordered' => $preordered->count(),
            'total_stock_quantity' => $inStock->sum('stock'),
            'total_stock_value' => $inStock->sum(fn($item) => $item->stock * $item->price),
            'total_stock_value_formatted' => monetaryUnit()->format(
                $inStock->sum(fn($item) => $item->stock * $item->price)
            ),
        ];
    }

    /**
     * Check if item is available for purchase
     */
    public function isAvailable(MerchantItem $item): bool
    {
        // Must be active
        if ($item->status !== 1) {
            return false;
        }

        // Must have approved merchant
        if ($item->user?->is_merchant !== 2) {
            return false;
        }

        // Must have stock or be preordered
        if ($item->stock <= 0 && !$item->preordered) {
            return false;
        }

        return true;
    }

    /**
     * Get stock status label
     */
    public function getStockStatusLabel(MerchantItem $item): string
    {
        if ($item->preordered) {
            return __('Pre-order');
        }

        if ($item->stock <= 0) {
            return __('Out of Stock');
        }

        if ($item->stock <= 5) {
            return __('Low Stock');
        }

        return __('In Stock');
    }

    /**
     * Get stock status color
     */
    public function getStockStatusColor(MerchantItem $item): string
    {
        if ($item->preordered) {
            return 'info';
        }

        if ($item->stock <= 0) {
            return 'danger';
        }

        if ($item->stock <= 5) {
            return 'warning';
        }

        return 'success';
    }
}
