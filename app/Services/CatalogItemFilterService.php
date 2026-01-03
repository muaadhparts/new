<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\MerchantItem;
use App\Models\QualityBrand;
use App\Models\NewCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Centralized service for catalog item filtering logic
 * Uses: Brand → Catalog → NewCategory hierarchy
 * Filters via: sections → section_parts_{code} → parts_{code} → catalog_items
 */
class CatalogItemFilterService
{
    public function __construct(
        private CatalogItemCardDataBuilder $cardBuilder
    ) {}

    /**
     * Get filter sidebar data (merchants, brand qualities, categories)
     * Now uses Brand → Catalog hierarchy
     */
    public function getFilterSidebarData(): array
    {
        return [
            // Brand has 'subs' accessor that returns catalogs
            // Catalog has 'childs' accessor that returns NewCategories Level 1
            'categories' => Brand::with('catalogs')->where('status', 1)->get(),
            'merchants' => $this->getActiveMerchants(),
            'brand_qualities' => QualityBrand::active()->orderBy('name_en', 'asc')->get(),
        ];
    }

    /**
     * Get active merchants with catalog items
     */
    public function getActiveMerchants()
    {
        return MerchantItem::select('merchant_items.user_id')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->where('merchant_items.status', 1)
            ->where('users.is_merchant', 2)
            ->groupBy('merchant_items.user_id')
            ->selectRaw('merchant_items.user_id, users.shop_name, users.shop_name_ar')
            ->orderBy('users.shop_name', 'asc')
            ->get();
    }

    /**
     * Get localized shop name
     */
    private function getLocalizedShopName($merchant): string
    {
        if (app()->getLocale() === 'ar' && !empty($merchant->shop_name_ar)) {
            return $merchant->shop_name_ar;
        }
        return $merchant->shop_name ?? '';
    }

    /**
     * Resolve category hierarchy from slugs
     * Now uses: Brand → Catalog → NewCategory (3 levels)
     */
    public function resolveCategoryHierarchy(
        ?string $catSlug,
        ?string $subcatSlug,
        ?string $childcatSlug,
        ?string $cat2Slug = null,
        ?string $cat3Slug = null
    ): array {
        $cat = null;      // Brand
        $subcat = null;   // Catalog
        $childcat = null; // NewCategory Level 1
        $cat2 = null;     // NewCategory Level 2
        $cat3 = null;     // NewCategory Level 3
        $catalog = null;  // Reference for filtering
        $deepest = null;  // Deepest resolved category

        // Level 1: Resolve Brand from slug
        if (!empty($catSlug)) {
            $cat = Brand::where('slug', $catSlug)
                ->where('status', 1)
                ->first();
        }

        // Level 2: Resolve Catalog from slug
        if (!empty($subcatSlug) && $cat) {
            $catalog = Catalog::where('slug', $subcatSlug)
                ->where('brand_id', $cat->id)
                ->where('status', 1)
                ->first();
            $subcat = $catalog; // For view compatibility
        }

        // Level 3: Resolve NewCategory Level 1
        if (!empty($childcatSlug) && $catalog) {
            $childcat = NewCategory::where('slug', $childcatSlug)
                ->where('catalog_id', $catalog->id)
                ->where('level', 1)
                ->first();

            if ($childcat) {
                $deepest = $childcat;

                // Level 4: NewCategory Level 2
                if (!empty($cat2Slug)) {
                    $cat2 = NewCategory::where('slug', $cat2Slug)
                        ->where('catalog_id', $catalog->id)
                        ->where('parent_id', $childcat->id)
                        ->where('level', 2)
                        ->first();

                    if ($cat2) {
                        $deepest = $cat2;

                        // Level 5: NewCategory Level 3
                        if (!empty($cat3Slug)) {
                            $cat3 = NewCategory::where('slug', $cat3Slug)
                                ->where('catalog_id', $catalog->id)
                                ->where('parent_id', $cat2->id)
                                ->where('level', 3)
                                ->first();

                            if ($cat3) {
                                $deepest = $cat3;
                            }
                        }
                    }
                }
            }
        }

        return compact('cat', 'subcat', 'childcat', 'cat2', 'cat3', 'catalog', 'deepest');
    }

    /**
     * Get all descendant category IDs using recursive CTE
     */
    public function getDescendantIds(int $categoryId, int $catalogId): array
    {
        $sql = "
            WITH RECURSIVE category_tree AS (
                SELECT id FROM newcategories
                WHERE id = ? AND catalog_id = ?

                UNION ALL

                SELECT nc.id FROM newcategories nc
                INNER JOIN category_tree ct ON nc.parent_id = ct.id
                WHERE nc.catalog_id = ?
            )
            SELECT id FROM category_tree
        ";

        $results = DB::select($sql, [$categoryId, $catalogId, $catalogId]);
        return array_map(fn($row) => $row->id, $results);
    }

