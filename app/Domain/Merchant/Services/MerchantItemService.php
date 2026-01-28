<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\DB;

/**
 * MerchantItemService - CRUD operations for merchant items
 *
 * Centralized business logic for creating, updating, and deleting merchant items.
 */
class MerchantItemService
{
    /**
     * Create new merchant item
     */
    public function createMerchantItem(int $merchantId, array $data): MerchantItem
    {
        return DB::transaction(function () use ($merchantId, $data) {
            return MerchantItem::create([
                'user_id' => $merchantId,
                'catalog_item_id' => $data['catalog_item_id'],
                'merchant_branch_id' => $data['merchant_branch_id'] ?? null,
                'quality_brand_id' => $data['quality_brand_id'] ?? null,
                'item_type' => $data['item_type'] ?? 'normal',
                'affiliate_link' => $data['affiliate_link'] ?? null,
                'price' => $data['price'],
                'previous_price' => $data['previous_price'] ?? null,
                'stock' => $data['stock'] ?? 0,
                'whole_sell_qty' => $data['whole_sell_qty'] ?? null,
                'whole_sell_discount' => $data['whole_sell_discount'] ?? null,
                'preordered' => $data['preordered'] ?? false,
                'minimum_qty' => $data['minimum_qty'] ?? 1,
                'stock_check' => $data['stock_check'] ?? 1,
                'status' => $data['status'] ?? 1,
                'ship' => $data['ship'] ?? null,
                'item_condition' => $data['item_condition'] ?? 1,
                'details' => $data['details'] ?? null,
                'policy' => $data['policy'] ?? null,
            ]);
        });
    }

    /**
     * Update merchant item
     */
    public function updateMerchantItem(MerchantItem $item, array $data): MerchantItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->update($data);
            return $item->fresh();
        });
    }

    /**
     * Delete merchant item
     */
    public function deleteMerchantItem(MerchantItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            // Delete related photos
            if ($item->photos()->exists()) {
                foreach ($item->photos as $photo) {
                    // Delete physical file
                    $photoPath = public_path('assets/images/products/' . $photo->photo);
                    if (file_exists($photoPath)) {
                        unlink($photoPath);
                    }
                    $photo->delete();
                }
            }

            return $item->delete();
        });
    }

    /**
     * Toggle item status (active/inactive)
     */
    public function toggleStatus(MerchantItem $item): MerchantItem
    {
        $item->update(['status' => $item->status === 1 ? 0 : 1]);
        return $item->fresh();
    }

    /**
     * Activate item
     */
    public function activate(MerchantItem $item): MerchantItem
    {
        $item->update(['status' => 1]);
        return $item->fresh();
    }

    /**
     * Deactivate item
     */
    public function deactivate(MerchantItem $item): MerchantItem
    {
        $item->update(['status' => 0]);
        return $item->fresh();
    }

    /**
     * Update price
     */
    public function updatePrice(MerchantItem $item, float $newPrice, ?float $previousPrice = null): MerchantItem
    {
        $item->update([
            'price' => $newPrice,
            'previous_price' => $previousPrice,
        ]);
        return $item->fresh();
    }

    /**
     * Update stock
     */
    public function updateStock(MerchantItem $item, int $newStock): MerchantItem
    {
        $item->update(['stock' => $newStock]);
        return $item->fresh();
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $itemIds, int $status): int
    {
        return MerchantItem::whereIn('id', $itemIds)->update(['status' => $status]);
    }

    /**
     * Bulk delete items
     */
    public function bulkDelete(array $itemIds): int
    {
        return DB::transaction(function () use ($itemIds) {
            $items = MerchantItem::whereIn('id', $itemIds)->get();
            
            foreach ($items as $item) {
                $this->deleteMerchantItem($item);
            }

            return $items->count();
        });
    }

    /**
     * Duplicate merchant item
     */
    public function duplicate(MerchantItem $item): MerchantItem
    {
        return DB::transaction(function () use ($item) {
            $newItem = $item->replicate();
            $newItem->status = 0; // Set as inactive by default
            $newItem->save();

            // Copy photos
            if ($item->photos()->exists()) {
                foreach ($item->photos as $photo) {
                    $newPhoto = $photo->replicate();
                    $newPhoto->merchant_item_id = $newItem->id;
                    $newPhoto->save();
                }
            }

            return $newItem;
        });
    }

    /**
     * Check if merchant can create more items (quota check)
     */
    public function canCreateMoreItems(int $merchantId, int $maxItems = 1000): bool
    {
        $currentCount = MerchantItem::where('user_id', $merchantId)->count();
        return $currentCount < $maxItems;
    }

    /**
     * Get merchant items count
     */
    public function getMerchantItemsCount(int $merchantId): int
    {
        return MerchantItem::where('user_id', $merchantId)->count();
    }
}
