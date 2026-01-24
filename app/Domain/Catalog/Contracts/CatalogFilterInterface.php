<?php

namespace App\Domain\Catalog\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CatalogFilterInterface - Contract for catalog filtering
 *
 * All catalog item filtering MUST go through this interface.
 */
interface CatalogFilterInterface
{
    /**
     * Build catalog item query with eager loading
     */
    public function buildCatalogItemQuery(): Builder;

    /**
     * Apply all filters from request
     */
    public function applyCatalogItemFilters(Builder $query, Request $request): Builder;

    /**
     * Apply merchant filter
     */
    public function applyCatalogItemMerchantFilter(Builder $query, ?int $merchantId): Builder;

    /**
     * Apply branch filter
     */
    public function applyCatalogItemBranchFilter(Builder $query, ?int $branchId): Builder;

    /**
     * Apply price range filter
     */
    public function applyCatalogItemPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice): Builder;

    /**
     * Apply search filter
     */
    public function applyCatalogItemSearchFilter(Builder $query, ?string $search): Builder;

    /**
     * Apply sorting
     */
    public function applyCatalogItemSorting(Builder $query, ?string $sortBy, string $sortDir = 'asc'): Builder;

    /**
     * Get filter sidebar data
     */
    public function getFilterSidebarData(Request $request): array;

    /**
     * Get paginated results with card data
     */
    public function getCatalogItemFirstResults(Builder $query, int $perPage = 20): LengthAwarePaginator;
}