    /**
     * Build the base query for merchant items
     */
    public function buildBaseQuery(): Builder
    {
        $query = MerchantItem::query()
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'merchant_items.catalog_item_id')
            ->select('merchant_items.*')
            ->where('merchant_items.status', 1)
            ->where('merchant_items.stock', '>=', 1)
            ->whereHas('user', fn($u) => $u->where('is_merchant', 2));

        $this->cardBuilder->applyMerchantItemEagerLoading($query);

        return $query;
    }

    /**
     * Apply fitment filter using NewCategories → sections → parts tables
     */
    public function applyFitmentFilters(Builder $query, $cat, $subcat, $childcat, $catalog = null): void
    {
        // No filters = show all
        if (!$cat && !$subcat && !$childcat) {
            return;
        }

        // Only Brand selected → filter by brand_id
        if ($cat && !$subcat && !$childcat) {
            $query->whereHas('catalogItem', fn($q) => $q->where('brand_id', $cat->id));
            return;
        }

        // Get catalog for dynamic table names
        $catalogObj = $catalog ?? $subcat;
        if (!$catalogObj || !($catalogObj instanceof Catalog)) {
            return;
        }

        $catalogCode = $catalogObj->code;

        // Only Catalog selected (no NewCategory)
        if (!$childcat) {
            $this->applyPartsTableFilter($query, $catalogCode, null);
            return;
        }

        // NewCategory selected → get all descendants
        $categoryIds = $this->getDescendantIds($childcat->id, $catalogObj->id);

        if (empty($categoryIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $this->applyPartsTableFilter($query, $catalogCode, $categoryIds);
    }

    /**
     * Apply filter using dynamic parts tables
     */
    protected function applyPartsTableFilter(Builder $query, string $catalogCode, ?array $categoryIds): void
    {
        // Validate catalog code
        if (!preg_match('/^[A-Za-z0-9_]+$/', $catalogCode)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $partsTable = strtolower("parts_{$catalogCode}");
        $sectionPartsTable = strtolower("section_parts_{$catalogCode}");

        // Check tables exist
        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('catalogItem', function ($catalogItemQuery) use ($partsTable, $sectionPartsTable, $categoryIds) {
            $catalogItemQuery->whereExists(function ($exists) use ($partsTable, $sectionPartsTable, $categoryIds) {
                $exists->selectRaw(1)
                    ->from("{$partsTable} as p")
                    ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                    ->whereColumn('p.part_number', 'catalog_items.part_number');

                // section_parts.category_id links directly to newcategories (level 3 only)
                if ($categoryIds !== null) {
                    $exists->whereIn('sp.category_id', $categoryIds);
                }
            });
        });
    }

    /**
     * Apply merchant filter
     */
    public function applyMerchantFilter(Builder $query, Request $request): void
    {
        $merchantFilter = $this->normalizeArrayInput($request->merchant);

        if (empty($merchantFilter) && $request->filled('user')) {
            $merchantFilter = [(int) $request->user];
        }
        if (empty($merchantFilter) && $request->filled('store')) {
            $merchantFilter = [(int) $request->store];
        }

        if (!empty($merchantFilter)) {
            $query->whereIn('merchant_items.user_id', $merchantFilter);
        }
    }

    /**
     * Apply brand quality filter
     */
    public function applyBrandQualityFilter(Builder $query, Request $request): void
    {
        $brandQuality = $this->normalizeArrayInput($request->brand_quality);

        if (!empty($brandQuality)) {
            $query->whereIn('merchant_items.brand_quality_id', $brandQuality);
        }
    }

    /**
     * Apply price range filter
     */
    public function applyPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice, float $currencyValue = 1): void
    {
        if ($minPrice) {
            $query->where('merchant_items.price', '>=', $minPrice / $currencyValue);
        }
        if ($maxPrice) {
            $query->where('merchant_items.price', '<=', $maxPrice / $currencyValue);
        }
    }

    /**
     * Apply search filter
     */
    public function applySearchFilter(Builder $query, ?string $search): void
    {
        if (!empty($search)) {
            $query->whereHas('catalogItem', fn($pq) =>
                $pq->where('name', 'like', '%' . $search . '%')
                   ->orWhere('part_number', 'like', $search . '%')
            );
        }
    }

    /**
     * Apply discount filter
     */
    public function applyDiscountFilter(Builder $query, bool $hasDiscount): void
    {
        if ($hasDiscount) {
            $query->where('merchant_items.is_discount', 1)
                  ->where('merchant_items.discount_date', '>=', date('Y-m-d'));
        }
    }

    /**
     * Apply sorting
     */
    public function applySorting(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'date_desc' => $query->latest('merchant_items.id'),
            'date_asc' => $query->oldest('merchant_items.id'),
            'price_asc' => $query->orderBy('merchant_items.price', 'asc'),
            'price_desc' => $query->orderBy('merchant_items.price', 'desc'),
            'sku_asc' => $query->orderBy('catalog_items.part_number', 'asc'),
            'sku_desc' => $query->orderBy('catalog_items.part_number', 'desc'),
            default => $query->latest('merchant_items.id'),
        };
    }

    /**
     * Apply all filters
     */
    public function applyAllFilters(
        Builder $query,
        Request $request,
        $cat = null,
        $subcat = null,
        $childcat = null,
        float $currencyValue = 1,
        $catalog = null
    ): void {
        $this->applyFitmentFilters($query, $cat, $subcat, $childcat, $catalog);
        $this->applyMerchantFilter($query, $request);
        $this->applyBrandQualityFilter($query, $request);
        $this->applyPriceFilter(
            $query,
            $request->filled('min') ? (float) $request->min : null,
            $request->filled('max') ? (float) $request->max : null,
            $currencyValue
        );
        $this->applySearchFilter($query, $request->search);
        $this->applyDiscountFilter($query, $request->has('type'));
        $this->applySorting($query, $request->sort);
    }

    /**
     * Execute full category query with pagination
     */
    public function getCatalogItemResults(
        Request $request,
        ?string $catSlug = null,
        ?string $subcatSlug = null,
        ?string $childcatSlug = null,
        int $perPage = 12,
        float $currencyValue = 1,
        ?string $cat2Slug = null,
        ?string $cat3Slug = null
    ): array {
        $sidebarData = $this->getFilterSidebarData();
        $hierarchy = $this->resolveCategoryHierarchy($catSlug, $subcatSlug, $childcatSlug, $cat2Slug, $cat3Slug);
        $query = $this->buildBaseQuery();

        // Use deepest category for filtering
        $deepestCategory = $hierarchy['deepest'] ?? $hierarchy['childcat'];

        $this->applyAllFilters(
            $query,
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $deepestCategory,
            $currencyValue,
            $hierarchy['catalog'] ?? null
        );

        $paginator = $query->paginate($perPage)->withQueryString();
        $cards = $this->cardBuilder->buildCardsFromPaginator($paginator);

        $filterSummary = $this->buildFilterSummary(
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $deepestCategory,
            $sidebarData['merchants'],
            $sidebarData['brand_qualities']
        );

        return array_merge($sidebarData, $hierarchy, [
            'cards' => $cards,
            'prods' => $cards,
            'filterSummary' => $filterSummary,
        ]);
    }

    /**
     * Build filter summary for display
     */
    private function buildFilterSummary(
        Request $request,
        $cat,
        $subcat,
        $childcat,
        $allMerchants,
        $allBrandQualities
    ): array {
        $summary = [
            'hasFilters' => false,
            'category' => null,
            'subcategory' => null,
            'childcategory' => null,
            'merchants' => [],
            'brandQualities' => [],
        ];

        if ($cat) {
            $summary['category'] = $cat->localized_name ?? $cat->name;
            $summary['hasFilters'] = true;
        }
        if ($subcat) {
            $summary['subcategory'] = $subcat->localized_name ?? $subcat->name;
            $summary['hasFilters'] = true;
        }
        if ($childcat) {
            $summary['childcategory'] = $childcat->localized_name ?? $childcat->name;
            $summary['hasFilters'] = true;
        }

        $merchantIds = $this->normalizeArrayInput($request->merchant);
        if (!empty($merchantIds)) {
            foreach ($allMerchants as $merchant) {
                if (in_array($merchant->user_id, $merchantIds)) {
                    $summary['merchants'][] = $this->getLocalizedShopName($merchant);
                }
            }
            $summary['hasFilters'] = true;
        }

        $brandQualityIds = $this->normalizeArrayInput($request->brand_quality);
        if (!empty($brandQualityIds)) {
            foreach ($allBrandQualities as $bq) {
                if (in_array($bq->id, $brandQualityIds)) {
                    $summary['brandQualities'][] = $bq->localized_name ?? $bq->name_en;
                }
            }
            $summary['hasFilters'] = true;
        }

        return $summary;
    }

    /**
     * Normalize input to array
     */
    private function normalizeArrayInput($input): array
    {
        if (empty($input)) {
            return [];
        }
        if (is_array($input)) {
            return array_filter($input);
        }
        return [$input];
    }
}
