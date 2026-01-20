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
     * Legacy method for single merchant - calls the new multi-merchant method
     * @deprecated Use getBranchesForMerchants() instead
     */
    public function getBranchesForMerchant(int $merchantId)
    {
        return $this->getBranchesForMerchants([$merchantId]);
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

        // Only Brand selected → filter via catalog_item_fitments
        if ($cat && !$subcat && !$childcat) {
            $query->whereHas('catalogItem.fitments', fn($f) => $f->where('brand_id', $cat->id));
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
     * Apply branch filter with merchant context isolation
     *
     * Key behavior:
     * - Branch selections only affect their own merchant
     * - When Merchant A has branches selected and Merchant B is added,
     *   Merchant B shows ALL items (not limited by A's branch selection)
     * - AND between different filter groups, OR within same group
     */
    public function applyBranchFilter(Builder $query, Request $request): void
    {
        $branchFilter = $this->normalizeArrayInput($request->branch);
        $merchantFilter = $this->normalizeArrayInput($request->merchant);

        // Fallback to user/store param for single merchant
        if (empty($merchantFilter) && $request->filled('user')) {
            $merchantFilter = [(int) $request->user];
        }
        if (empty($merchantFilter) && $request->filled('store')) {
            $merchantFilter = [(int) $request->store];
        }

        // If no branches selected, no branch filter needed
        if (empty($branchFilter)) {
            return;
        }

        // If no merchants selected, branches have no context
        if (empty($merchantFilter)) {
            return;
        }

        // Get which merchant each selected branch belongs to
        $branchMerchantMap = $this->getBranchMerchantMapping($branchFilter);

        if (empty($branchMerchantMap)) {
            return;
        }

        // Group branches by their merchant
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

        // Find merchants that have NO branches selected (they show ALL items)
        $merchantsWithBranches = array_keys($branchesByMerchant);
        $merchantsWithoutBranches = array_diff(array_map('intval', $merchantFilter), $merchantsWithBranches);

        // Build the context-aware filter:
        // - Merchants WITH branch selections: filter to those specific branches
        // - Merchants WITHOUT branch selections: show all their items
        $query->where(function ($q) use ($branchesByMerchant, $merchantsWithoutBranches) {
            // For merchants WITH branch selections: show only selected branches
            foreach ($branchesByMerchant as $merchantId => $branches) {
                $q->orWhere(function ($subQ) use ($merchantId, $branches) {
                    $subQ->where('merchant_items.user_id', $merchantId)
                         ->whereIn('merchant_items.merchant_branch_id', $branches);
                });
            }

            // For merchants WITHOUT branch selections: show all items
            if (!empty($merchantsWithoutBranches)) {
                $q->orWhereIn('merchant_items.user_id', $merchantsWithoutBranches);
            }
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

        // Query merchant_items to find which merchant owns each branch
        // Each branch belongs to one merchant (via merchant_items.merchant_branch_id + user_id)
        $results = DB::table('merchant_items')
            ->whereIn('merchant_branch_id', $branchIds)
            ->where('status', 1)
            ->groupBy('merchant_branch_id', 'user_id')
            ->select('merchant_branch_id', 'user_id')
            ->get();

        $mapping = [];
        foreach ($results as $row) {
            $mapping[$row->merchant_branch_id] = $row->user_id;
        }

        return $mapping;
    }

    /**
     * Apply quality brand filter
     */
    public function applyQualityBrandFilter(Builder $query, Request $request): void
    {
        $qualityBrand = $this->normalizeArrayInput($request->quality_brand);

        if (!empty($qualityBrand)) {
            $query->whereIn('merchant_items.quality_brand_id', $qualityBrand);
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
        $this->applyBranchFilter($query, $request);
        $this->applyQualityBrandFilter($query, $request);
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
            $sidebarData['quality_brands']
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
