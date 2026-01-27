<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CatalogItemQuery - Query builder for CatalogItem
 *
 * Provides fluent interface for building complex CatalogItem queries.
 * Follows CatalogItem-first principle: one card per SKU.
 */
class CatalogItemQuery
{
    protected Builder $query;
    protected array $merchantFilters = [];
    protected array $branchFilters = [];
    protected array $qualityBrandFilters = [];
    protected ?float $minPrice = null;
    protected ?float $maxPrice = null;
    protected float $currencyValue = 1.0;
    protected ?string $searchTerm = null;
    protected bool $hasDiscount = false;
    protected ?string $sortBy = null;

    public function __construct()
    {
        $this->query = CatalogItem::query()->withOffersData();
    }

    /**
     * Create new query instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter by brand (via fitments)
     */
    public function forBrand(int $brandId): self
    {
        $this->query->whereHas('fitments', fn($f) => $f->where('brand_id', $brandId));
        return $this;
    }

    /**
     * Filter by catalog code (via parts table)
     */
    public function forCatalog(string $catalogCode, ?array $categoryIds = null): self
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $catalogCode)) {
            $this->query->whereRaw('1 = 0');
            return $this;
        }

        $partsTable = strtolower("parts_{$catalogCode}");
        $sectionPartsTable = strtolower("section_parts_{$catalogCode}");

        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            $this->query->whereRaw('1 = 0');
            return $this;
        }

        $this->query->whereExists(function ($exists) use ($partsTable, $sectionPartsTable, $categoryIds) {
            $exists->selectRaw(1)
                ->from("{$partsTable} as p")
                ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                ->whereColumn('p.part_number', 'catalog_items.part_number');

            if ($categoryIds !== null) {
                $exists->whereIn('sp.category_id', $categoryIds);
            }
        });

        return $this;
    }

    /**
     * Filter by category IDs (for NewCategory tree)
     */
    public function forCategories(string $catalogCode, array $categoryIds): self
    {
        return $this->forCatalog($catalogCode, $categoryIds);
    }

    /**
     * Filter by merchant IDs
     */
    public function forMerchants(array $merchantIds): self
    {
        $this->merchantFilters = array_filter(array_map('intval', $merchantIds));

        if (!empty($this->merchantFilters)) {
            $this->query->whereHas('merchantItems', function ($q) {
                $q->where('status', 1)
                    ->whereIn('user_id', $this->merchantFilters)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            });
        }

        return $this;
    }

    /**
     * Filter by branch IDs with merchant context isolation
     */
    public function forBranches(array $branchIds, array $merchantIds = []): self
    {
        $this->branchFilters = array_filter(array_map('intval', $branchIds));

        if (empty($this->branchFilters) || empty($merchantIds)) {
            return $this;
        }

        // Get branch-merchant mapping
        $branchMerchantMap = $this->getBranchMerchantMapping($this->branchFilters);

        if (empty($branchMerchantMap)) {
            return $this;
        }

        $branchesByMerchant = [];
        foreach ($this->branchFilters as $branchId) {
            if (isset($branchMerchantMap[$branchId])) {
                $merchantId = $branchMerchantMap[$branchId];
                if (!isset($branchesByMerchant[$merchantId])) {
                    $branchesByMerchant[$merchantId] = [];
                }
                $branchesByMerchant[$merchantId][] = $branchId;
            }
        }

        $merchantsWithBranches = array_keys($branchesByMerchant);
        $merchantsWithoutBranches = array_diff(array_map('intval', $merchantIds), $merchantsWithBranches);

        $this->query->whereHas('merchantItems', function ($q) use ($branchesByMerchant, $merchantsWithoutBranches) {
            $q->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                ->where(function ($subQ) use ($branchesByMerchant, $merchantsWithoutBranches) {
                    foreach ($branchesByMerchant as $merchantId => $branches) {
                        $subQ->orWhere(function ($innerQ) use ($merchantId, $branches) {
                            $innerQ->where('user_id', $merchantId)
                                ->whereIn('merchant_branch_id', $branches);
                        });
                    }

                    if (!empty($merchantsWithoutBranches)) {
                        $subQ->orWhereIn('user_id', $merchantsWithoutBranches);
                    }
                });
        });

        return $this;
    }

    /**
     * Filter by quality brand IDs
     */
    public function forQualityBrands(array $qualityBrandIds): self
    {
        $this->qualityBrandFilters = array_filter(array_map('intval', $qualityBrandIds));

        if (!empty($this->qualityBrandFilters)) {
            $this->query->whereHas('merchantItems', function ($q) {
                $q->where('status', 1)
                    ->whereIn('quality_brand_id', $this->qualityBrandFilters)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            });
        }

        return $this;
    }

    /**
     * Filter by price range
     */
    public function priceRange(?float $min, ?float $max, float $currencyValue = 1.0): self
    {
        $this->minPrice = $min;
        $this->maxPrice = $max;
        $this->currencyValue = $currencyValue;

        if ($min) {
            $this->query->having('lowest_price', '>=', $min / $currencyValue);
        }
        if ($max) {
            $this->query->having('lowest_price', '<=', $max / $currencyValue);
        }

        return $this;
    }

    /**
     * Search by name or part number
     */
    public function search(?string $term): self
    {
        $this->searchTerm = $term;

        if (!empty($term)) {
            $this->query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('part_number', 'like', $term . '%');
            });
        }

        return $this;
    }

    /**
     * Filter items with discount (previous_price > price)
     */
    public function withDiscount(): self
    {
        $this->hasDiscount = true;

        $this->query->whereHas('merchantItems', function ($q) {
            $q->where('status', 1)
                ->whereNotNull('previous_price')
                ->whereColumn('previous_price', '>', 'price')
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
        });

        return $this;
    }

    /**
     * Apply sorting
     */
    public function sortBy(?string $sort): self
    {
        $this->sortBy = $sort;

        match ($sort) {
            'price_asc' => $this->query->orderBy('lowest_price', 'asc'),
            'price_desc' => $this->query->orderBy('lowest_price', 'desc'),
            'part_number' => $this->query->orderBy('catalog_items.part_number', 'asc'),
            'name_asc' => $this->applyNameSorting(),
            default => $this->query->orderBy('lowest_price', 'asc'),
        };

        return $this;
    }

    /**
     * Apply name sorting based on locale
     */
    protected function applyNameSorting(): void
    {
        $isArabic = app()->getLocale() === 'ar';

        if ($isArabic) {
            $this->query->orderByRaw("CASE WHEN catalog_items.label_ar IS NOT NULL AND catalog_items.label_ar != '' THEN 0 ELSE 1 END ASC")
                ->orderByRaw("COALESCE(NULLIF(catalog_items.label_ar, ''), NULLIF(catalog_items.label_en, ''), catalog_items.name) ASC");
        } else {
            $this->query->orderByRaw("CASE WHEN catalog_items.label_en IS NOT NULL AND catalog_items.label_en != '' THEN 0 ELSE 1 END ASC")
                ->orderByRaw("COALESCE(NULLIF(catalog_items.label_en, ''), NULLIF(catalog_items.label_ar, ''), catalog_items.name) ASC");
        }
    }

    /**
     * Eager load merchant items with filters
     */
    public function withMerchantItems(): self
    {
        $merchantFilters = $this->merchantFilters;
        $branchFilters = $this->branchFilters;
        $qualityBrandFilters = $this->qualityBrandFilters;

        $this->query->with(['merchantItems' => function ($q) use ($merchantFilters, $branchFilters, $qualityBrandFilters) {
            $q->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                ->with([
                    'user:id,is_merchant,name,shop_name,shop_name_ar,email',
                    'qualityBrand:id,name_en,name_ar,logo',
                    'merchantBranch:id,warehouse_name,branch_name',
                ]);

            if (!empty($merchantFilters)) {
                $q->whereIn('user_id', $merchantFilters);
            }

            if (!empty($qualityBrandFilters)) {
                $q->whereIn('quality_brand_id', $qualityBrandFilters);
            }

            $q->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
                ->orderBy('price', 'asc');
        }])
            ->with('fitments.brand')
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating');

        return $this;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 12)
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Get branch-merchant mapping
     */
    protected function getBranchMerchantMapping(array $branchIds): array
    {
        if (empty($branchIds)) {
            return [];
        }

        $results = DB::table('merchant_branches')
            ->whereIn('id', $branchIds)
            ->select('id', 'user_id')
            ->get();

        $mapping = [];
        foreach ($results as $row) {
            $mapping[$row->id] = $row->user_id;
        }

        return $mapping;
    }
}
