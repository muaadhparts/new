<?php

namespace App\Domain\Merchant\Queries;

use App\Models\MerchantItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * MerchantItemQuery - Query builder for merchant items
 *
 * Provides fluent interface for building MerchantItem queries.
 */
class MerchantItemQuery
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = MerchantItem::query();
    }

    /**
     * Create new query instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter by merchant ID
     */
    public function forMerchant(int $merchantId): self
    {
        $this->query->where('user_id', $merchantId);
        return $this;
    }

    /**
     * Filter by branch ID
     */
    public function forBranch(int $branchId): self
    {
        $this->query->where('merchant_branch_id', $branchId);
        return $this;
    }

    /**
     * Filter by catalog item ID
     */
    public function forCatalogItem(int $catalogItemId): self
    {
        $this->query->where('catalog_item_id', $catalogItemId);
        return $this;
    }

    /**
     * Filter active items only
     */
    public function active(): self
    {
        $this->query->where('status', 1);
        return $this;
    }

    /**
     * Filter inactive items
     */
    public function inactive(): self
    {
        $this->query->where('status', 0);
        return $this;
    }

    /**
     * Filter items in stock
     */
    public function inStock(): self
    {
        $this->query->where(function ($q) {
            $q->where('stock', '>', 0)
                ->orWhere('preordered', 1);
        });
        return $this;
    }

    /**
     * Filter out of stock items
     */
    public function outOfStock(): self
    {
        $this->query->where('stock', '<=', 0)
            ->where('preordered', 0);
        return $this;
    }

    /**
     * Filter preorder items
     */
    public function preorder(): self
    {
        $this->query->where('preordered', 1);
        return $this;
    }

    /**
     * Filter by quality brand
     */
    public function withQualityBrand(int $qualityBrandId): self
    {
        $this->query->where('quality_brand_id', $qualityBrandId);
        return $this;
    }

    /**
     * Filter discounted items
     */
    public function onDiscount(): self
    {
        $this->query->where('is_discount', 1)
            ->where('discount_date', '>=', date('Y-m-d'));
        return $this;
    }

    /**
     * Filter by price range
     */
    public function priceRange(float $min, float $max): self
    {
        $this->query->whereBetween('price', [$min, $max]);
        return $this;
    }

    /**
     * Filter below price
     */
    public function maxPrice(float $price): self
    {
        $this->query->where('price', '<=', $price);
        return $this;
    }

    /**
     * Filter above price
     */
    public function minPrice(float $price): self
    {
        $this->query->where('price', '>=', $price);
        return $this;
    }

    /**
     * Filter low stock items
     */
    public function lowStock(int $threshold = 5): self
    {
        $this->query->where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->where('preordered', 0);
        return $this;
    }

    /**
     * Search by catalog item name or part number
     */
    public function search(string $term): self
    {
        $this->query->whereHas('catalogItem', function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
                ->orWhere('part_number', 'like', $term . '%');
        });
        return $this;
    }

    /**
     * Order by price ascending
     */
    public function cheapestFirst(): self
    {
        $this->query->orderBy('price', 'asc');
        return $this;
    }

    /**
     * Order by price descending
     */
    public function expensiveFirst(): self
    {
        $this->query->orderBy('price', 'desc');
        return $this;
    }

    /**
     * Order by stock descending
     */
    public function mostStock(): self
    {
        $this->query->orderBy('stock', 'desc');
        return $this;
    }

    /**
     * Order by newest first
     */
    public function latest(): self
    {
        $this->query->orderBy('created_at', 'desc');
        return $this;
    }

    /**
     * Eager load relations
     */
    public function withRelations(): self
    {
        $this->query->with([
            'catalogItem',
            'catalogItem.fitments.brand',
            'qualityBrand',
            'merchantBranch',
            'user',
        ]);
        return $this;
    }

    /**
     * Only with active merchant
     */
    public function withActiveMerchant(): self
    {
        $this->query->whereHas('user', fn($q) => $q->where('is_merchant', 2));
        return $this;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get first result
     */
    public function first(): ?MerchantItem
    {
        return $this->query->first();
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get sum of stock
     */
    public function totalStock(): int
    {
        return (int) $this->query->sum('stock');
    }

    /**
     * Get average price
     */
    public function averagePrice(): float
    {
        return (float) $this->query->avg('price');
    }

    /**
     * Get inventory value (sum of stock * price)
     */
    public function inventoryValue(): float
    {
        return (float) $this->query->selectRaw('SUM(stock * price) as value')->value('value');
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}
