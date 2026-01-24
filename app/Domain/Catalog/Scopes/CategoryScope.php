<?php

namespace App\Domain\Catalog\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Category Scope
 *
 * Local scopes for category queries.
 */
trait CategoryScope
{
    /**
     * Scope to get root categories only.
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get children of a category.
     */
    public function scopeChildrenOf(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope to get categories at a specific level.
     */
    public function scopeAtLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to get categories with products.
     */
    public function scopeWithProducts(Builder $query): Builder
    {
        return $query->whereHas('catalogItems');
    }

    /**
     * Scope to get categories without products.
     */
    public function scopeWithoutProducts(Builder $query): Builder
    {
        return $query->whereDoesntHave('catalogItems');
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to get leaf categories (no children).
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Scope to get categories with children.
     */
    public function scopeParents(Builder $query): Builder
    {
        return $query->whereHas('children');
    }
}
