<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Merchant\Models\MerchantBranch;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service for merchant catalog operations on the frontend.
 * Handles merchant product listing, filtering, and sorting.
 */
class MerchantCatalogService
{
    /**
     * Find merchant by shop slug.
     *
     * @param string $slug Shop name slug (with dashes)
     * @return User|null
     */
    public function findMerchantBySlug(string $slug): ?User
    {
        $shopName = str_replace('-', ' ', $slug);

        return User::where('shop_name', '=', $shopName)->first();
    }

    /**
     * Get quality brands available for a merchant.
     * Only returns brands that have active merchant items.
     *
     * @param int $merchantId
     * @return Collection<QualityBrand>
     */
    public function getAvailableQualityBrands(int $merchantId): Collection
    {
        $qualityIds = DB::table('merchant_items')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->whereNotNull('quality_brand_id')
            ->distinct()
            ->pluck('quality_brand_id');

        return QualityBrand::whereIn('id', $qualityIds)->get();
    }

    /**
     * Get branches that have active items for a merchant.
     *
     * @param int $merchantId
     * @return Collection<MerchantBranch>
     */
    public function getAvailableBranches(int $merchantId): Collection
    {
        $branchIds = DB::table('merchant_items')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->whereNotNull('merchant_branch_id')
            ->distinct()
            ->pluck('merchant_branch_id');

        return MerchantBranch::whereIn('id', $branchIds)
            ->where('status', 1)
            ->orderBy('branch_name', 'asc')
            ->get();
    }

    /**
     * Get latest catalog items with active merchant items (platform-wide).
     *
     * @param int $limit
     * @return Collection<CatalogItem>
     */
    public function getLatestProducts(int $limit = 5): Collection
    {
        return CatalogItem::status(1)
            ->whereHas('merchantItems', function ($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            })
            ->with(['merchantItems' => function ($q) {
                $q->where('status', 1)->with('user:id,is_merchant');
            }])
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->latest('catalog_items.id')
            ->take($limit)
            ->get();
    }

    /**
     * Get filtered catalog items for a merchant.
     *
     * @param int $merchantId
     * @param array $filters [
     *   'quality_brand' => array|null,
     *   'branch' => array|null,
     *   'type' => string|null (for discount filter),
     *   'sort' => string|null (price_asc, price_desc, name_asc, part_number),
     *   'pageby' => int|null (per page count),
     * ]
     * @param int $defaultPerPage
     * @return LengthAwarePaginator
     */
    public function getFilteredCatalogItems(int $merchantId, array $filters, int $defaultPerPage = 12): LengthAwarePaginator
    {
        $qualityBrandFilter = $filters['quality_brand'] ?? [];
        $branchFilter = $filters['branch'] ?? [];
        $sort = $filters['sort'] ?? null;
        $hasDiscountFilter = isset($filters['type']);
        $perPage = (int) ($filters['pageby'] ?? $defaultPerPage);

        $query = CatalogItem::query();

        // Eager load merchantItems for this merchant
        $query->with([
            'merchantItems' => function ($q) use ($merchantId) {
                $q->where('user_id', $merchantId)
                  ->where('status', 1)
                  ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar,logo']);
            }
        ]);

        // Filter by merchant and apply sub-filters
        $query->whereHas('merchantItems', function ($q) use ($merchantId, $qualityBrandFilter, $branchFilter, $hasDiscountFilter) {
            $q->where('user_id', $merchantId)
              ->where('status', 1);

            if (!empty($qualityBrandFilter)) {
                $q->whereIn('quality_brand_id', (array) $qualityBrandFilter);
            }

            if (!empty($branchFilter)) {
                $q->whereIn('merchant_branch_id', (array) $branchFilter);
            }

            if ($hasDiscountFilter) {
                $q->whereNotNull('previous_price')
                  ->whereColumn('previous_price', '>', 'price');
            }
        });

        // Apply sorting
        $this->applySorting($query, $sort, $merchantId);

        // Load reviews
        $query->withCount('catalogReviews')
              ->withAvg('catalogReviews', 'rating');

        // Paginate
        $paginator = $query->paginate($perPage);

        // Transform items to attach merchant-specific data
        $this->transformWithMerchantPrices($paginator, $merchantId);

        return $paginator;
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting($query, ?string $sort, int $merchantId): void
    {
        $isArabic = app()->getLocale() === 'ar';

        if ($sort === 'name_asc') {
            if ($isArabic) {
                $query->orderByRaw("CASE WHEN catalog_items.label_ar IS NOT NULL AND catalog_items.label_ar != '' THEN 0 ELSE 1 END ASC")
                      ->orderByRaw("COALESCE(NULLIF(catalog_items.label_ar, ''), NULLIF(catalog_items.label_en, ''), catalog_items.name) ASC");
            } else {
                $query->orderByRaw("CASE WHEN catalog_items.label_en IS NOT NULL AND catalog_items.label_en != '' THEN 0 ELSE 1 END ASC")
                      ->orderByRaw("COALESCE(NULLIF(catalog_items.label_en, ''), NULLIF(catalog_items.label_ar, ''), catalog_items.name) ASC");
            }
            return;
        }

        $priceSubquery = '(select min(mp.price) from merchant_items mp where mp.catalog_item_id = catalog_items.id and mp.user_id = ? and mp.status = 1)';

        match ($sort) {
            'price_desc' => $query->orderByRaw("{$priceSubquery} desc", [$merchantId]),
            'part_number' => $query->orderBy('catalog_items.part_number', 'asc'),
            default => $query->orderByRaw("{$priceSubquery} asc", [$merchantId]),
        };
    }

    /**
     * Transform paginated items to include merchant-specific price data.
     */
    private function transformWithMerchantPrices(LengthAwarePaginator $paginator, int $merchantId): void
    {
        $paginator->getCollection()->transform(function ($item) {
            $mp = $item->merchantItems->first();

            if ($mp) {
                $item->merchant_merchant_item = $mp;
                $item->price = method_exists($mp, 'merchantSizePrice')
                    ? $mp->merchantSizePrice()
                    : $mp->price;
            } else {
                $item->merchant_merchant_item = null;
                $item->price = null;
            }

            return $item;
        });
    }

    /**
     * Get filtered catalog items for API (returns Collection, not paginator).
     *
     * @param int $merchantId
     * @param array $filters ['min' => float, 'max' => float, 'sort' => string]
     * @return Collection
     */
    public function getFilteredCatalogItemsForApi(int $merchantId, array $filters): Collection
    {
        $minPrice = $filters['min'] ?? null;
        $maxPrice = $filters['max'] ?? null;
        $sort = $filters['sort'] ?? null;

        $query = CatalogItem::query();

        // Eager load merchantItems for this merchant with price filters
        $query->with(['merchantItems' => function ($q) use ($merchantId, $minPrice, $maxPrice) {
            $q->where('user_id', $merchantId)->where('status', 1);
            if ($minPrice) $q->where('price', '>=', $minPrice);
            if ($maxPrice) $q->where('price', '<=', $maxPrice);
        }]);

        // Filter by merchant
        $query->whereHas('merchantItems', function ($q) use ($merchantId, $minPrice, $maxPrice) {
            $q->where('user_id', $merchantId)->where('status', 1);
            if ($minPrice) $q->where('price', '>=', $minPrice);
            if ($maxPrice) $q->where('price', '<=', $maxPrice);
        });

        // Apply sorting
        $this->applySorting($query, $sort, $merchantId);

        return $query->get();
    }
}
