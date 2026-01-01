<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Report;
use App\Models\Subcategory;
use App\Services\CatalogItemFilterService;
use App\Services\NewCategoryTreeService;
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
        $data = $this->filterService->getCatalogItemResults(
            $request,
            null,  // no category
            null,  // no subcategory
            null,  // no childcategory
            $perPage,
            $currValue
        );

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.catalog-items', $data);
    }

    // -------------------------------- CATEGORY SECTION ----------------------------------------

    /**
     * Unified 5-level category route
     * Structure: /category/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
     *
     * @param Request $request
     * @param string|null $slug Brand slug (e.g., "nissan")
     * @param string|null $slug1 Catalog slug (e.g., "safari-patrol-1997")
     * @param string|null $slug2 TreeCategory L1 slug (e.g., "engine")
     * @param string|null $slug3 TreeCategory L2 slug (e.g., "cooling")
     * @param string|null $slug4 TreeCategory L3 slug (e.g., "radiator")
     */
    public function category(Request $request, $slug = null, $slug1 = null, $slug2 = null, $slug3 = null, $slug4 = null)
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
        $data = $this->filterService->getCatalogItemResults(
            $request,
            $slug,       // Brand slug
            $slug1,      // Catalog slug
            $slug2,      // TreeCategory L1 slug
            $perPage,
            $currValue,
            $slug3,      // TreeCategory L2 slug
            $slug4       // TreeCategory L3 slug
        );

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
        $page = max(1, (int)$request->get('page', 1));

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
            $categoryIds = $this->categoryTreeService->getDescendantIds(
                $selectedCategory->id,
                $catalog->id
            );
        }

        // Get parts with merchant_items only
        $items = $this->categoryTreeService->getPartsWithMerchantItems(
            $categoryIds,
            $catalog->code,
            $perPage,
            $page
        );

        // Build category tree for sidebar
        $categoryTree = $this->categoryTreeService->buildCategoryTree($catalog->id, $brand->id);

        // Prepare data for view
        $data = [
            'brand' => $brand,
            'catalog' => $catalog,
            'categoryTree' => $categoryTree,
            'selectedCategory' => $selectedCategory,
            'breadcrumb' => $breadcrumb,
            'hierarchy' => $hierarchy,
            'items' => $items,
            'cat1_slug' => $cat1,
            'cat2_slug' => $cat2,
            'cat3_slug' => $cat3,
            'brand_slug' => $brand_slug,
            'catalog_slug' => $catalog_slug,
        ];

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.new-category', $data);
        }

        return view('frontend.new-catalog-items', $data);
    }

    // -------------------------------- NEW CATEGORY TREE SECTION ENDS ----------------------------------------

    public function getsubs(Request $request)
    {
        $category = Category::where('slug', $request->category)->firstOrFail();
        $subcategories = Subcategory::where('category_id', $category->id)->get();
        return $subcategories;
    }

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

        $brand = \App\Models\Brand::where('slug', $brandSlug)->where('status', 1)->first();
        if (!$brand) {
            return response()->json([]);
        }

        $catalogs = \App\Models\Catalog::where('brand_id', $brand->id)
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

        $catalog = \App\Models\Catalog::where('slug', $catalogSlug)->first();
        if (!$catalog) {
            return response()->json([]);
        }

        $query = \App\Models\TreeCategory::where('catalog_id', $catalog->id)
            ->where('level', $level)
            ->orderBy('label_en');

        if ($level === 1) {
            // Level 1: no parent filter
        } else {
            // Level 2+: need parent
            if (!$parentSlug) {
                return response()->json([]);
            }
            $parent = \App\Models\TreeCategory::where('catalog_id', $catalog->id)
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

        $data = new Report;
        // Security: Only allow specific fields, set user_id from authenticated user
        $data->product_id = $request->input('product_id');
        $data->merchant_product_id = $request->input('merchant_product_id');
        $data->title = $request->input('title');
        $data->note = $request->input('note');
        $data->user_id = auth()->id(); // Set from authenticated user, not from request
        $data->save();

        return back()->with('success', 'Report has been sent successfully.');

    }
}
