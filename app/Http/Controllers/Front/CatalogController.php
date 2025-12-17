<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\QualityBrand;
use App\Models\Report;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CatalogController extends FrontBaseController
{

    // CATEGORIES SECTOPN

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

        // Retrieve latest products that have at least one active merchant listing with a vendor account
        // ✅ N+1 FIX: Use withBestMerchant() for eager loading
        $latest_products = Product::with('brand')
            ->withBestMerchant()
            ->whereLatest(1)
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                    ->whereHas('user', function ($user) {
                        $user->where('is_vendor', 2);
                    });
            })
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->take(5)
            ->get();

        // Get merchant products (offers) for display
        $prods = MerchantProduct::with([
            'user',
            'qualityBrand',
            'product' => function ($q) {
                $q->with('brand')->withCount('ratings')->withAvg('ratings', 'rating');
            },
        ])
            ->where('merchant_products.status', 1)
            ->where('merchant_products.stock', '>=', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            })
            ->latest('merchant_products.id')
            ->paginate($this->gs->page_count);

        return view('frontend.products', compact('categories', 'latest_products', 'prods', 'vendors', 'brand_qualities'));
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
            session::put('view', $request->view_check);
        }

        //   dd(session::get('view'));

        $cat = null;
        $subcat = null;
        $childcat = null;
        $flash = null;
        $minprice = $request->min;
        $maxprice = $request->max;
        $sort = $request->sort;
        $search = $request->search;
        $pageby = $request->pageby && $request->pageby !== 'undefined' && is_numeric($request->pageby) ? (int)$request->pageby : null;

        $minprice = ($minprice / $this->curr->value);
        $maxprice = ($maxprice / $this->curr->value);
        $type = $request->has('type') ?? '';
        $brandQuality = (array) $request->brand_quality;
        $quality = $request->quality;
        $user = $request->user;

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

        // Retrieve latest products that have at least one active merchant listing with a vendor account
        // ✅ N+1 FIX: Use withBestMerchant() for eager loading
        $data['latest_products'] = Product::with('brand')
            ->withBestMerchant()
            ->whereLatest(1)
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                    ->whereHas('user', function ($user) {
                        $user->where('is_vendor', 2);
                    });
            })
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->take(5)
            ->get();

        // Get merchant products (offers) instead of products for displaying multiple cards per product
        // Build query for merchant products (offers) with their related products
        $prods = MerchantProduct::with([
            'user',
            'qualityBrand',
            'product' => function ($q) {
                $q->with('brand')->withCount('ratings')->withAvg('ratings', 'rating');
            },
        ])
            ->leftJoin('products', 'products.id', '=', 'merchant_products.product_id')
            ->select('merchant_products.*') // Avoid column collisions
            ->where('merchant_products.status', 1)
            ->where('merchant_products.stock', '>=', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            });

        // Filter by product_fitments (vehicle tree source of truth)
        // Use EXISTS against product_fitments instead of products.category_id
        $prods = $prods->when($cat, function ($query, $cat) {
            return $query->whereHas('product', function ($productQuery) use ($cat) {
                $productQuery->whereExists(function ($exists) use ($cat) {
                    $exists->selectRaw(1)
                        ->from('product_fitments')
                        ->whereColumn('product_fitments.product_id', 'products.id')
                        ->where('product_fitments.category_id', $cat->id);
                });
            });
        });

        $prods = $prods->when($subcat, function ($query, $subcat) {
            return $query->whereHas('product', function ($productQuery) use ($subcat) {
                $productQuery->whereExists(function ($exists) use ($subcat) {
                    $exists->selectRaw(1)
                        ->from('product_fitments')
                        ->whereColumn('product_fitments.product_id', 'products.id')
                        ->where('product_fitments.subcategory_id', $subcat->id);
                });
            });
        });

        $prods = $prods->when($childcat, function ($query, $childcat) {
            return $query->whereHas('product', function ($productQuery) use ($childcat) {
                $productQuery->whereExists(function ($exists) use ($childcat) {
                    $exists->selectRaw(1)
                        ->from('product_fitments')
                        ->whereColumn('product_fitments.product_id', 'products.id')
                        ->where('product_fitments.childcategory_id', $childcat->id);
                });
            });
        });

        // Search by product name
        $prods = $prods->when($search, function ($query, $search) {
            return $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', $search . '%');
                });
            });
        });

        // Price filters
        $prods = $prods->when($minprice, function ($query) use ($minprice) {
            return $query->where('price', '>=', $minprice);
        });

        $prods = $prods->when($maxprice, function ($query) use ($maxprice) {
            return $query->where('price', '<=', $maxprice);
        });

        // Discount filter
        $prods = $prods->when($type, function ($query) {
            return $query->where('is_discount', 1)
                ->where('discount_date', '>=', date('Y-m-d'));
        });

        // Brand Quality filter
        $prods = $prods->when(!empty($brandQuality), fn($q) => $q->whereIn('brand_quality_id', $brandQuality));

        // Quality filter from dropdown
        $prods = $prods->when($quality, fn($q) => $q->where('brand_quality_id', $quality));

        // User (vendor) filter
        $prods = $prods->when($request->filled('user'), function ($q) use ($request) {
            $q->where('user_id', (int) $request->user);
        });

        // Vendor filter via 'vendor' query param (supports multiple vendors)
        $vendorFilter = (array) $request->vendor;
        $prods = $prods->when(!empty($vendorFilter), function ($q) use ($vendorFilter) {
            $q->whereIn('merchant_products.user_id', $vendorFilter);
        });

        // Store by vendor ID filter (accepts integer vendor ID)
        $prods = $prods->when($request->filled('store'), function ($q) use ($request) {
            $q->where('merchant_products.user_id', (int) $request->store);
        });

        // Sorting options - sort by merchant products
        $prods = $prods->when($sort, function ($query, $sort) {
            if ($sort == 'date_desc') {
                return $query->latest('merchant_products.id');
            } elseif ($sort == 'date_asc') {
                return $query->oldest('merchant_products.id');
            } elseif ($sort == 'latest_product') {
                // Latest Product = order by maximum beginYear DESC
                return $query->leftJoin(\DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                    'pf_max.product_id', '=', 'merchant_products.product_id')
                    ->orderBy('pf_max.max_year', 'desc');
            } elseif ($sort == 'oldest_product') {
                // Oldest Product = order by maximum beginYear ASC
                return $query->leftJoin(\DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                    'pf_max.product_id', '=', 'merchant_products.product_id')
                    ->orderBy('pf_max.max_year', 'asc');
            } elseif ($sort == 'price_desc') {
                return $query->orderBy('merchant_products.price', 'desc');
            } elseif ($sort == 'price_asc') {
                return $query->orderBy('merchant_products.price', 'asc');
            } elseif ($sort == 'sku_asc') {
                return $query->orderBy('products.sku', 'asc');
            } elseif ($sort == 'sku_desc') {
                return $query->orderBy('products.sku', 'desc');
            }
        });

        // Default sorting if no sort option provided
        if (empty($sort)) {
            $prods = $prods->latest('merchant_products.id');
        }

        // Handle product attributes filtering through product relationship
        $prods = $prods->whereHas('product', function ($productQuery) use ($cat, $subcat, $childcat, $request) {
            $productQuery->where(function ($query) use ($cat, $subcat, $childcat, $request) {
                $flag = 0;
                if (!empty($cat)) {
                    foreach ($cat->attributes as $key => $attribute) {
                        $inname = $attribute->input_name;
                        $chFilters = $request["$inname"];

                        if (!empty($chFilters)) {
                            $flag = 1;
                            foreach ($chFilters as $key => $chFilter) {
                                if ($key == 0) {
                                    $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                } else {
                                    $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                }
                            }
                        }
                    }
                }

                if (!empty($subcat)) {
                    foreach ($subcat->attributes as $attribute) {
                        $inname = $attribute->input_name;
                        $chFilters = $request["$inname"];

                        if (!empty($chFilters)) {
                            $flag = 1;
                            foreach ($chFilters as $key => $chFilter) {
                                if ($key == 0 && $flag == 0) {
                                    $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                } else {
                                    $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                }
                            }
                        }
                    }
                }

                if (!empty($childcat)) {
                    foreach ($childcat->attributes as $attribute) {
                        $inname = $attribute->input_name;
                        $chFilters = $request["$inname"];

                        if (!empty($chFilters)) {
                            $flag = 1;
                            foreach ($chFilters as $key => $chFilter) {
                                if ($key == 0 && $flag == 0) {
                                    $query->where('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                } else {
                                    $query->orWhere('attributes', 'like', '%' . '"' . $chFilter . '"' . '%');
                                }
                            }
                        }
                    }
                }
            });
        });
        
        // dd($prods->toSql(), $prods->getBindings(), $prods->count());


        // Paginate the merchant products (offers)
        $prods = $prods->paginate(isset($pageby) ? $pageby : $this->gs->page_count);

        // No need to transform ratings - they are already eager loaded with withCount and withAvg
        
        $data['prods'] = $prods;
        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.category', $data);
        }

        return view('frontend.products', $data);
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
