<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\CatalogItem;
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
     * Note: Branches are NOT loaded globally - they are fetched via AJAX per merchant
     */
    public function getFilterSidebarData(): array
    {
        return [
            // Brand has 'subs' accessor that returns catalogs
            // Catalog has 'childs' accessor that returns NewCategories Level 1
            'categories' => Brand::with('catalogs')->where('status', 1)->get(),
            'merchants' => $this->getActiveMerchants(),
            'quality_brands' => QualityBrand::active()->orderBy('name_en', 'asc')->get(),
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
     * Get branches for one or more merchants based on ACTUAL merchant_items data
     * Returns branches that have items for these merchants
     *
     * @param array $merchantIds Array of merchant IDs (user_id)
     * @return \Illuminate\Support\Collection
     */
    public function getBranchesForMerchants(array $merchantIds)
    {
        if (empty($merchantIds)) {
            return collect([]);
        }

        // Get distinct branches from merchant_items for selected merchants
        // Include merchant info so frontend can group by merchant if needed
        return DB::table('merchant_items')
            ->join('merchant_branches', 'merchant_branches.id', '=', 'merchant_items.merchant_branch_id')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->whereIn('merchant_items.user_id', $merchantIds)
            ->where('merchant_items.status', 1)
            ->where('merchant_branches.status', 1)
            ->groupBy(
                'merchant_items.merchant_branch_id',
                'merchant_branches.branch_name',
                'merchant_items.user_id',
                'users.shop_name'
            )
            ->orderBy('users.shop_name', 'asc')
            ->orderBy('merchant_branches.branch_name', 'asc')
            ->select(
                'merchant_items.merchant_branch_id as id',
                'merchant_branches.branch_name',
                'merchant_items.user_id as merchant_id',
                'users.shop_name as merchant_name'
            )
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
        // Note: catalogs.brand_id still exists (catalog belongs to brand)
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
     * Build CatalogItem-first query (NEW - one card per CatalogItem)
     *
     * Returns CatalogItem query with:
     * - offers_count: number of active merchant_items
     * - lowest_price: minimum price among offers
     * - NOTE: merchantItems eager loading is added separately via applyMerchantItemsEagerLoad()
     */
    public function buildCatalogItemQuery(): Builder
    {
        return CatalogItem::query()
            ->withOffersData();
        // NOTE: withBestOffer() is NOT called here anymore.
        // Use applyMerchantItemsEagerLoad() after filters are applied.
    }

    /**
     * Build filtered merchant items eager loading constraint
     *
     * This applies the SAME filters used in whereHas to the with() clause,
     * so the eager-loaded merchantItems match the filter criteria.
     *
     * @param Request $request HTTP request with filter parameters
     * @return \Closure The constraint function for with(['merchantItems' => ...])
     */
    protected function buildMerchantItemsEagerLoadConstraint(Request $request): \Closure
    {
        // Extract filter values
        $merchantFilter = $this->normalizeArrayInput($request->merchant);
        if (empty($merchantFilter) && $request->filled('user')) {
            $merchantFilter = [(int) $request->user];
        }
        if (empty($merchantFilter) && $request->filled('store')) {
            $merchantFilter = [(int) $request->store];
        }

        $branchFilter = $this->normalizeArrayInput($request->branch);
        $qualityBrandFilter = $this->normalizeArrayInput($request->quality_brand);

        // Get branch-merchant mapping if needed
        $branchMerchantMap = [];
        $branchesByMerchant = [];
        $merchantsWithoutBranches = [];

        if (!empty($branchFilter) && !empty($merchantFilter)) {
            $branchMerchantMap = $this->getBranchMerchantMapping($branchFilter);

            foreach ($branchFilter as $branchId) {
                $branchId = (int) $branchId;
                if (isset($branchMerchantMap[$branchId])) {
                    $merchantId = $branchMerchantMap[$branchId];
                    if (!isset($branchesByMerchant[$merchantId])) {
                        $branchesByMerchant[$merchantId] = [];
                    }
                    $branchesByMerchant[$merchantId][] = $branchId;
                }
            }

            $merchantsWithBranches = array_keys($branchesByMerchant);
            $merchantsWithoutBranches = array_diff(array_map('intval', $merchantFilter), $merchantsWithBranches);
        }

        return function ($q) use ($merchantFilter, $branchFilter, $qualityBrandFilter, $branchesByMerchant, $merchantsWithoutBranches) {
            $q->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                ->with([
                    'user:id,is_merchant,name,shop_name,shop_name_ar,email',
                    'qualityBrand:id,name_en,name_ar,logo',
                    'merchantBranch:id,warehouse_name,branch_name',
                ]);

            // Apply merchant filter to eager loaded items
            if (!empty($merchantFilter)) {
                $q->whereIn('user_id', $merchantFilter);
            }

            // Apply quality brand filter to eager loaded items
            if (!empty($qualityBrandFilter)) {
                $q->whereIn('quality_brand_id', $qualityBrandFilter);
            }

            // Apply branch filter with merchant context isolation
            if (!empty($branchesByMerchant) || !empty($merchantsWithoutBranches)) {
                $q->where(function ($subQ) use ($branchesByMerchant, $merchantsWithoutBranches) {
                    // For merchants WITH branch selections: show only selected branches
                    foreach ($branchesByMerchant as $merchantId => $branches) {
                        $subQ->orWhere(function ($innerQ) use ($merchantId, $branches) {
                            $innerQ->where('user_id', $merchantId)
                                ->whereIn('merchant_branch_id', $branches);
                        });
                    }

                    // For merchants WITHOUT branch selections: show all items
                    if (!empty($merchantsWithoutBranches)) {
                        $subQ->orWhereIn('user_id', $merchantsWithoutBranches);
                    }
                });
            }

            // Sort by stock (has stock first) then by price
            $q->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
                ->orderBy('price', 'asc');
        };
    }

    /**
     * Apply merchant items eager loading with filters
     *
     * Call this AFTER applying all filters to ensure the eager-loaded
     * merchantItems match the filter criteria.
     *
     * @param Builder $query The CatalogItem query
     * @param Request $request HTTP request with filter parameters
     */
    public function applyMerchantItemsEagerLoad(Builder $query, Request $request): void
    {
        $constraint = $this->buildMerchantItemsEagerLoadConstraint($request);

        $query->with(['merchantItems' => $constraint])
            ->with('fitments.brand')
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating');
    }

    /**
     * Apply fitment filter for CatalogItem query
     */
    public function applyCatalogItemFitmentFilters(Builder $query, $cat, $subcat, $childcat, $catalog = null): void
    {
        // No filters = show all
        if (!$cat && !$subcat && !$childcat) {
            return;
        }

        // Only Brand selected → filter via catalog_item_fitments
        if ($cat && !$subcat && !$childcat) {
            $query->whereHas('fitments', fn($f) => $f->where('brand_id', $cat->id));
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
            $this->applyCatalogItemPartsTableFilter($query, $catalogCode, null);
            return;
        }

        // NewCategory selected → get all descendants
        $categoryIds = $this->getDescendantIds($childcat->id, $catalogObj->id);

        if (empty($categoryIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $this->applyCatalogItemPartsTableFilter($query, $catalogCode, $categoryIds);
    }

    /**
     * Apply parts table filter for CatalogItem query
     */
    protected function applyCatalogItemPartsTableFilter(Builder $query, string $catalogCode, ?array $categoryIds): void
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

        $query->whereExists(function ($exists) use ($partsTable, $sectionPartsTable, $categoryIds) {
            $exists->selectRaw(1)
                ->from("{$partsTable} as p")
                ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                ->whereColumn('p.part_number', 'catalog_items.part_number');

            if ($categoryIds !== null) {
                $exists->whereIn('sp.category_id', $categoryIds);
            }
        });
    }

    /**
     * Apply merchant filter for CatalogItem query
     */
    public function applyCatalogItemMerchantFilter(Builder $query, Request $request): void
    {
        $merchantFilter = $this->normalizeArrayInput($request->merchant);

        if (empty($merchantFilter) && $request->filled('user')) {
            $merchantFilter = [(int) $request->user];
        }
        if (empty($merchantFilter) && $request->filled('store')) {
            $merchantFilter = [(int) $request->store];
        }

        if (!empty($merchantFilter)) {
            $query->whereHas('merchantItems', function ($q) use ($merchantFilter) {
                $q->where('status', 1)
                    ->whereIn('user_id', $merchantFilter)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            });
        }
    }

    /**
     * Apply branch filter for CatalogItem query
     */
    public function applyCatalogItemBranchFilter(Builder $query, Request $request): void
    {
        $branchFilter = $this->normalizeArrayInput($request->branch);
        $merchantFilter = $this->normalizeArrayInput($request->merchant);

        if (empty($merchantFilter) && $request->filled('user')) {
            $merchantFilter = [(int) $request->user];
        }
        if (empty($merchantFilter) && $request->filled('store')) {
            $merchantFilter = [(int) $request->store];
        }

        if (empty($branchFilter)) {
            return;
        }

        if (empty($merchantFilter)) {
            return;
        }

        $branchMerchantMap = $this->getBranchMerchantMapping($branchFilter);

        if (empty($branchMerchantMap)) {
            return;
        }

        $branchesByMerchant = [];
        foreach ($branchFilter as $branchId) {
            $branchId = (int) $branchId;
            if (isset($branchMerchantMap[$branchId])) {
                $merchantId = $branchMerchantMap[$branchId];
                if (!isset($branchesByMerchant[$merchantId])) {
                    $branchesByMerchant[$merchantId] = [];
                }
                $branchesByMerchant[$merchantId][] = $branchId;
            }
        }

        $merchantsWithBranches = array_keys($branchesByMerchant);
        $merchantsWithoutBranches = array_diff(array_map('intval', $merchantFilter), $merchantsWithBranches);

        $query->whereHas('merchantItems', function ($q) use ($branchesByMerchant, $merchantsWithoutBranches) {
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
    }

    /**
     * Get mapping of branch IDs to their merchant IDs
     *
     * @param array $branchIds Array of branch IDs
     * @return array [branch_id => merchant_id, ...]
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

    /**
     * Apply quality brand filter for CatalogItem query
     */
    public function applyCatalogItemQualityBrandFilter(Builder $query, Request $request): void
    {
        $qualityBrand = $this->normalizeArrayInput($request->quality_brand);

        if (!empty($qualityBrand)) {
            $query->whereHas('merchantItems', function ($q) use ($qualityBrand) {
                $q->where('status', 1)
                    ->whereIn('quality_brand_id', $qualityBrand)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            });
        }
    }

    /**
     * Apply price filter for CatalogItem query (uses lowest_price subquery)
     */
    public function applyCatalogItemPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice, float $currencyValue = 1): void
    {
        if ($minPrice) {
            $query->having('lowest_price', '>=', $minPrice / $currencyValue);
        }
        if ($maxPrice) {
            $query->having('lowest_price', '<=', $maxPrice / $currencyValue);
        }
    }

    /**
     * Apply search filter for CatalogItem query
     */
    public function applyCatalogItemSearchFilter(Builder $query, ?string $search): void
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('part_number', 'like', $search . '%');
            });
        }
    }

    /**
     * Apply discount filter for CatalogItem query
     */
    public function applyCatalogItemDiscountFilter(Builder $query, bool $hasDiscount): void
    {
        if ($hasDiscount) {
            $query->whereHas('merchantItems', function ($q) {
                $q->where('status', 1)
                    ->where('is_discount', 1)
                    ->where('discount_date', '>=', date('Y-m-d'))
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            });
        }
    }

    /**
     * Apply sorting for CatalogItem query
     */
    public function applyCatalogItemSorting(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('lowest_price', 'asc'),
            'price_desc' => $query->orderBy('lowest_price', 'desc'),
            'part_number' => $query->orderBy('catalog_items.part_number', 'asc'),
            'name_asc' => $this->applyNameSorting($query),
            default => $query->orderBy('lowest_price', 'asc'),
        };
    }

    /**
     * Apply name sorting based on current locale
     * Arabic: Show items with Arabic names first, then others
     * English: Show items with English names first, then others
     */
    protected function applyNameSorting(Builder $query): void
    {
        $isArabic = app()->getLocale() === 'ar';

        if ($isArabic) {
            // Arabic: items with label_ar first, then sort alphabetically
            $query->orderByRaw("CASE WHEN catalog_items.label_ar IS NOT NULL AND catalog_items.label_ar != '' THEN 0 ELSE 1 END ASC")
                  ->orderByRaw("COALESCE(NULLIF(catalog_items.label_ar, ''), NULLIF(catalog_items.label_en, ''), catalog_items.name) ASC");
        } else {
            // English: items with label_en first, then sort alphabetically
            $query->orderByRaw("CASE WHEN catalog_items.label_en IS NOT NULL AND catalog_items.label_en != '' THEN 0 ELSE 1 END ASC")
                  ->orderByRaw("COALESCE(NULLIF(catalog_items.label_en, ''), NULLIF(catalog_items.label_ar, ''), catalog_items.name) ASC");
        }
    }

    /**
     * Apply all filters for CatalogItem query (NEW)
     *
     * This applies:
     * 1. whereHas filters (to determine WHICH CatalogItems to show)
     * 2. Filtered eager loading (to load only MATCHING merchantItems for display)
     */
    public function applyCatalogItemFilters(
        Builder $query,
        Request $request,
        $cat = null,
        $subcat = null,
        $childcat = null,
        float $currencyValue = 1,
        $catalog = null
    ): void {
        // Apply whereHas filters (determines which CatalogItems to show)
        $this->applyCatalogItemFitmentFilters($query, $cat, $subcat, $childcat, $catalog);
        $this->applyCatalogItemMerchantFilter($query, $request);
        $this->applyCatalogItemBranchFilter($query, $request);
        $this->applyCatalogItemQualityBrandFilter($query, $request);
        $this->applyCatalogItemPriceFilter(
            $query,
            $request->filled('min') ? (float) $request->min : null,
            $request->filled('max') ? (float) $request->max : null,
            $currencyValue
        );
        $this->applyCatalogItemSearchFilter($query, $request->search);
        $this->applyCatalogItemDiscountFilter($query, $request->has('type'));
        $this->applyCatalogItemSorting($query, $request->sort);

        // Apply filtered eager loading (loads only matching merchantItems for display)
        $this->applyMerchantItemsEagerLoad($query, $request);
    }

    /**
     * Execute CatalogItem-first query with pagination (NEW - one card per CatalogItem)
     *
     * Returns CatalogItem models with:
     * - offers_count: number of active merchant_items
     * - lowest_price: minimum price among offers
     * - best merchant_item loaded for display
     */
    public function getCatalogItemFirstResults(
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

        // NEW: CatalogItem-first query
        $query = $this->buildCatalogItemQuery();

        // Use deepest category for filtering
        $deepestCategory = $hierarchy['deepest'] ?? $hierarchy['childcat'];

        // Apply CatalogItem-specific filters
        $this->applyCatalogItemFilters(
            $query,
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $deepestCategory,
            $currencyValue,
            $hierarchy['catalog'] ?? null
        );

        $paginator = $query->paginate($perPage)->withQueryString();

        // Build cards from CatalogItem models (not MerchantItem)
        $cards = $this->cardBuilder->buildCardsFromCatalogItemPaginator($paginator);

        $filterSummary = $this->buildFilterSummary(
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $deepestCategory,
            $sidebarData['merchants'],
            $sidebarData['quality_brands']
        );

        return array_merge($sidebarData, $hierarchy, [
            'cards' => $cards,
            'prods' => $cards,
            'filterSummary' => $filterSummary,
        ]);
    }

    /**
     * Get CatalogItem results for NewCategory tree navigation with FULL filter support
     *
     * This method combines:
     * - Recursive CTE category traversal (from NewCategoryTreeService)
     * - Full filter integration (merchant, branch, quality_brand, price, sort)
     *
     * @param Request $request HTTP request with filter parameters
     * @param int $catalogId The catalog ID
     * @param string $catalogCode The catalog code for dynamic tables
     * @param array $categoryIds Array of category IDs (already resolved with descendants)
     * @param int $perPage Items per page
     * @param float $currencyValue Currency conversion value
     * @return array Data for view including cards, sidebar data, pagination
     */
    public function getCatalogItemsFromCategoryTree(
        Request $request,
        int $catalogId,
        string $catalogCode,
        array $categoryIds,
        int $perPage = 12,
        float $currencyValue = 1
    ): array {
        // Get sidebar filter data
        $sidebarData = $this->getFilterSidebarData();

        // Validate catalog code
        if (!preg_match('/^[A-Za-z0-9_]+$/', $catalogCode)) {
            return array_merge($sidebarData, [
                'cards' => collect(),
                'prods' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
                'filterSummary' => ['hasFilters' => false],
            ]);
        }

        $partsTable = strtolower("parts_{$catalogCode}");
        $sectionPartsTable = strtolower("section_parts_{$catalogCode}");

        // Check tables exist
        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            return array_merge($sidebarData, [
                'cards' => collect(),
                'prods' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
                'filterSummary' => ['hasFilters' => false],
            ]);
        }

        // Build CatalogItem-first query
        $query = $this->buildCatalogItemQuery();

        // Apply category tree filter (parts table lookup)
        if (!empty($categoryIds)) {
            $query->whereExists(function ($exists) use ($partsTable, $sectionPartsTable, $categoryIds) {
                $exists->selectRaw(1)
                    ->from("{$partsTable} as p")
                    ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                    ->whereColumn('p.part_number', 'catalog_items.part_number')
                    ->whereIn('sp.category_id', $categoryIds);
            });
        }

        // Apply ALL filters (merchant, branch, quality_brand, price, search, sort)
        $this->applyCatalogItemMerchantFilter($query, $request);
        $this->applyCatalogItemBranchFilter($query, $request);
        $this->applyCatalogItemQualityBrandFilter($query, $request);
        $this->applyCatalogItemPriceFilter(
            $query,
            $request->filled('min') ? (float) $request->min : null,
            $request->filled('max') ? (float) $request->max : null,
            $currencyValue
        );
        $this->applyCatalogItemSearchFilter($query, $request->search);
        $this->applyCatalogItemSorting($query, $request->sort);

        // Apply filtered eager loading (loads only matching merchantItems for display)
        $this->applyMerchantItemsEagerLoad($query, $request);

        // Paginate
        $paginator = $query->paginate($perPage)->withQueryString();

        // Build cards from CatalogItem models
        $cards = $this->cardBuilder->buildCardsFromCatalogItemPaginator($paginator);

        // Build filter summary
        $filterSummary = $this->buildCategoryTreeFilterSummary(
            $request,
            $sidebarData['merchants'],
            $sidebarData['quality_brands']
        );

        return array_merge($sidebarData, [
            'cards' => $cards,
            'prods' => $cards,
            'items' => $cards, // Alias for newCategory view compatibility
            'filterSummary' => $filterSummary,
        ]);
    }

    /**
     * Build filter summary for category tree view
     */
    private function buildCategoryTreeFilterSummary(
        Request $request,
        $allMerchants,
        $allQualityBrands
    ): array {
        $summary = [
            'hasFilters' => false,
            'merchants' => [],
            'branches' => [],
            'qualityBrands' => [],
        ];

        $merchantIds = $this->normalizeArrayInput($request->merchant);
        if (!empty($merchantIds)) {
            foreach ($allMerchants as $merchant) {
                if (in_array($merchant->user_id, $merchantIds)) {
                    $summary['merchants'][] = $this->getLocalizedShopName($merchant);
                }
            }
            $summary['hasFilters'] = true;
        }

        $branchIds = $this->normalizeArrayInput($request->branch);
        if (!empty($branchIds)) {
            $branchNames = DB::table('merchant_branches')
                ->whereIn('id', $branchIds)
                ->pluck('branch_name')
                ->toArray();
            $summary['branches'] = $branchNames;
            if (!empty($branchNames)) {
                $summary['hasFilters'] = true;
            }
        }

        $qualityBrandIds = $this->normalizeArrayInput($request->quality_brand);
        if (!empty($qualityBrandIds)) {
            foreach ($allQualityBrands as $qb) {
                if (in_array($qb->id, $qualityBrandIds)) {
                    $summary['qualityBrands'][] = $qb->localized_name ?? $qb->name_en;
                }
            }
            $summary['hasFilters'] = true;
        }

        return $summary;
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
        $allQualityBrands
    ): array {
        $summary = [
            'hasFilters' => false,
            'category' => null,
            'subcategory' => null,
            'childcategory' => null,
            'merchants' => [],
            'branches' => [],
            'qualityBrands' => [],
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

        // Branches - fetch names when filter is applied
        $branchIds = $this->normalizeArrayInput($request->branch);
        if (!empty($branchIds)) {
            // Get branch names directly by IDs
            $branchNames = DB::table('merchant_branches')
                ->whereIn('id', $branchIds)
                ->pluck('branch_name')
                ->toArray();
            $summary['branches'] = $branchNames;
            if (!empty($branchNames)) {
                $summary['hasFilters'] = true;
            }
        }

        $qualityBrandIds = $this->normalizeArrayInput($request->quality_brand);
        if (!empty($qualityBrandIds)) {
            foreach ($allQualityBrands as $qb) {
                if (in_array($qb->id, $qualityBrandIds)) {
                    $summary['qualityBrands'][] = $qb->localized_name ?? $qb->name_en;
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
