<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pagesetting;
use Illuminate\Http\Request;
use Validator;

class PageSettingController extends AdminBaseController
{
    protected $rules =
    [
        'best_seller_banner'    => 'mimes:jpeg,jpg,png,svg',
        'big_save_banner'       => 'mimes:jpeg,jpg,png,svg',
        'best_seller_banner1'   => 'mimes:jpeg,jpg,png,svg',
        'big_save_banner1'      => 'mimes:jpeg,jpg,png,svg',
        'rightbanner1'          => 'mimes:jpeg,jpg,png,svg',
        'rightbanner2'          => 'mimes:jpeg,jpg,png,svg'
    ];

    protected $customs =
    [
        'best_seller_banner.mimes'  => 'Photo type must be in jpeg, jpg, png, svg.',
        'big_save_banner.mimes'     => 'Photo type must be in jpeg, jpg, png, svg.',
        'best_seller_banner1.mimes' => 'Photo type must be in jpeg, jpg, png, svg.',
        'big_save_banner1.mimes'    => 'Photo type must be in jpeg, jpg, png, svg.',
        'rightbanner1.mimes'        => 'Photo type must be in jpeg, jpg, png, svg.',
        'rightbanner2.mimes'        => 'Photo type must be in jpeg, jpg, png, svg.'
    ];

    // Page Settings All post requests will be done in this method
    public function update(Request $request)
    {
        //--- Validation Section
        $validator = Validator::make($request->all(), $this->rules,$this->customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $data = Pagesetting::findOrFail(1);
        $input = $request->all();

            if ($file = $request->file('best_seller_banner'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->best_seller_banner);
                $input['best_seller_banner'] = $name;
            }
            if ($file = $request->file('big_save_banner'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->big_save_banner);
                $input['big_save_banner'] = $name;
            }
            if ($file = $request->file('best_seller_banner1'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->best_seller_banner1);
                $input['best_seller_banner1'] = $name;
            }
            if ($file = $request->file('big_save_banner1'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->big_save_banner1);
                $input['big_save_banner1'] = $name;
            }
            if ($file = $request->file('rightbanner1'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->rightbanner1);
                $input['rightbanner1'] = $name;
            }
            if ($file = $request->file('rightbanner2'))
            {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name,$file,$data->rightbanner2);
                $input['rightbanner2'] = $name;
            }

        $data->update($input);
        cache()->forget('pagesettings');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }


    public function homeupdate(Request $request)
    {
        $data = Pagesetting::findOrFail(1);
        $input = $request->all();

        if ($request->category == ""){
            $input['category'] = 0;
        }
        if ($request->arrival_section == ""){
            $input['arrival_section'] = 0;
        }
        if ($request->our_services == ""){
            $input['our_services'] = 0;
        }
        if ($request->blog == ""){
            $input['blog'] = 0;
        }
        if ($request->popular_products == ""){
            $input['popular_products'] = 0;
        }
        if ($request->third_left_banner == ""){
            $input['third_left_banner'] = 0;
        }
        if ($request->slider == ""){
            $input['slider'] = 0;
        }
        if ($request->flash_deal == ""){
            $input['flash_deal'] = 0;
        }
        if ($request->deal_of_the_day == ""){
            $input['deal_of_the_day'] = 0;
        }
        if ($request->best_sellers == ""){
            $input['best_sellers'] = 0;
        }
        if ($request->brand == ""){
            $input['brand'] = 0;
        }
        if ($request->top_big_trending == ""){
            $input['top_big_trending'] = 0;
        }
        if ($request->top_brand == ""){
            $input['top_brand'] = 0;
        }


        $data->update($input);

        cache()->forget('pagesettings');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }

    public function deal(){
        $data = Pagesetting::findOrFail(1);

        // Get current deal products (is_discount = 1 and valid discount_date)
        $dealProducts = \App\Models\MerchantItem::where('is_discount', 1)
            ->where('discount_date', '>=', date('Y-m-d'))
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with([
                'catalogItem:id,name,label_en,label_ar,slug,photo,sku,brand_id',
                'catalogItem.brand', // CatalogItem brand (Toyota, Nissan, etc.)
                'user:id,shop_name,shop_name_ar',
                'qualityBrand' // Quality brand (OEM, Aftermarket, etc.)
            ])
            ->latest()
            ->get();

        return view('admin.pagesetting.deal', compact('dealProducts'));
    }

