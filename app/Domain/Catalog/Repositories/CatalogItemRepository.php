<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Catalog Item Repository
 *
 * Repository for catalog item data access.
 */
class CatalogItemRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return CatalogItem::class;
    }

    /**
     * Find by SKU.
     */
    public function findBySku(string $sku): ?CatalogItem
    {
        return $this->findFirstBy('sku', $sku);
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $slug): ?CatalogItem
    {
        return $this->findFirstBy('slug', $slug);
    }

    /**
     * Get items by category.
     */
    public function getByCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('newcategory_id', $categoryId)
            ->where('status', 1)
            ->paginate($perPage);
    }

    /**
     * Get items by brand.
     */
    public function getByBrand(int $brandId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('brand_id', $brandId)
            ->where('status', 1)
            ->paginate($perPage);
    }

    /**
     * Search items.
     */
    public function search(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('part_number', 'like', "%{$query}%");
            })
            ->paginate($perPage);
    }

    /**
     * Get items with active merchant offers.
     */
    public function getWithMerchantOffers(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with(['merchantItems' => fn($q) => $q->where('status', 1)])
            ->paginate($perPage);
    }

    /**
     * Get recently added items.
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->query()
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
