<?php

namespace App\Domain\Merchant\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Merchant Item Scope
 *
 * Local scopes for merchant item queries.
 */
trait MerchantItemScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('merchant_branch_id', $branchId);
    }

    /**
     * Scope to get in-stock items.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope to get out-of-stock items.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Scope to get low-stock items.
     */
    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query->where('stock', '>', 0)
            ->where('stock', '<=', $threshold);
    }

    /**
     * Scope to get items with discount.
     */
    public function scopeWithDiscount(Builder $query): Builder
    {
        return $query->where('discount', '>', 0);
    }

    /**
     * Scope to get items without discount.
     */
    public function scopeWithoutDiscount(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('discount')->orWhere('discount', 0);
        });
    }

    /**
     * Scope to filter by price range.
     */
    public function scopePriceBetween(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope to filter by minimum price.
     */
    public function scopeMinPrice(Builder $query, float $min): Builder
    {
        return $query->where('price', '>=', $min);
    }

    /**
     * Scope to filter by maximum price.
     */
    public function scopeMaxPrice(Builder $query, float $max): Builder
    {
        return $query->where('price', '<=', $max);
    }

    /**
     * Scope to order by price (low to high).
     */
    public function scopeOrderByPriceAsc(Builder $query): Builder
    {
        return $query->orderBy('price', 'asc');
    }

    /**
     * Scope to order by price (high to low).
     */
    public function scopeOrderByPriceDesc(Builder $query): Builder
    {
        return $query->orderBy('price', 'desc');
    }

    /**
     * Scope to get active items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get inactive items.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }
}
