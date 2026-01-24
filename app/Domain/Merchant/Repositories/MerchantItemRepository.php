<?php

namespace App\Domain\Merchant\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Merchant Item Repository
 *
 * Repository for merchant item (inventory) data access.
 */
class MerchantItemRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return MerchantItem::class;
    }

    /**
     * Get items by merchant.
     */
    public function getByMerchant(int $merchantId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->with('catalogItem')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get active items by merchant.
     */
    public function getActiveByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get low stock items for merchant.
     */
    public function getLowStockByMerchant(int $merchantId, int $threshold = 5): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('stock', '<=', $threshold)
            ->where('stock', '>', 0)
            ->get();
    }

    /**
     * Get out of stock items for merchant.
     */
    public function getOutOfStockByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('stock', 0)
            ->get();
    }

    /**
     * Find by merchant and catalog item.
     */
    public function findByMerchantAndCatalogItem(int $merchantId, int $catalogItemId): ?MerchantItem
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('catalog_item_id', $catalogItemId)
            ->first();
    }

    /**
     * Get items for catalog item.
     */
    public function getByCatalogItem(int $catalogItemId): Collection
    {
        return $this->query()
            ->where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->with('merchant')
            ->orderBy('price')
            ->get();
    }

    /**
     * Update stock.
     */
    public function updateStock(int $id, int $quantity): bool
    {
        return $this->update($id, ['stock' => $quantity]);
    }

    /**
     * Decrement stock.
     */
    public function decrementStock(int $id, int $quantity = 1): bool
    {
        return $this->query()
            ->where('id', $id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity) > 0;
    }
}
