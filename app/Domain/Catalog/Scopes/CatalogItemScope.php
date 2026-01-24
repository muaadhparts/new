<?php

namespace App\Domain\Catalog\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Catalog Item Scope
 *
 * Local scopes for catalog item queries.
 */
trait CatalogItemScope
{
    /**
     * Scope to filter by brand.
     */
    public function scopeForBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('new_category_id', $categoryId);
    }

    /**
     * Scope to filter by categories (including children).
     */
    public function scopeInCategories(Builder $query, array $categoryIds): Builder
    {
        return $query->whereIn('new_category_id', $categoryIds);
    }

    /**
     * Scope to search by name or SKU.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('name_ar', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('part_number', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to get items with available stock.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereHas('merchantItems', function ($q) {
            $q->where('status', 1)->where('stock', '>', 0);
        });
    }

    /**
     * Scope to get items with reviews.
     */
    public function scopeWithReviews(Builder $query): Builder
    {
        return $query->whereHas('reviews');
    }

    /**
     * Scope to get items with high ratings.
     */
    public function scopeHighRated(Builder $query, float $minRating = 4.0): Builder
    {
        return $query->where('average_rating', '>=', $minRating);
    }

    /**
     * Scope to order by popularity.
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('view_count');
    }

    /**
     * Scope to order by newest.
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope to get recently viewed items.
     */
    public function scopeRecentlyViewed(Builder $query, array $itemIds): Builder
    {
        return $query->whereIn('id', $itemIds)
            ->orderByRaw('FIELD(id, ' . implode(',', $itemIds) . ')');
    }
}