    /**
     * Toggle deal status for a merchant product
     */
    public function toggleDeal(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'merchant_item_id' => 'required|exists:merchant_items,id',
                'is_discount' => 'required',
                'discount_date' => 'nullable|date',
            ]);

            $isDiscount = filter_var($request->is_discount, FILTER_VALIDATE_BOOLEAN);

            $mp = \App\Models\MerchantItem::findOrFail($request->merchant_item_id);
            $mp->is_discount = $isDiscount ? 1 : 0;
            $mp->discount_date = $isDiscount ? ($request->discount_date ?? now()->addDays(7)->format('Y-m-d')) : null;
            $mp->save();

            // Clear homepage cache
            \Illuminate\Support\Facades\Cache::forget('homepage_flash_merchant');

            return response()->json([
                'success' => true,
                'message' => $isDiscount ? __('Product added to Deal of the Day') : __('Product removed from Deal of the Day')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products for deal selection (Step 1: Find products)
     */
    public function searchDealProducts(\Illuminate\Http\Request $request)
    {
        $search = $request->get('q', '');

        // Search products first
        $catalogItems = \App\Models\CatalogItem::where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('label_en', 'like', "%{$search}%")
                  ->orWhere('label_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->whereHas('merchantItems', function($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            })
            ->withCount(['merchantItems' => function($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            }])
            ->limit(20)
            ->get()
            ->map(function($catalogItem) {
                // Get localized name
                $name = app()->getLocale() == 'ar'
                    ? ($catalogItem->label_ar ?: $catalogItem->label_en ?: $catalogItem->name)
                    : ($catalogItem->label_en ?: $catalogItem->name);

                // Get photo URL like homepage
                $photo = $catalogItem->photo
                    ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                        ? $catalogItem->photo
                        : \Illuminate\Support\Facades\Storage::url($catalogItem->photo))
                    : asset('assets/images/noimage.png');

                return [
                    'catalog_item_id' => $catalogItem->id,
                    'name' => $name,
                    'sku' => $catalogItem->sku,
                    'photo' => $photo,
                    'merchants_count' => $catalogItem->merchant_items_count,
                ];
            });

        return response()->json($catalogItems);
    }

    /**
     * Get merchants for a specific catalog item (Step 2: Choose merchant + quality brand)
     */
    public function getProductMerchants(\Illuminate\Http\Request $request)
    {
        $catalogItemId = $request->get('product_id') ?? $request->get('catalog_item_id');

        // Get catalog item with brand
        $catalogItem = \App\Models\CatalogItem::with('brand')->find($catalogItemId);

        $merchants = \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with([
                'user:id,shop_name,shop_name_ar',
                'qualityBrand'
            ])
            ->get()
            ->map(function($mp) use ($catalogItem) {
                return [
                    'id' => $mp->id,
                    // CatalogItem Brand (Toyota, Nissan, etc.)
                    'brand_name' => $catalogItem->brand?->localized_name,
                    'brand_logo' => $catalogItem->brand?->photo_url,
                    // Quality Brand (OEM, Aftermarket, etc.)
                    'quality_brand_id' => $mp->quality_brand_id,
                    'quality_brand' => $mp->qualityBrand?->localized_name,
                    'quality_brand_logo' => $mp->qualityBrand?->logo_url,
                    // Vendor
                    'merchant_id' => $mp->user_id,
                    'merchant_name' => app()->getLocale() == 'ar'
                        ? ($mp->user->shop_name_ar ?: $mp->user->shop_name)
                        : $mp->user->shop_name,
                    // Pricing
                    'price' => $mp->price,
                    'previous_price' => $mp->previous_price,
                    'stock' => $mp->stock,
                    'is_discount' => $mp->is_discount,
                    'discount_date' => $mp->discount_date,
                ];
            });

        return response()->json($merchants);
    }

    public function menuupdate(Request $request)
    {
        $data = Pagesetting::findOrFail(1);
        $input = $request->all();

        if ($request->home == ""){
            $input['home'] = 0;
        }
        if ($request->blog == ""){
            $input['blog'] = 0;
        }
        if ($request->faq == ""){
            $input['faq'] = 0;
        }
        if ($request->contact == ""){
            $input['contact'] = 0;
        }
        $data->update($input);
        cache()->forget('pagesettings');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }


    public function contact()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.contact',compact('data'));
    }

    public function customize()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.customize',compact('data'));
    }

    public function best_seller()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.best_seller',compact('data'));
    }

    public function big_save()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.big_save',compact('data'));
    }

    public function page_banner()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.page_banner',compact('data'));
    }

    public function right_banner()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.right_banner',compact('data'));
    }

    public function menu_links()
    {
        $data = Pagesetting::find(1);
        return view('admin.pagesetting.menu_links',compact('data'));
    }

    // =========================================================================
    // BEST SELLERS MANAGEMENT
    // =========================================================================

    /**
     * Show Best Sellers management page
     */
    public function bestSellers()
    {
        // Get current best sellers (best = 1)
        $bestProducts = \App\Models\MerchantItem::where('best', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with([
                'catalogItem:id,name,label_en,label_ar,slug,photo,sku,brand_id',
                'catalogItem.brand',
                'user:id,shop_name,shop_name_ar',
                'qualityBrand'
            ])
            ->latest()
            ->get();

        return view('admin.pagesetting.best_sellers_manage', compact('bestProducts'));
    }

    /**
     * Toggle best seller status for a merchant product
     */
    public function toggleBestSellers(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'merchant_item_id' => 'required|exists:merchant_items,id',
                'best' => 'required',
            ]);

            $isBest = filter_var($request->best, FILTER_VALIDATE_BOOLEAN);

            $mp = \App\Models\MerchantItem::findOrFail($request->merchant_item_id);
            $mp->best = $isBest ? 1 : 0;
            $mp->save();

            // Clear homepage cache
            \Illuminate\Support\Facades\Cache::forget('homepage_best_merchants');

            return response()->json([
                'success' => true,
                'message' => $isBest ? __('Product added to Best Sellers') : __('Product removed from Best Sellers')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products for best sellers selection
     */
    public function searchBestSellersProducts(\Illuminate\Http\Request $request)
    {
        $search = $request->get('q', '');

        $catalogItems = \App\Models\CatalogItem::where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('label_en', 'like', "%{$search}%")
                  ->orWhere('label_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->whereHas('merchantItems', function($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            })
            ->withCount(['merchantItems' => function($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            }])
            ->limit(20)
            ->get()
            ->map(function($catalogItem) {
                $name = app()->getLocale() == 'ar'
                    ? ($catalogItem->label_ar ?: $catalogItem->label_en ?: $catalogItem->name)
                    : ($catalogItem->label_en ?: $catalogItem->name);

                $photo = $catalogItem->photo
                    ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                        ? $catalogItem->photo
                        : \Illuminate\Support\Facades\Storage::url($catalogItem->photo))
                    : asset('assets/images/noimage.png');

                return [
                    'catalog_item_id' => $catalogItem->id,
                    'name' => $name,
                    'sku' => $catalogItem->sku,
                    'photo' => $photo,
                    'merchants_count' => $catalogItem->merchant_items_count,
                ];
            });

        return response()->json($catalogItems);
    }

    /**
     * Get merchants for a specific catalog item (best sellers)
     */
    public function getBestSellersMerchants(\Illuminate\Http\Request $request)
    {
        $catalogItemId = $request->get('product_id') ?? $request->get('catalog_item_id');
        $catalogItem = \App\Models\CatalogItem::with('brand')->find($catalogItemId);

        $merchants = \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with([
                'user:id,shop_name,shop_name_ar',
                'qualityBrand'
            ])
            ->get()
            ->map(function($mp) use ($catalogItem) {
                return [
                    'id' => $mp->id,
                    'brand_name' => $catalogItem->brand?->localized_name,
                    'brand_logo' => $catalogItem->brand?->photo_url,
                    'quality_brand_id' => $mp->quality_brand_id,
                    'quality_brand' => $mp->qualityBrand?->localized_name,
                    'quality_brand_logo' => $mp->qualityBrand?->logo_url,
                    'merchant_id' => $mp->user_id,
                    'merchant_name' => app()->getLocale() == 'ar'
                        ? ($mp->user->shop_name_ar ?: $mp->user->shop_name)
                        : $mp->user->shop_name,
                    'price' => $mp->price,
                    'previous_price' => $mp->previous_price,
                    'stock' => $mp->stock,
                    'best' => $mp->best,
                ];
            });

        return response()->json($merchants);
    }

    // =========================================================================
    // GENERIC HOMEPAGE SECTION MANAGEMENT (Top Rated, Big Save, Trending, Featured)
    // =========================================================================

    /**
     * Generic method to get catalog items by flag
     */
    private function getProductsByFlag($flag)
    {
        return \App\Models\MerchantItem::where($flag, 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with([
                'catalogItem:id,name,label_en,label_ar,slug,photo,sku,brand_id',
                'catalogItem.brand',
                'user:id,shop_name,shop_name_ar',
                'qualityBrand'
            ])
            ->latest()
            ->get();
    }

    /**
     * Generic toggle method
     */
    private function toggleFlag($request, $flag, $cacheKey)
    {
        try {
            $request->validate([
                'merchant_item_id' => 'required|exists:merchant_items,id',
                'flag' => 'required',
            ]);

            $isEnabled = filter_var($request->flag, FILTER_VALIDATE_BOOLEAN);
            $mp = \App\Models\MerchantItem::findOrFail($request->merchant_item_id);
            $mp->$flag = $isEnabled ? 1 : 0;
            $mp->save();

            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generic search catalog items
     */
    private function searchProducts($search)
    {
        return \App\Models\CatalogItem::where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('label_en', 'like', "%{$search}%")
                  ->orWhere('label_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->whereHas('merchantItems', fn($q) => $q->where('status', 1)->whereHas('user', fn($u) => $u->where('is_merchant', 2)))
            ->withCount(['merchantItems' => fn($q) => $q->where('status', 1)->whereHas('user', fn($u) => $u->where('is_merchant', 2))])
            ->limit(20)
            ->get()
            ->map(function($catalogItem) {
                $name = app()->getLocale() == 'ar' ? ($catalogItem->label_ar ?: $catalogItem->label_en ?: $catalogItem->name) : ($catalogItem->label_en ?: $catalogItem->name);
                $photo = $catalogItem->photo ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : \Illuminate\Support\Facades\Storage::url($catalogItem->photo)) : asset('assets/images/noimage.png');
                return ['catalog_item_id' => $catalogItem->id, 'name' => $name, 'sku' => $catalogItem->sku, 'photo' => $photo, 'merchants_count' => $catalogItem->merchant_items_count];
            });
    }

    /**
     * Generic get merchants for catalog item
     */
    private function getMerchants($catalogItemId, $flag)
    {
        $catalogItem = \App\Models\CatalogItem::with('brand')->find($catalogItemId);
        return \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['user:id,shop_name,shop_name_ar', 'qualityBrand'])
            ->get()
            ->map(function($mp) use ($catalogItem, $flag) {
                return [
                    'id' => $mp->id,
                    'brand_name' => $catalogItem->brand?->localized_name,
                    'brand_logo' => $catalogItem->brand?->photo_url,
                    'quality_brand' => $mp->qualityBrand?->localized_name,
                    'quality_brand_logo' => $mp->qualityBrand?->logo_url,
                    'vendor_name' => app()->getLocale() == 'ar' ? ($mp->user->shop_name_ar ?: $mp->user->shop_name) : $mp->user->shop_name,
                    'price' => $mp->price,
                    'previous_price' => $mp->previous_price,
                    'stock' => $mp->stock,
                    'is_flagged' => $mp->$flag == 1,
                ];
            });
    }

    // TOP RATED
    public function topRated() { return view('admin.pagesetting.top_rated_manage', ['products' => $this->getProductsByFlag('top')]); }
    public function toggleTopRated(\Illuminate\Http\Request $request) { return $this->toggleFlag($request, 'top', 'homepage_top_merchants'); }
    public function searchTopRated(\Illuminate\Http\Request $request) { return response()->json($this->searchProducts($request->get('q', ''))); }
    public function getTopRatedMerchants(\Illuminate\Http\Request $request) { return response()->json($this->getMerchants($request->get('product_id'), 'top')); }

    // BIG SAVE
    public function bigSave() { return view('admin.pagesetting.big_save_manage', ['products' => $this->getProductsByFlag('big')]); }
    public function toggleBigSave(\Illuminate\Http\Request $request) { return $this->toggleFlag($request, 'big', 'homepage_big_merchants'); }
    public function searchBigSave(\Illuminate\Http\Request $request) { return response()->json($this->searchProducts($request->get('q', ''))); }
    public function getBigSaveMerchants(\Illuminate\Http\Request $request) { return response()->json($this->getMerchants($request->get('product_id'), 'big')); }

    // TRENDING
    public function trending() { return view('admin.pagesetting.trending_manage', ['products' => $this->getProductsByFlag('trending')]); }
    public function toggleTrending(\Illuminate\Http\Request $request) { return $this->toggleFlag($request, 'trending', 'homepage_trending_merchants'); }
    public function searchTrending(\Illuminate\Http\Request $request) { return response()->json($this->searchProducts($request->get('q', ''))); }
    public function getTrendingMerchants(\Illuminate\Http\Request $request) { return response()->json($this->getMerchants($request->get('product_id'), 'trending')); }

    // FEATURED
    public function featured() { return view('admin.pagesetting.featured_manage', ['products' => $this->getProductsByFlag('featured')]); }
    public function toggleFeatured(\Illuminate\Http\Request $request) { return $this->toggleFlag($request, 'featured', 'homepage_featured_merchants'); }
    public function searchFeatured(\Illuminate\Http\Request $request) { return response()->json($this->searchProducts($request->get('q', ''))); }
    public function getFeaturedMerchants(\Illuminate\Http\Request $request) { return response()->json($this->getMerchants($request->get('product_id'), 'featured')); }

    //Upadte About Page Section Settings

}
