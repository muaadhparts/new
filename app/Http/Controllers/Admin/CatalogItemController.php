<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Currency;
use App\Models\Gallery;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use Validator;

class CatalogItemController extends AdminBaseController
{
    //*** JSON Request
    // داخل class CatalogItemController extends AdminBaseController

    public function datatables(Request $request)
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        // item_type is now on merchant_items, not catalog_items
        $query = MerchantItem::with(['catalogItem.brand', 'user', 'qualityBrand'])
            ->where('item_type', 'normal');

        if ($request->type == 'deactive') {
            $query->where('status', 0);
        }

        $datas = $query->latest('id');

        return \Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereHas('catalogItem', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('sku', 'like', "%{$keyword}%")
                      ->orWhere('label_ar', 'like', "%{$keyword}%")
                      ->orWhere('label_en', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('photo', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '<img src="' . asset('assets/images/noimage.png') . '" class="img-thumbnail" style="width:80px">';

                $photo = filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                    ? $catalogItem->photo
                    : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png'));
                return '<img src="' . $photo . '" alt="Image" class="img-thumbnail" style="width:80px">';
            })
            ->addColumn('name', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return __('N/A');

                $prodLink = route('front.catalog-item', [
                    'slug' => $catalogItem->slug,
                    'merchant_id' => $mp->user_id,
                    'merchant_item_id' => $mp->id
                ]);

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $sku = $catalogItem->sku ? '<br><small class="text-muted">' . __('SKU') . ': ' . $catalogItem->sku . '</small>' : '';
                $condition = $mp->item_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $sku . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                return $catalogItem && $catalogItem->brand ? getLocalizedBrandName($catalogItem->brand) : __('N/A');
            })
            ->addColumn('quality_brand', function (MerchantItem $mp) {
                return $mp->qualityBrand ? getLocalizedQualityName($mp->qualityBrand) : __('N/A');
            })
            ->addColumn('merchant', function (MerchantItem $mp) {
                if (!$mp->user) return __('N/A');
                $shopName = $mp->user->shop_name ?: $mp->user->name;
                return '<span title="' . $mp->user->name . '">' . $shopName . '</span>';
            })
            ->addColumn('price', function (MerchantItem $mp) {
                $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());

                $price = (float) $mp->price;
                $base = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

                return \PriceHelper::showAdminCurrencyPrice($base * $this->curr->value);
            })
            ->addColumn('stock', function (MerchantItem $mp) {
                if ($mp->stock === null) return __('Unlimited');
                if ((int) $mp->stock === 0) return '<span class="text-danger">' . __('Out Of Stock') . '</span>';
                return $mp->stock;
            })
            ->addColumn('type', function (MerchantItem $mp) {
                return $mp->catalogItem ? $mp->catalogItem->type : __('N/A');
            })
            ->addColumn('status', function (MerchantItem $mp) {
                $class = $mp->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $mp->status == 1 ? 'selected' : '';
                $ns = $mp->status == 0 ? 'selected' : '';

                // حالة السجل التجاري
                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('admin-merchant-item-status', ['id' => $mp->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('admin-merchant-item-status', ['id' => $mp->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '';

                $catalog = $catalogItem->type == 'Physical'
                    ? ($catalogItem->is_catalog == 1
                        ? '<a href="javascript:;" data-href="' . route('admin-catalog-item-catalog', ['id1' => $catalogItem->id, 'id2' => 0]) . '" data-bs-toggle="modal" data-bs-target="#catalog-modal" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Remove Catalog") . '</a>'
                        : '<a href="javascript:;" data-href="' . route('admin-catalog-item-catalog', ['id1' => $catalogItem->id, 'id2' => 1]) . '" data-bs-toggle="modal" data-bs-target="#catalog-modal"> <i class="fas fa-plus"></i> ' . __("Add To Catalog") . '</a>')
                    : '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('admin-catalog-item-edit', $mp->id) . '"><i class="fas fa-edit"></i> ' . __("Edit CatalogItem") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $catalogItem->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>'
                        . $catalog .
                        '<a data-href="' . route('admin-catalog-item-feature', $catalogItem->id) . '" class="feature" data-bs-toggle="modal" data-bs-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a>
                        <a href="javascript:;" data-href="' . route('admin-catalog-item-delete', $catalogItem->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete CatalogItem") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant'])
            ->toJson();
    }

    //*** JSON Request
    public function catalogdatatables()
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        // فقط المنتجات المضافة للكتالوج (is_catalog = 1)
        $query = MerchantItem::with(['catalogItem.brand', 'user', 'qualityBrand'])
            ->whereHas('catalogItem', function($q) {
                $q->where('is_catalog', 1);
            });

        $datas = $query->latest('id');

        return \Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereHas('catalogItem', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('sku', 'like', "%{$keyword}%")
                      ->orWhere('label_ar', 'like', "%{$keyword}%")
                      ->orWhere('label_en', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('photo', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '<img src="' . asset('assets/images/noimage.png') . '" class="img-thumbnail" style="width:80px">';

                $photo = filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                    ? $catalogItem->photo
                    : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png'));
                return '<img src="' . $photo . '" alt="Image" class="img-thumbnail" style="width:80px">';
            })
            ->addColumn('name', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return __('N/A');

                $prodLink = route('front.catalog-item', [
                    'slug' => $catalogItem->slug,
                    'merchant_id' => $mp->user_id,
                    'merchant_item_id' => $mp->id
                ]);

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $sku = $catalogItem->sku ? '<br><small class="text-muted">' . __('SKU') . ': ' . $catalogItem->sku . '</small>' : '';
                $condition = $mp->item_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $sku . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                return $catalogItem && $catalogItem->brand ? getLocalizedBrandName($catalogItem->brand) : __('N/A');
            })
            ->addColumn('quality_brand', function (MerchantItem $mp) {
                return $mp->qualityBrand ? getLocalizedQualityName($mp->qualityBrand) : __('N/A');
            })
            ->addColumn('merchant', function (MerchantItem $mp) {
                if (!$mp->user) return __('N/A');
                $shopName = $mp->user->shop_name ?: $mp->user->name;
                return '<span title="' . $mp->user->name . '">' . $shopName . '</span>';
            })
            ->addColumn('price', function (MerchantItem $mp) {
                $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());

                $price = (float) $mp->price;
                $base = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

                return \PriceHelper::showAdminCurrencyPrice($base * $this->curr->value);
            })
            ->addColumn('stock', function (MerchantItem $mp) {
                if ($mp->stock === null) return __('Unlimited');
                if ((int) $mp->stock === 0) return '<span class="text-danger">' . __('Out Of Stock') . '</span>';
                return $mp->stock;
            })
            ->addColumn('status', function (MerchantItem $mp) {
                $class = $mp->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $mp->status == 1 ? 'selected' : '';
                $ns = $mp->status == 0 ? 'selected' : '';

                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('admin-merchant-item-status', ['id' => $mp->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('admin-merchant-item-status', ['id' => $mp->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('admin-catalog-item-edit', $mp->id) . '"><i class="fas fa-edit"></i> ' . __("Edit CatalogItem") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $catalogItem->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>
                        <a data-href="' . route('admin-catalog-item-feature', $catalogItem->id) . '" class="feature" data-bs-toggle="modal" data-bs-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a>
                        <a href="javascript:;" data-href="' . route('admin-catalog-item-catalog', ['id1' => $catalogItem->id, 'id2' => 0]) . '" data-bs-toggle="modal" data-bs-target="#catalog-modal"><i class="fas fa-trash-alt"></i> ' . __("Remove Catalog") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant'])
            ->toJson();
    }

    public function catalogItemsCatalog()
    {
        return view('admin.catalog-item.catalog');
    }

    public function index()
    {
        return view('admin.catalog-item.index');
    }

    public function types()
    {
        return view('admin.catalog-item.types');
    }

    public function deactive()
    {
        return view('admin.catalog-item.deactive');
    }

    public function catalogItemSettings()
    {
        return view('admin.catalog-item.settings');
    }

    //*** GET Request
    public function create($slug)
    {
        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $sign = $this->curr;
        if ($slug == 'physical') {
            return view('admin.catalog-item.create.physical', compact('cats', 'sign'));
        } else if ($slug == 'digital') {
            return view('admin.catalog-item.create.digital', compact('cats', 'sign'));
        } else if (($slug == 'license')) {
            return view('admin.catalog-item.create.license', compact('cats', 'sign'));
        } else if (($slug == 'listing')) {
            return view('admin.catalog-item.create.listing', compact('cats', 'sign'));
        }
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = CatalogItem::findOrFail($id1);
        $data->status = $id2;
        $data->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request - Merchant Item Status
    public function merchantItemStatus($id, $status)
    {
        $merchantItem = MerchantItem::findOrFail($id);
        $merchantItem->status = $status;
        $merchantItem->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** POST Request
    public function uploadUpdate(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'image' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $data = CatalogItem::findOrFail($id);

        //--- Validation Section Ends
        $image = $request->image;
        list($type, $image) = explode(';', $image);
        list(, $image) = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time() . Str::random(8) . '.png';
        $path = 'assets/images/catalogItems/' . $image_name;
        file_put_contents($path, $image);
        if ($data->photo != null) {
            if (file_exists(public_path() . '/assets/images/catalogItems/' . $data->photo)) {
                unlink(public_path() . '/assets/images/catalogItems/' . $data->photo);
            }
        }
        $input['photo'] = $image_name;
        $data->update($input);
        if ($data->thumbnail != null) {
            if (file_exists(public_path() . '/assets/images/thumbnails/' . $data->thumbnail)) {
                unlink(public_path() . '/assets/images/thumbnails/' . $data->thumbnail);
            }
        }

        $img = Image::make('assets/images/catalogItems/' . $data->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save('assets/images/thumbnails/' . $thumbnail);
        $data->thumbnail = $thumbnail;
        $data->update();
        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'photo' => 'required',
            'file' => 'mimes:zip',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new CatalogItem;
        $sign = $this->curr;
        $input = $request->all();

        // Check File
        if ($file = $request->file('file')) {
            $name = time() . \Str::random(8) . str_replace(' ', '', $file->getClientOriginalExtension());
            $file->move('assets/files', $name);
            $input['file'] = $name;
        }

        $image = $request->photo;
        list($type, $image) = explode(';', $image);
        list(, $image) = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time() . Str::random(8) . '.png';
        $path = 'assets/images/catalogItems/' . $image_name;
        file_put_contents($path, $image);
        $input['photo'] = $image_name;

        if ($request->type == "Physical" || $request->type == "Listing") {
            $rules = ['sku' => 'min:8|unique:catalog_items'];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }

            // Handle size data (belongs to catalog_items table)
            if (empty($request->size_check)) {
                $input['size'] = null;
                $input['size_qty'] = null;
            } else {
                if (in_array(null, $request->size) || in_array(null, $request->size_qty)) {
                    $input['size'] = null;
                    $input['size_qty'] = null;
                } else {
                    $input['size'] = implode(',', $request->size);
                    $input['size_qty'] = implode(',', $request->size_qty);
                }
            }

            if ($request->mesasure_check == "") {
                $input['measure'] = null;
            }
        }

        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', $request->meta_tag);
            }
        }

        if ($request->type == "License") {
            if (in_array(null, $request->license) || in_array(null, $request->license_qty)) {
                $input['license'] = null;
                $input['license_qty'] = null;
            } else {
                $input['license'] = implode(',,', $request->license);
                $input['license_qty'] = implode(',', $request->license_qty);
            }
        }

        if (in_array(null, $request->features)) {
            $input['features'] = null;
        } else {
            $input['features'] = implode(',', str_replace(',', ' ', $request->features));
        }

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        }

        // Legacy fields removed - prices now handled via MerchantItem
        $basePrice = isset($input['price']) ? ($input['price'] / $sign->value) : 0;
        $basePreviousPrice = isset($input['previous_price']) ? ($input['previous_price'] / $sign->value) : null;

        // Store merchant-specific data before removing from input
        $merchantId = (int) ($request->input('user_id') ?? 0);
        $brandQualityId = $request->input('brand_quality_id') ?: null;

        // Remove merchant-specific fields from catalog item table input
        unset($input['price'], $input['previous_price'], $input['stock'], $input['user_id'], $input['brand_quality_id'], $input['merchant_id']);
        if ($request->cross_products) {
            $input['cross_products'] = implode(',', $request->cross_products);
        }

        // Old category attribute system removed - categories now linked via TreeCategories
        $attrArr = [];
        if (empty($attrArr)) {
            $input['attributes'] = null;
        } else {
            $jsonAttr = json_encode($attrArr);
            $input['attributes'] = $jsonAttr;
        }

        // Save base catalog item data (without merchant-specific fields)
        $data->fill($input)->save();

        // Create merchant_item entry for the merchant
        if ($merchantId > 0) {
            MerchantItem::create([
                'catalog_item_id' => $data->id,
                'user_id' => $merchantId,
                'brand_quality_id' => $brandQualityId,
                'price' => $basePrice,
                'previous_price' => $basePreviousPrice,
                'stock' => (int) $request->input('stock', 0),
                'minimum_qty' => $request->input('minimum_qty') ?: null,
                'whole_sell_qty' => !empty($request->whole_sell_qty) ? implode(',', $request->whole_sell_qty) : null,
                'whole_sell_discount' => !empty($request->whole_sell_discount) ? implode(',', $request->whole_sell_discount) : null,
                'ship' => $request->input('ship') ?: null,
                'item_condition' => $request->input('item_condition') ?? 0,
                'color_all' => !empty($request->color_all) ? implode(',', $request->color_all) : null,
                'status' => 1
            ]);
        }

        // Set Slug
        $catalogItem = CatalogItem::find($data->id);
        if ($catalogItem->type != 'Physical' || $request->type != "Listing") {
            $catalogItem->slug = Str::slug($data->name, '-') . '-' . strtolower(Str::random(3) . $data->id . Str::random(3));
        } else {
            $catalogItem->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
        }

        // Set Thumbnail
        $img = Image::make('assets/images/catalogItems/' . $catalogItem->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save('assets/images/thumbnails/' . $thumbnail);
        $catalogItem->thumbnail = $thumbnail;
        $catalogItem->update();

        // Add To Gallery If any
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                if (in_array($key, $request->galval)) {
                    $gallery = new Gallery;
                    $name = time() . \Str::random(8) . str_replace(' ', '', $file->getClientOriginalExtension());
                    $file->move('assets/images/galleries', $name);
                    $gallery['photo'] = $name;
                    $gallery['catalog_item_id'] = $lastid;
                    $gallery->save();
                }
            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __("New CatalogItem Added Successfully.") . '<a href="' . route('admin-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function import()
    {
        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $sign = $this->curr;

        // Get merchants list for dropdown (only verified active merchants - is_merchant=2 means verified)
        $merchants = \App\Models\User::where('is_merchant', 2)->where('status', 1)->get();

        return view('admin.catalog-item.catalogitemcsv', compact('cats', 'sign', 'merchants'));
    }

    //*** POST Request
    public function importSubmit(Request $request)
    {
        $log = "";
        //--- Validation Section
        $rules = [
            'csvfile' => 'required|mimes:csv,txt',
            'merchant_id' => 'required|exists:users,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $filename = '';
        if ($file = $request->file('csvfile')) {
            $filename = time() . '-' . $file->getClientOriginalExtension();
            $file->move('assets/temp_files', $filename);
        }

        $datas = "";

        $file = fopen(public_path('assets/temp_files/' . $filename), "r");
        $i = 1;

        while (($line = fgetcsv($file)) !== false) {

            if ($i != 1) {

                if (!CatalogItem::where('sku', $line[0])->exists()) {
                    //--- Validation Section Ends

                    //--- Logic Section
                    $data = new CatalogItem;
                    $sign = Currency::where('is_default', '=', 1)->first();

                    $input['type'] = 'Physical';
                    $input['sku'] = $line[0];

                    // Old category system removed - now using TreeCategories
                    // category_id, subcategory_id, childcategory_id no longer used
                    // Categories are linked via parts tables instead

                        $input['photo'] = $line[5];
                        $input['name'] = $line[4];
                        $input['details'] = $line[6];
                        $input['color'] = $line[13];
                        // Store prices temporarily for merchant_item creation
                        $csvPrice = $line[7];
                        $csvPreviousPrice = $line[8] != "" ? $line[8] : null;
                        $csvStock = $line[9];
                        $input['size'] = $line[10];
                        $input['size_qty'] = $line[11];
                        $input['youtube'] = $line[15];
                        $input['policy'] = $line[16];
                        $input['meta_tag'] = $line[17];
                        $input['meta_description'] = $line[18];
                        $input['tags'] = $line[14];
                        // item_type moved to merchant_items
                        $csvItemType = $line[19] ?? 'normal';
                        // affiliate_link moved to merchant_items
                        $csvAffiliateLink = $line[20] ?? null;
                        $input['slug'] = Str::slug($input['name'], '-') . '-' . strtolower($input['sku']);

                        $image_url = $line[5];

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $image_url);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_HEADER, true);
                        curl_setopt($ch, CURLOPT_NOBODY, true);

                        $content = curl_exec($ch);
                        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

                        $thumb_url = '';

                        if (strpos($contentType, 'image/') !== false) {
                            $fimg = Image::make($line[5])->resize(800, 800);
                            $fphoto = time() . Str::random(8) . '.jpg';
                            $fimg->save(public_path() . '/assets/images/catalogItems/' . $fphoto);
                            $input['photo'] = $fphoto;
                            $thumb_url = $line[5];
                        } else {
                            $fimg = Image::make(public_path() . '/assets/images/noimage.png')->resize(800, 800);
                            $fphoto = time() . Str::random(8) . '.jpg';
                            $fimg->save(public_path() . '/assets/images/catalogItems/' . $fphoto);
                            $input['photo'] = $fphoto;
                            $thumb_url = public_path() . '/assets/images/noimage.png';
                        }

                        $timg = Image::make($thumb_url)->resize(285, 285);
                        $thumbnail = time() . Str::random(8) . '.jpg';
                        $timg->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
                        $input['thumbnail'] = $thumbnail;

                        // Convert Price According to Currency
                        $convertedPrice = ($csvPrice / $sign->value);
                        $convertedPreviousPrice = ($csvPreviousPrice / $sign->value);

                        // Save base catalog item data (without merchant-specific fields)
                        $data->fill($input)->save();

                        // Create merchant_item entry for selected merchant
                        MerchantItem::create([
                            'catalog_item_id' => $data->id,
                            'user_id' => $request->merchant_id,
                            'item_type' => $csvItemType,
                            'price' => $convertedPrice,
                            'previous_price' => $convertedPreviousPrice,
                            'stock' => (int) $csvStock,
                            'affiliate_link' => $csvAffiliateLink,
                            'status' => 1
                        ]);

                } else {
                    $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('Duplicate CatalogItem Code!') . "<br>";
                }
            }

            $i++;
        }
        fclose($file);

        //--- Redirect Section
        $msg = __('Bulk CatalogItem File Imported Successfully.') . $log;
        return response()->json($msg);
    }

    //*** GET Request
    public function edit($merchantItemId)
    {
        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->findOrFail($merchantItemId);
        $data = $merchantItem->catalogItem;
        $sign = $this->curr;

        // Get merchants list for dropdown (merchants + admins with ID=1)
        $merchants = \App\Models\User::where('is_merchant', 1)->orWhere('id', 1)->get();

        // Get quality brands for dropdown
        $qualityBrands = \App\Models\QualityBrand::all();

        if ($data->type == 'Digital') {
            return view('admin.catalog-item.edit.digital', compact('cats', 'data', 'merchantItem', 'sign', 'merchants', 'qualityBrands'));
        } elseif ($data->type == 'License') {
            return view('admin.catalog-item.edit.license', compact('cats', 'data', 'merchantItem', 'sign', 'merchants', 'qualityBrands'));
        } elseif ($data->type == 'Listing') {
            return view('admin.catalog-item.edit.listing', compact('cats', 'data', 'merchantItem', 'sign', 'merchants', 'qualityBrands'));
        } else {
            return view('admin.catalog-item.edit.physical', compact('cats', 'data', 'merchantItem', 'sign', 'merchants', 'qualityBrands'));
        }
    }

    //*** POST Request
    public function update(Request $request, $merchantItemId)
    {
        //--- Validation Section
        $rules = [
            'file' => 'mimes:zip',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //-- Logic Section
        $merchantItem = MerchantItem::findOrFail($merchantItemId);
        $data = CatalogItem::findOrFail($merchantItem->catalog_item_id);
        $sign = $this->curr;
        $input = $request->all();

        //Check Types
        if ($request->type_check == 1) {
            $input['link'] = null;
        } else {
            if ($data->file != null) {
                if (file_exists(public_path() . '/assets/files/' . $data->file)) {
                    unlink(public_path() . '/assets/files/' . $data->file);
                }
            }
            $input['file'] = null;
        }

        // Check Physical
        if ($data->type == "Physical" || $data->type == "Listing") {
            //--- Validation Section
            $rules = ['sku' => 'min:8|unique:catalog_items,sku,' . $data->id];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            // Check Condition
            if ($request->item_condition_check == "") {
                $input['item_condition'] = 0;
            }

            // Check Preorderd
            if ($request->preordered_check == "") {
                $input['preordered'] = 0;
            }

            // Check Minimum Qty
            if ($request->minimum_qty_check == "") {
                $input['minimum_qty'] = null;
            }

            // Check Size
            if (empty($request->stock_check)) {
                $input['stock_check'] = 0;
                $input['size'] = null;
                $input['size_qty'] = null;
            } else {
                if (in_array(null, $request->size) || in_array(null, $request->size_qty)) {
                    $input['stock_check'] = 0;
                    $input['size'] = null;
                    $input['size_qty'] = null;
                } else {
                    $input['stock_check'] = 1;
                    $input['size'] = implode(',', $request->size);
                    $input['size_qty'] = implode(',', $request->size_qty);
                }
            }

            if (empty($request->color_check)) {
                $input['color_all'] = null;
            } else {
                $input['color_all'] = implode(',', $request->color_all);
            }

            // Check Measure
            if ($request->measure_check == "") {
                $input['measure'] = null;
            }
        }

        // Check Seo
        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', $request->meta_tag);
            }
        }

        // Check License
        if ($data->type == "License") {

            if (!in_array(null, $request->license) && !in_array(null, $request->license_qty)) {
                $input['license'] = implode(',,', $request->license);
                $input['license_qty'] = implode(',', $request->license_qty);
            } else {
                if (in_array(null, $request->license) || in_array(null, $request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $license = explode(',,', $data->license);
                    $license_qty = explode(',', $data->license_qty);
                    $input['license'] = implode(',,', $license);
                    $input['license_qty'] = implode(',', $license_qty);
                }
            }
        }

        if (!in_array(null, $request->features)) {
            $input['features'] = implode(',', str_replace(',', ' ', $request->features));
        } else {
            if (in_array(null, $request->features)) {
                $input['features'] = null;
            } else {
                $features = explode(',', $data->features);
                $input['features'] = implode(',', $features);
            }
        }

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        }
        if (empty($request->tags)) {
            $input['tags'] = null;
        }

        // Legacy fields removed - prices now handled via MerchantItem
        $basePrice = isset($input['price']) ? ($input['price'] / $sign->value) : 0;
        $basePreviousPrice = isset($input['previous_price']) ? ($input['previous_price'] / $sign->value) : null;

        // Remove legacy fields from catalog item table
        unset($input['price'], $input['previous_price'], $input['stock'], $input['user_id']);

        // store filtering attributes for physical catalog item
        // Old category attribute system removed - categories now linked via TreeCategories
        $attrArr = [];
        if (empty($attrArr)) {
            $input['attributes'] = null;
        } else {
            $jsonAttr = json_encode($attrArr);
            $input['attributes'] = $jsonAttr;
        }
        if ($request->cross_products) {
            $input['cross_products'] = implode(',', $request->cross_products);
        }
        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);

        $data->update($input);

        // Update merchant_item entry
        $merchantItem->update([
            'user_id' => (int) ($request->input('merchant_id') ?? $merchantItem->user_id),
            'brand_quality_id' => $request->input('brand_quality_id') ?: null,
            'price' => $basePrice,
            'previous_price' => $basePreviousPrice,
            'stock' => $request->input('stock') !== null ? (int) $request->input('stock') : null,
            'minimum_qty' => $request->input('minimum_qty') ?: null,
            'whole_sell_qty' => !empty($request->whole_sell_qty) ? implode(',', $request->whole_sell_qty) : null,
            'whole_sell_discount' => !empty($request->whole_sell_discount) ? implode(',', $request->whole_sell_discount) : null,
            'ship' => $request->input('ship') ?: null,
            'item_condition' => $request->input('item_condition') ?? 0,
            'color_all' => !empty($request->color_all) ? implode(',', $request->color_all) : null,
        ]);
        //-- Logic Section Ends

        //--- Redirect Section
        $msg = __("CatalogItem Updated Successfully.") . '<a href="' . route('admin-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function feature($id)
    {
        $data = CatalogItem::findOrFail($id);
        return view('admin.catalog-item.highlight', compact('data'));
    }

    //*** POST Request
    public function featuresubmit(Request $request, $id)
    {
        //-- Logic Section
        $data = CatalogItem::findOrFail($id);
        $input = $request->all();
        if ($request->featured == "") {
            $input['featured'] = 0;
        }
        if ($request->hot == "") {
            $input['hot'] = 0;
        }
        if ($request->best == "") {
            $input['best'] = 0;
        }
        if ($request->top == "") {
            $input['top'] = 0;
        }
        if ($request->latest == "") {
            $input['latest'] = 0;
        }
        if ($request->big == "") {
            $input['big'] = 0;
        }
        if ($request->trending == "") {
            $input['trending'] = 0;
        }
        if ($request->sale == "") {
            $input['sale'] = 0;
        }
        if ($request->is_discount == "") {
            $input['is_discount'] = 0;
            $input['discount_date'] = null;
        } else {
            $input['discount_date'] = \Carbon\Carbon::parse($input['discount_date'])->format('Y-m-d');
        }

        $data->update($input);
        //-- Logic Section Ends

        //--- Redirect Section
        $msg = __('Highlight Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function destroy($id)
    {
        $data = CatalogItem::findOrFail($id);
        if ($data->galleries->count() > 0) {
            foreach ($data->galleries as $gal) {
                if (file_exists(public_path() . '/assets/images/galleries/' . $gal->photo)) {
                    unlink(public_path() . '/assets/images/galleries/' . $gal->photo);
                }
                $gal->delete();
            }
        }

        if ($data->reports->count() > 0) {
            foreach ($data->reports as $gal) {
                $gal->delete();
            }
        }

        if ($data->catalogReviews->count() > 0) {
            foreach ($data->catalogReviews as $gal) {
                $gal->delete();
            }
        }
        if ($data->favorites->count() > 0) {
            foreach ($data->favorites as $gal) {
                $gal->delete();
            }
        }
        if ($data->clicks->count() > 0) {
            foreach ($data->clicks as $gal) {
                $gal->delete();
            }
        }
        if ($data->comments->count() > 0) {
            foreach ($data->comments as $gal) {
                if ($gal->replies->count() > 0) {
                    foreach ($gal->replies as $key) {
                        $key->delete();
                    }
                }
                $gal->delete();
            }
        }

        if (!filter_var($data->photo, FILTER_VALIDATE_URL)) {
            if ($data->photo) {
                if (file_exists(public_path() . '/assets/images/catalogItems/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/catalogItems/' . $data->photo);
                }
            }
        }

        if (file_exists(public_path() . '/assets/images/thumbnails/' . $data->thumbnail) && $data->thumbnail != "") {
            unlink(public_path() . '/assets/images/thumbnails/' . $data->thumbnail);
        }

        if ($data->file != null) {
            if (file_exists(public_path() . '/assets/files/' . $data->file)) {
                unlink(public_path() . '/assets/files/' . $data->file);
            }
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('CatalogItem Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function catalog($id1, $id2)
    {
        $data = CatalogItem::findOrFail($id1);
        $data->is_catalog = $id2;
        $data->update();
        if ($id2 == 1) {
            $msg = "CatalogItem added to catalog successfully.";
        } else {
            $msg = "CatalogItem removed from catalog successfully.";
        }
        return response()->json($msg);
    }

    public function settingUpdate(Request $request)
    {
        //--- Logic Section
        $input = $request->all();
        $data = \App\Models\Muaadhsetting::findOrFail(1);

        if (!empty($request->catalog_item_page)) {
            $input['item_page'] = implode(',', $request->catalog_item_page);
        } else {
            $input['item_page'] = null;
        }

        if (!empty($request->favorite_page)) {
            $input['favorite_page'] = implode(',', $request->favorite_page);
        } else {
            $input['favorite_page'] = null;
        }

        cache()->forget('muaadhsettings');

        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function getAttributes(Request $request)
    {
        $model = '';
        if ($request->type == 'category') {
            $model = 'App\Models\Category';
        } elseif ($request->type == 'subcategory') {
            $model = 'App\Models\Subcategory';
        } elseif ($request->type == 'childcategory') {
            $model = 'App\Models\Childcategory';
        }

        $attributes = Attribute::where('attributable_id', $request->id)->where('attributable_type', $model)->get();
        $attrOptions = [];
        foreach ($attributes as $key => $attribute) {
            $options = AttributeOption::where('attribute_id', $attribute->id)->get();
            $attrOptions[] = ['attribute' => $attribute, 'options' => $options];
        }
        return response()->json($attrOptions);
    }

    /**
     * @deprecated category_id column removed from catalog_items
     */
    public function getCrossCatalogItem($catId)
    {
        // category_id column removed - return empty collection
        $crossCatalogItems = collect();
        return view('load.cross_catalog_item', compact('crossCatalogItems'));
    }

}
