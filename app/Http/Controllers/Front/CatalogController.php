<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\QualityBrand;
use App\Models\Report;
use App\Models\Subcategory;
use App\Services\ProductCardDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CatalogController extends FrontBaseController
{
    public function __construct(
        private ProductCardDataBuilder $cardBuilder
    ) {
        parent::__construct();
    }

    // CATEGORIES SECTION

    public function categories()
    {
        $categories = Category::with('subs.childs')->where('status', 1)->get();

        // Get distinct vendors with shop names for the filter
        $vendors = MerchantProduct::select('merchant_products.user_id')
            ->join('users', 'users.id', '=', 'merchant_products.user_id')
            ->where('merchant_products.status', 1)
            ->where('users.is_vendor', 2)
            ->groupBy('merchant_products.user_id')
            ->selectRaw('merchant_products.user_id, users.shop_name')
            ->orderBy('users.shop_name', 'asc')
            ->get();

        // Get active brand qualities for the filter
        $brand_qualities = QualityBrand::active()->orderBy('name_en', 'asc')->get();

        // Retrieve latest products for sidebar (limited to 5)
        $latest_products = Product::with('brand')
            ->withBestMerchant()
            ->whereLatest(1)
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                    ->whereHas('user', fn($u) => $u->where('is_vendor', 2));
            })
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->take(5)
            ->get();

        // Build query for merchant products with eager loading
        $query = MerchantProduct::query()
            ->where('merchant_products.status', 1)
            ->where('merchant_products.stock', '>=', 1)
            ->whereHas('user', fn($u) => $u->where('is_vendor', 2))
            ->latest('merchant_products.id');

        // Apply eager loading via builder
        $this->cardBuilder->applyMerchantProductEagerLoading($query);

        // Paginate - only fetches page_count rows from DB
        $perPage = $this->gs->page_count ?? 12;
        $paginator = $query->paginate($perPage)->withQueryString();

        // Build DTOs only for the current page items
        $cards = $this->cardBuilder->buildCardsFromPaginator($paginator);

        // Keep prods for backward compatibility
        $prods = $cards;

        return view('frontend.products', compact('categories', 'latest_products', 'prods', 'cards', 'vendors', 'brand_qualities'));
    }

    // -------------------------------- CATEGORY SECTION ----------------------------------------

    public function category(Request $request, $slug = null, $slug1 = null, $slug2 = null, $slug3 = null)
    {
        $data['categories'] = Category::with('subs.childs')->where('status', 1)->get();

        // Get distinct vendors with shop names for the dropdown
        $data['vendors'] = MerchantProduct::select('merchant_products.user_id')
            ->join('users', 'users.id', '=', 'merchant_products.user_id')
            ->where('merchant_products.status', 1)
            ->where('users.is_vendor', 2)
            ->groupBy('merchant_products.user_id')
            ->selectRaw('merchant_products.user_id, users.shop_name')
            ->orderBy('users.shop_name', 'asc')
            ->get();

        // Get active brand qualities for the filter
        $data['brand_qualities'] = QualityBrand::active()->orderBy('name_en', 'asc')->get();

        if ($request->view_check) {
            Session::put('view', $request->view_check);
        }

        $cat = null;
        $subcat = null;
        $childcat = null;
        $minprice = $request->min;
        $maxprice = $request->max;
        $sort = $request->sort;
        $search = $request->search;
        $pageby = $request->pageby && $request->pageby !== 'undefined' && is_numeric($request->pageby) ? (int)$request->pageby : null;
        $perPage = $pageby ?? $this->gs->page_count ?? 12;

        $currValue = $this->curr->value ?? 1;
        $minprice = $minprice ? ($minprice / $currValue) : null;
        $maxprice = $maxprice ? ($maxprice / $currValue) : null;
        $type = $request->has('type') ?? '';
        $brandQuality = (array) $request->brand_quality;
        $quality = $request->quality;

        if (!empty($slug)) {
            $cat = Category::where('slug', $slug)->firstOrFail();
            $data['cat'] = $cat;
        }

        if (!empty($slug1)) {
            $subcat = Subcategory::where('slug', $slug1)->firstOrFail();
            $data['subcat'] = $subcat;
        }
        if (!empty($slug2) && $subcat) {
            $childcat = Childcategory::where('slug', $slug2)
                ->where('subcategory_id', $subcat->id)
                ->firstOrFail();
            $data['childcat'] = $childcat;
        }

        // Retrieve latest products for sidebar (limited to 5)
        $data['latest_products'] = Product::with('brand')
            ->withBestMerchant()
            ->whereLatest(1)
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                    ->whereHas('user', fn($u) => $u->where('is_vendor', 2));
            })
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->take(5)
            ->get();

        // Build query for merchant products
        $query = MerchantProduct::query()
            ->leftJoin('products', 'products.id', '=', 'merchant_products.product_id')
            ->select('merchant_products.*')
            ->where('merchant_products.status', 1)
            ->where('merchant_products.stock', '>=', 1)
            ->whereHas('user', fn($u) => $u->where('is_vendor', 2));

        // Apply eager loading via builder
        $this->cardBuilder->applyMerchantProductEagerLoading($query);

        // Filter by product_fitments (vehicle tree source of truth)
        $query->when($cat, fn($q) => $q->whereHas('product', fn($pq) => $pq->whereExists(fn($e) =>
            $e->selectRaw(1)->from('product_fitments')
              ->whereColumn('product_fitments.product_id', 'products.id')
              ->where('product_fitments.category_id', $cat->id)
        )));

        $query->when($subcat, fn($q) => $q->whereHas('product', fn($pq) => $pq->whereExists(fn($e) =>
            $e->selectRaw(1)->from('product_fitments')
              ->whereColumn('product_fitments.product_id', 'products.id')
              ->where('product_fitments.subcategory_id', $subcat->id)
        )));

        $query->when($childcat, fn($q) => $q->whereHas('product', fn($pq) => $pq->whereExists(fn($e) =>
            $e->selectRaw(1)->from('product_fitments')
              ->whereColumn('product_fitments.product_id', 'products.id')
              ->where('product_fitments.childcategory_id', $childcat->id)
        )));

        // Search by product name
        $query->when($search, fn($q) => $q->whereHas('product', fn($pq) =>
            $pq->where('name', 'like', '%' . $search . '%')
               ->orWhere('name', 'like', $search . '%')
        ));

        // Price filters
        $query->when($minprice, fn($q) => $q->where('price', '>=', $minprice));
        $query->when($maxprice, fn($q) => $q->where('price', '<=', $maxprice));

        // Discount filter
        $query->when($type, fn($q) => $q->where('is_discount', 1)->where('discount_date', '>=', date('Y-m-d')));

        // Brand Quality filter
        $query->when(!empty($brandQuality), fn($q) => $q->whereIn('brand_quality_id', $brandQuality));
        $query->when($quality, fn($q) => $q->where('brand_quality_id', $quality));

        // User (vendor) filter
        $query->when($request->filled('user'), fn($q) => $q->where('user_id', (int) $request->user));

        // Vendor filter via 'vendor' query param
        $vendorFilter = (array) $request->vendor;
        $query->when(!empty($vendorFilter), fn($q) => $q->whereIn('merchant_products.user_id', $vendorFilter));

        // Store by vendor ID filter
        $query->when($request->filled('store'), fn($q) => $q->where('merchant_products.user_id', (int) $request->store));

        // Handle product attributes filtering
        if (!empty($cat) || !empty($subcat) || !empty($childcat)) {
            $query->whereHas('product', function ($productQuery) use ($cat, $subcat, $childcat, $request) {
                $productQuery->where(function ($q) use ($cat, $subcat, $childcat, $request) {
                    $this->applyAttributeFilters($q, $cat, $subcat, $childcat, $request);
                });
            });
        }

        // Apply sorting at QUERY level
        $this->applySortingToQuery($query, $sort);

        // Paginate - only fetches $perPage rows from DB
        $paginator = $query->paginate($perPage)->withQueryString();

        // Build DTOs only for the current page items
        $data['cards'] = $this->cardBuilder->buildCardsFromPaginator($paginator);

        // Keep prods for backward compatibility (pagination links, count)
        $data['prods'] = $data['cards'];

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.products', $data);
    }

    /**
     * Apply sorting directly to the query (ORDER BY in SQL)
     */
    private function applySortingToQuery($query, ?string $sort): void
    {
        match ($sort) {
            'date_desc' => $query->latest('merchant_products.id'),
            'date_asc' => $query->oldest('merchant_products.id'),
            'price_asc' => $query->orderBy('merchant_products.price', 'asc'),
            'price_desc' => $query->orderBy('merchant_products.price', 'desc'),
            'sku_asc' => $query->orderBy('products.sku', 'asc'),
            'sku_desc' => $query->orderBy('products.sku', 'desc'),
            'latest_product' => $query->leftJoin(
                DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                'pf_max.product_id', '=', 'merchant_products.product_id'
            )->orderBy('pf_max.max_year', 'desc'),
            'oldest_product' => $query->leftJoin(
                DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                'pf_max.product_id', '=', 'merchant_products.product_id'
            )->orderBy('pf_max.max_year', 'asc'),
            default => $query->latest('merchant_products.id'),
        };
    }

    /**
     * Apply attribute filters to the query
     */
    private function applyAttributeFilters($query, $cat, $subcat, $childcat, $request): void
    {
        $flag = 0;

        $applyFilters = function ($attributes) use (&$query, &$flag, $request) {
            foreach ($attributes as $attribute) {
                $inname = $attribute->input_name;
                $chFilters = $request[$inname] ?? null;

                if (!empty($chFilters)) {
                    foreach ($chFilters as $key => $chFilter) {
                        if ($key == 0 && $flag == 0) {
                            $query->where('attributes', 'like', '%"' . $chFilter . '"%');
                            $flag = 1;
                        } else {
                            $query->orWhere('attributes', 'like', '%"' . $chFilter . '"%');
                        }
                    }
                }
            }
        };

        if (!empty($cat) && $cat->attributes) {
            $applyFilters($cat->attributes);
        }
        if (!empty($subcat) && $subcat->attributes) {
            $applyFilters($subcat->attributes);
        }
        if (!empty($childcat) && $childcat->attributes) {
            $applyFilters($childcat->attributes);
        }
    }

    public function getsubs(Request $request)
    {
        $category = Category::where('slug', $request->category)->firstOrFail();
        $subcategories = Subcategory::where('category_id', $category->id)->get();
        return $subcategories;
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
        $input = $request->all();
        $data->fill($input)->save();
        return back()->with('success', 'Report has been sent successfully.');

    }
}
