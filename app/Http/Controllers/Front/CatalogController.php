<?php

namespace App\Http\Controllers\Front;

use App\Domain\Catalog\Models\AbuseFlag;
use App\Domain\Catalog\Services\CatalogItemFilterService;
use App\Domain\Catalog\Services\NewCategoryTreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CatalogController extends FrontBaseController
{
    public function __construct(
        private CatalogItemFilterService $filterService,
        private NewCategoryTreeService $categoryTreeService
    ) {
        parent::__construct();
    }

    // CATEGORIES SECTION

    public function categories(Request $request)
    {
        // Handle view mode
        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        $perPage = $this->gs->page_count ?? 12;
        $currValue = $this->curr->value ?? 1;

        // Use service to get all data (no category selected = ALL catalog items)
        $data = $this->filterService->getCatalogItemFirstResults(
            $request,
            null,
            null,
            null,
            $perPage,
            $currValue
        );

        // Build category selector data (reads URL segments)
        $categorySelector = $this->categoryTreeService->buildCategorySelectorData(
            $data['brands'] ?? collect(),
            $request->segment(2), // brand
            $request->segment(3), // catalog
            $request->segment(4), // cat1
            $request->segment(5), // cat2
            $request->segment(6)  // cat3
        );
        $data['categorySelector'] = $categorySelector;

        // PRE-COMPUTED: Extract category selector variables for view (DATA_FLOW_POLICY)
        $data['currentBrandSlug'] = $categorySelector['brandSlug'] ?? null;
        $data['currentCatalogSlug'] = $categorySelector['catalogSlug'] ?? null;
        $data['currentLevel1Slug'] = $categorySelector['level1Slug'] ?? null;
        $data['currentLevel2Slug'] = $categorySelector['level2Slug'] ?? null;
        $data['currentLevel3Slug'] = $categorySelector['level3Slug'] ?? null;
        $data['brandCatalogs'] = $categorySelector['brandCatalogs'] ?? collect();
        $data['catalogLevel1'] = $categorySelector['catalogLevel1'] ?? collect();
        $data['level1Level2'] = $categorySelector['level1Level2'] ?? collect();
        $data['level2Level3'] = $categorySelector['level2Level3'] ?? collect();

        // PRE-COMPUTED: View mode and sort (DATA_FLOW_POLICY - no @php in view)
        $viewCheck = $request->input('view_check');
        $data['viewMode'] = ($viewCheck == null || $viewCheck == 'list-view') ? 'list-view' : 'grid-view';
        $data['currentSort'] = $request->input('sort', 'price_asc');

        // PRE-COMPUTED: AJAX view specific variables (DATA_FLOW_POLICY)
        $data['view'] = $request->input('view_check', session('view', 'grid-view'));
        $data['catalogItems'] = $data['cards'] ?? $data['prods'] ?? collect();
        $data['total'] = isset($data['prods']) ? $data['prods']->total() : 0;

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.catalog-items', $data);
    }

    // -------------------------------- CATALOG SECTION ----------------------------------------

    /**
     * Unified 5-level catalog route
     * Structure: /catalog/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
     *
     * @param Request $request
     * @param string|null $brand Brand slug (e.g., "nissan")
     * @param string|null $catalog Catalog slug (e.g., "safari-patrol-1997")
     * @param string|null $cat1 NewCategory L1 slug (e.g., "engine")
     * @param string|null $cat2 NewCategory L2 slug (e.g., "cooling")
     * @param string|null $cat3 NewCategory L3 slug (e.g., "radiator")
     */
    public function catalog(Request $request, $brand = null, $catalog = null, $cat1 = null, $cat2 = null, $cat3 = null)
    {
        // Handle view mode
        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        // Get pagination settings
        $pageby = $request->pageby && $request->pageby !== 'undefined' && is_numeric($request->pageby) ? (int)$request->pageby : null;
        $perPage = $pageby ?? $this->gs->page_count ?? 12;
        $currValue = $this->curr->value ?? 1;

        // Use service to get all data with filters applied
        // Pass all 5 levels: brand, catalog, cat1, cat2, cat3
        $data = $this->filterService->getCatalogItemFirstResults(
            $request,
            $brand,
            $catalog,
            $cat1,
            $perPage,
            $currValue,
            $cat2,
            $cat3
        );

        // Build category selector data from route parameters
        $categorySelector = $this->categoryTreeService->buildCategorySelectorData(
            $data['brands'] ?? collect(),
            $brand,
            $catalog,
            $cat1,
            $cat2,
            $cat3
        );
        $data['categorySelector'] = $categorySelector;

        // PRE-COMPUTED: Extract category selector variables for view (DATA_FLOW_POLICY)
        $data['currentBrandSlug'] = $categorySelector['brandSlug'] ?? null;
        $data['currentCatalogSlug'] = $categorySelector['catalogSlug'] ?? null;
        $data['currentLevel1Slug'] = $categorySelector['level1Slug'] ?? null;
        $data['currentLevel2Slug'] = $categorySelector['level2Slug'] ?? null;
        $data['currentLevel3Slug'] = $categorySelector['level3Slug'] ?? null;
        $data['brandCatalogs'] = $categorySelector['brandCatalogs'] ?? collect();
        $data['catalogLevel1'] = $categorySelector['catalogLevel1'] ?? collect();
        $data['level1Level2'] = $categorySelector['level1Level2'] ?? collect();
        $data['level2Level3'] = $categorySelector['level2Level3'] ?? collect();

        // PRE-COMPUTED: View mode and sort (DATA_FLOW_POLICY - no @php in view)
        $viewCheck = $request->input('view_check');
        $data['viewMode'] = ($viewCheck == null || $viewCheck == 'list-view') ? 'list-view' : 'grid-view';
        $data['currentSort'] = $request->input('sort', 'price_asc');

        // PRE-COMPUTED: AJAX view specific variables (DATA_FLOW_POLICY)
        $data['view'] = $request->input('view_check', session('view', 'grid-view'));
        $data['catalogItems'] = $data['cards'] ?? $data['prods'] ?? collect();
        $data['total'] = isset($data['prods']) ? $data['prods']->total() : 0;

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.catalog-items', $data);
    }

    // -------------------------------- NEW CATEGORY TREE SECTION ----------------------------------------

    /**
     * Unified catalog category tree with recursive descendant traversal
     * Route: /catalog/{brand_slug}/{catalog_slug}/category/{cat1?}/{cat2?}/{cat3?}
     *
     * Shows all parts from selected category AND all descendant categories
     * Only shows parts that have merchant_items (available for sale)
     * NOW WITH FULL FILTER SUPPORT (merchant, branch, quality_brand, price, sort)
     */
    public function newCategory(
        Request $request,
        string $brand_slug,
        string $catalog_slug,
        ?string $cat1 = null,
        ?string $cat2 = null,
        ?string $cat3 = null
    ) {
        // Handle view mode
        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        // Pagination settings
        $pageby = $request->pageby && $request->pageby !== 'undefined' && is_numeric($request->pageby)
            ? (int)$request->pageby
            : null;
        $perPage = $pageby ?? $this->gs->page_count ?? 12;
        $currValue = $this->curr->value ?? 1;

        // Resolve brand and catalog from URL slugs
        $resolved = $this->categoryTreeService->resolveBrandAndCatalog($brand_slug, $catalog_slug);
        $brand = $resolved['brand'];
        $catalog = $resolved['catalog'];

        if (!$brand || !$catalog) {
            abort(404, __('Catalog not found'));
        }

        // Resolve category hierarchy from newcategories
        $hierarchy = $this->categoryTreeService->resolveCategoryHierarchy(
            $catalog->id,
            $cat1,
            $cat2,
            $cat3
        );

        $selectedCategory = $hierarchy['deepest'];
        $breadcrumb = collect();

        if ($selectedCategory) {
            $breadcrumb = $this->categoryTreeService->getBreadcrumb($selectedCategory);
        }

        // Get all descendant category IDs (includes selected category)
        $categoryIds = [];
        if ($selectedCategory) {
            $categoryIds = $this->filterService->getDescendantIds(
                $selectedCategory->id,
                $catalog->id
            );
        }

        // Get CatalogItems with FULL FILTER SUPPORT via CatalogItemFilterService
        $filterResults = $this->filterService->getCatalogItemsFromCategoryTree(
            $request,
            $catalog->id,
            $catalog->code,
            $categoryIds,
            $perPage,
            $currValue
        );

        // Build category tree for sidebar (with pre-computed URLs - DATA_FLOW_POLICY)
        $categoryTree = $this->categoryTreeService->buildCategoryTree($catalog->id, $brand->id, $brand_slug, $catalog_slug);

        // PRE-COMPUTED: Breadcrumb with URLs (DATA_FLOW_POLICY - no @php in view)
        $breadcrumbWithUrls = $this->buildBreadcrumbWithUrls($breadcrumb, $brand_slug, $catalog_slug);

        // PRE-COMPUTED: View mode and sort (DATA_FLOW_POLICY - no @php in view)
        $viewCheck = $request->input('view_check');
        $viewMode = ($viewCheck == null || $viewCheck == 'list-view') ? 'list-view' : 'grid-view';
        $view = $request->input('view_check', 'list-view'); // Alias for AJAX partial
        $currentSort = $request->input('sort', 'price_asc');

        // Prepare data for view (merge filter results with category tree data)
        $data = array_merge($filterResults, [
            'brand' => $brand,
            'catalog' => $catalog,
            'categoryTree' => $categoryTree,
            'selectedCategory' => $selectedCategory,
            'breadcrumb' => $breadcrumb,
            'breadcrumbWithUrls' => $breadcrumbWithUrls,
            'hierarchy' => $hierarchy,
            'cat1_slug' => $cat1,
            'cat2_slug' => $cat2,
            'cat3_slug' => $cat3,
            'brand_slug' => $brand_slug,
            'catalog_slug' => $catalog_slug,
            'viewMode' => $viewMode,
            'view' => $view, // For AJAX partial (DATA_FLOW_POLICY)
            'currentSort' => $currentSort,
        ]);

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.new-category', $data);
        }

        return view('frontend.new-catalog-items', $data);
    }

    // -------------------------------- NEW CATEGORY TREE SECTION ENDS ----------------------------------------

    // =========================================================
    // AJAX API - جلب البيانات عند الطلب (لتخفيف الحمل)
    // =========================================================

    /**
     * Get catalogs for a brand (AJAX)
     */
    public function getCatalogs(Request $request)
    {
        $brandSlug = $request->input('brand');
        if (!$brandSlug) {
            return response()->json([]);
        }

        $brand = \App\Domain\Catalog\Models\Brand::where('slug', $brandSlug)->where('status', 1)->first();
        if (!$brand) {
            return response()->json([]);
        }

        $catalogs = \App\Domain\Catalog\Models\Catalog::where('brand_id', $brand->id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'name_ar']);

        return response()->json($catalogs->map(fn($c) => [
            'slug' => $c->slug,
            'name' => $c->localized_name,
        ]));
    }

    /**
     * Get TreeCategories for a level (AJAX)
     */
    public function getTreeCategories(Request $request)
    {
        $catalogSlug = $request->input('catalog');
        $parentSlug = $request->input('parent'); // null for level 1
        $level = (int) $request->input('level', 1);

        if (!$catalogSlug) {
            return response()->json([]);
        }

        $catalog = \App\Domain\Catalog\Models\Catalog::where('slug', $catalogSlug)->first();
        if (!$catalog) {
            return response()->json([]);
        }

        $query = \App\Domain\Catalog\Models\NewCategory::where('catalog_id', $catalog->id)
            ->where('level', $level)
            ->orderBy('label_en');

        if ($level === 1) {
            // Level 1: no parent filter
        } else {
            // Level 2+: need parent
            if (!$parentSlug) {
                return response()->json([]);
            }
            $parent = \App\Domain\Catalog\Models\NewCategory::where('catalog_id', $catalog->id)
                ->where('slug', $parentSlug)
                ->where('level', $level - 1)
                ->first();
            if (!$parent) {
                return response()->json([]);
            }
            $query->where('parent_id', $parent->id);
        }

        $categories = $query->get(['id', 'slug', 'label_en', 'label_ar']);

        return response()->json($categories->map(fn($c) => [
            'slug' => $c->slug,
            'name' => $c->localized_name,
        ]));
    }

    /**
     * Get branches for one or more merchants (AJAX endpoint)
     * Accepts: merchant_ids[] (array) or merchant_id (single)
     * Returns branches grouped by merchant for multi-merchant selection
     */
    public function getMerchantBranches(Request $request)
    {
        // Accept both array (merchant_ids[]) and single (merchant_id) formats
        $merchantIds = $request->input('merchant_ids', []);

        // Fallback to single merchant_id for backward compatibility
        if (empty($merchantIds) && $request->filled('merchant_id')) {
            $merchantIds = [(int) $request->input('merchant_id')];
        }

        // Normalize to array of integers
        $merchantIds = array_filter(array_map('intval', (array) $merchantIds));

        if (empty($merchantIds)) {
            return response()->json([]);
        }

        // Get branches for all selected merchants
        $branches = $this->filterService->getBranchesForMerchants($merchantIds);

        // Return with merchant info for grouping in frontend
        return response()->json($branches->map(fn($b) => [
            'id' => $b->id,
            'name' => $b->branch_name,
            'merchant_id' => $b->merchant_id,
            'merchant_name' => $b->merchant_name,
        ]));
    }

    public function report(Request $request)
    {

        //--- Validation Section
        $rules = [
            'note' => 'max:400',
        ];
        $customs = [
            'note.max' => 'Note Must Be Less Than 400 Characters.',
        ];
        
        $request->validate($rules, $customs);

        $data = new AbuseFlag;
        // Security: Only allow specific fields, set user_id from authenticated user
        $data->catalog_item_id = $request->input('catalog_item_id');
        $data->merchant_item_id = $request->input('merchant_item_id');
        $data->name = $request->input('name');
        $data->note = $request->input('note');
        $data->user_id = auth()->id(); // Set from authenticated user, not from request
        $data->save();

        return back()->with('success', 'Report has been sent successfully.');

    }

    /**
     * Build breadcrumb array with pre-computed URLs (DATA_FLOW_POLICY)
     * Handles cumulative URL building for category hierarchy
     */
    private function buildBreadcrumbWithUrls($breadcrumb, string $brand_slug, string $catalog_slug): array
    {
        $result = [];
        $cat1Slug = null;
        $cat2Slug = null;
        $isArabic = app()->getLocale() === 'ar';

        foreach ($breadcrumb as $crumb) {
            $params = ['brand_slug' => $brand_slug, 'catalog_slug' => $catalog_slug];

            if ($crumb->level == 1) {
                $cat1Slug = $crumb->slug;
                $params['cat1'] = $crumb->slug;
            } elseif ($crumb->level == 2) {
                $cat2Slug = $crumb->slug;
                $params['cat1'] = $cat1Slug;
                $params['cat2'] = $crumb->slug;
            } elseif ($crumb->level == 3) {
                $params['cat1'] = $cat1Slug;
                $params['cat2'] = $cat2Slug;
                $params['cat3'] = $crumb->slug;
            }

            $result[] = [
                'label' => $isArabic ? ($crumb->label_ar ?: $crumb->label_en) : $crumb->label_en,
                'url' => route('front.catalog.category', $params),
                'level' => $crumb->level,
            ];
        }

        return $result;
    }
}
