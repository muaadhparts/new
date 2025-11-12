<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Currency;
use App\Models\Gallery;
use App\Models\Product;
use App\Models\Subcategory;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use Validator;

class ProductController extends AdminBaseController
{
    //*** JSON Request
    // داخل class ProductController extends AdminBaseController

    public function datatables(Request $request)
    {
        // // dd(['admin_datatables' => true]); // اختباري

        if ($request->type == 'all') {
            $datas = \App\Models\Product::with('brand')->whereProductType('normal')->latest('id')->limit(50);
        } elseif ($request->type == 'deactive') {
            $datas = \App\Models\Product::with('brand')->whereProductType('normal')->whereStatus(0)->latest('id')->limit(50);
        } else {
            $datas = \App\Models\Product::with('brand')->latest('id')->limit(50);
        }

        return \Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('sku', 'like', "%{$keyword}%")
                      ->orWhere('label_ar', 'like', "%{$keyword}%")
                      ->orWhere('label_en', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('name', function (\App\Models\Product $data) {
                // استخرج أول/أرخص عرض بائع نشط لهذا المنتج (المتوفر أولاً ثم الأرخص)
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                    ->orderBy('price')
                    ->first();

                $vendorId = optional($mp)->user_id ?: 0;
                $merchantProductId = optional($mp)->id ?: 0;

                $prodLink = ($vendorId && $merchantProductId)
                    ? route('front.product', ['slug' => $data->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
                    : '#';

                // Use the product name component WITHOUT showing SKU
                $nameComponent = view('components.product-name', [
                    'product' => $data,
                    'vendorId' => $vendorId,
                    'merchantProductId' => $merchantProductId,
                    'target' => '_blank',
                    'showSku' => false  // إخفاء SKU من عمود الاسم
                ])->render();

                $id  = '<small>' . __("ID") . ': <a href="' . $prodLink . '" target="_blank">' . sprintf("%'.08d", $data->id) . '</a></small>';

                // checkVendor() تم تحديثها لقراءة أول بائع نشط
                return $nameComponent . '<br>' . $id . $data->checkVendor();
            })
            ->addColumn('sku', function (\App\Models\Product $data) {
                if ($data->type != 'Physical' || !$data->sku) {
                    return '-';
                }

                // في صفحات الأدمن، نستخدم route('search.result', sku)
                $prodLink = route('search.result', $data->sku);
                return '<a href="' . $prodLink . '" target="_blank">' . $data->sku . '</a>';
            })
            ->editColumn('price', function (\App\Models\Product $data) {
                // أقل سعر نشط من عروض البائعين
                $min = DB::table('merchant_products')
                    ->where('product_id', $data->id)
                    ->where('status', 1)
                    ->min('price');

                if ($min === null) {
                    return \PriceHelper::showAdminCurrencyPrice(0);
                }

                // عمولة المنصة (ثابت + نسبة)
                $gs = cache()->remember(
                    'generalsettings',
                    now()->addDay(),
                    fn () => DB::table('generalsettings')->first()
                );

                $base = (float) $min
                      + (float) $gs->fixed_commission
                      + ((float) $min * (float) $gs->percentage_commission / 100);

                // إظهار حسب عملة الأدمن
                return \PriceHelper::showAdminCurrencyPrice($base * $this->curr->value);
            })
            ->editColumn('stock', function (\App\Models\Product $data) {
                // مجموع مخزون العروض النشطة
                $sum = DB::table('merchant_products')
                    ->where('product_id', $data->id)
                    ->where('status', 1)
                    ->sum('stock');

                if ((int) $sum === 0) {
                    return __("Out Of Stock");
                }
                return $sum;
            })
            ->editColumn('photo', function (\App\Models\Product $data) {
                $photo = filter_var($data->photo, FILTER_VALIDATE_URL)
                    ? $data->photo
                    : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png'));
                return '<img src="' . $photo . '" alt="Image" class="img-thumbnail" style="width:80px">';
            })
            ->addColumn('brand', function (\App\Models\Product $data) {
                return $data->brand ? $data->brand->name : __('N/A');
            })
            ->addColumn('quality_brand', function (\App\Models\Product $data) {
                // Get the first active merchant product with quality brand
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->with('qualityBrand')
                    ->whereNotNull('brand_quality_id')
                    ->first();

                return $mp && $mp->qualityBrand ? $mp->qualityBrand->display_name : __('N/A');
            })
            ->addColumn('vendor', function (\App\Models\Product $data) {
                // Get the first active merchant product with vendor
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->with('user')
                    ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                    ->orderBy('price')
                    ->first();

                return $mp && $mp->user ? $mp->user->shop_name : __('N/A');
            })
            ->addColumn('status', function (\App\Models\Product $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s     = $data->status == 1 ? 'selected' : '';
                $ns    = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list">
                            <select class="process select droplinks ' . $class . '">
                                <option data-val="1" value="' . route('admin-prod-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                                <option data-val="0" value="' . route('admin-prod-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                            </select>
                        </div>';
            })
            ->addColumn('action', function (\App\Models\Product $data) {
                $catalog = $data->type == 'Physical'
                    ? ($data->is_catalog == 1
                        ? '<a href="javascript:;" data-href="' . route('admin-prod-catalog', ['id1' => $data->id, 'id2' => 0]) . '" data-toggle="modal" data-target="#catalog-modal" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Remove Catalog") . '</a>'
                        : '<a href="javascript:;" data-href="' . route('admin-prod-catalog', ['id1' => $data->id, 'id2' => 1]) . '" data-toggle="modal" data-target="#catalog-modal"> <i class="fas fa-plus"></i> ' . __("Add To Catalog") . '</a>')
                    : '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                        <div class="action-list">
                            <a href="' . route('admin-prod-edit', $data->id) . '"><i class="fas fa-edit"></i> ' . __("Edit") . '</a>
                            <a href="javascript" class="set-gallery" data-toggle="modal" data-target="#setgallery"><input type="hidden" value="' . $data->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>'
                            . $catalog .
                            '<a data-href="' . route('admin-prod-feature', $data->id) . '" class="feature" data-toggle="modal" data-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a>
                            <a href="javascript:;" data-href="' . route('admin-prod-delete', $data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a>
                        </div></div>';
            })
            ->rawColumns(['name', 'sku', 'status', 'action', 'photo', 'brand', 'quality_brand', 'vendor'])
            ->toJson();
    }

    //*** JSON Request
    public function catalogdatatables()
    {
        // // dd(['admin_catalog_datatables' => true]); // اختباري

        $datas = Product::with('brand')->where('is_catalog', '=', 1)->orderBy('id', 'desc');

        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('name', function (Product $data) {
                // استخرج أول/أرخص عرض بائع نشط لهذا المنتج (المتوفر أولاً ثم الأرخص)
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                    ->orderBy('price')
                    ->first();

                $vendorId = optional($mp)->user_id ?: 0;
                $merchantProductId = optional($mp)->id ?: 0;

                $prodLink = ($vendorId && $merchantProductId)
                    ? route('front.product', ['slug' => $data->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
                    : '#';

                // Use the product name component WITHOUT showing SKU
                $nameComponent = view('components.product-name', [
                    'product' => $data,
                    'vendorId' => $vendorId,
                    'merchantProductId' => $merchantProductId,
                    'target' => '_blank',
                    'showSku' => false  // إخفاء SKU من عمود الاسم
                ])->render();

                $id  = '<small>' . __("ID") . ': <a href="' . $prodLink . '" target="_blank">' . sprintf("%'.08d", $data->id) . '</a></small>';

                // checkVendor() تم تحديثها لقراءة أول بائع نشط
                return $nameComponent . '<br>' . $id . $data->checkVendor();
            })
            ->addColumn('sku', function (\App\Models\Product $data) {
                if ($data->type != 'Physical' || !$data->sku) {
                    return '-';
                }

                // في صفحات الأدمن، نستخدم route('search.result', sku)
                $prodLink = route('search.result', $data->sku);
                return '<a href="' . $prodLink . '" target="_blank">' . $data->sku . '</a>';
            })
            ->addColumn('status', function (Product $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->status == 1 ? 'selected' : '';
                $ns = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('admin-prod-status', ['id1' => $data->id, 'id2' => 1]) . '" ' . $s . '>' . __("Activated") . '</option><option data-val="0" value="' . route('admin-prod-status', ['id1' => $data->id, 'id2' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>/select></div>';
            })
            ->addColumn('brand', function (\App\Models\Product $data) {
                return $data->brand ? $data->brand->name : __('N/A');
            })
            ->addColumn('quality_brand', function (\App\Models\Product $data) {
                // Get the first active merchant product with quality brand
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->with('qualityBrand')
                    ->whereNotNull('brand_quality_id')
                    ->first();

                return $mp && $mp->qualityBrand ? $mp->qualityBrand->display_name : __('N/A');
            })
            ->addColumn('vendor', function (\App\Models\Product $data) {
                // Get the first active merchant product with vendor
                $mp = $data->merchantProducts()
                    ->where('status', 1)
                    ->with('user')
                    ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                    ->orderBy('price')
                    ->first();

                return $mp && $mp->user ? $mp->user->shop_name : __('N/A');
            })
            ->addColumn('action', function (Product $data) {
                return '<div class="godropdown"><button class="go-dropdown-toggle">  ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button><div class="action-list"><a href="' . route('admin-prod-edit', $data->id) . '"> <i class="fas fa-edit"></i> ' . __("Edit") . '</a><a href="javascript" class="set-gallery" data-toggle="modal" data-target="#setgallery"><input type="hidden" value="' . $data->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a><a data-href="' . route('admin-prod-feature', $data->id) . '" class="feature" data-toggle="modal" data-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a><a href="javascript:;" data-href="' . route('admin-prod-catalog', ['id1' => $data->id, 'id2' => 0]) . '" data-toggle="modal" data-target="#catalog-modal"><i class="fas fa-trash-alt"></i> ' . __("Remove Catalog") . '</a></div></div>';
            })
            ->rawColumns(['name', 'sku', 'status', 'action', 'brand', 'quality_brand', 'vendor'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function productscatalog()
    {
        return view('admin.product.catalog');
    }

    public function index()
    {
        return view('admin.product.index');
    }

    public function types()
    {
        return view('admin.product.types');
    }

    public function deactive()
    {
        return view('admin.product.deactive');
    }

    public function productsettings()
    {
        return view('admin.product.settings');
    }

    //*** GET Request
    public function create($slug)
    {
        $cats = Category::all();
        $sign = $this->curr;
        if ($slug == 'physical') {
            return view('admin.product.create.physical', compact('cats', 'sign'));
        } else if ($slug == 'digital') {
            return view('admin.product.create.digital', compact('cats', 'sign'));
        } else if (($slug == 'license')) {
            return view('admin.product.create.license', compact('cats', 'sign'));
        } else if (($slug == 'listing')) {
            return view('admin.product.create.listing', compact('cats', 'sign'));
        }
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = Product::findOrFail($id1);
        $data->status = $id2;
        $data->update();
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

        $data = Product::findOrFail($id);

        //--- Validation Section Ends
        $image = $request->image;
        list($type, $image) = explode(';', $image);
        list(, $image) = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time() . Str::random(8) . '.png';
        $path = 'assets/images/products/' . $image_name;
        file_put_contents($path, $image);
        if ($data->photo != null) {
            if (file_exists(public_path() . '/assets/images/products/' . $data->photo)) {
                unlink(public_path() . '/assets/images/products/' . $data->photo);
            }
        }
        $input['photo'] = $image_name;
        $data->update($input);
        if ($data->thumbnail != null) {
            if (file_exists(public_path() . '/assets/images/thumbnails/' . $data->thumbnail)) {
                unlink(public_path() . '/assets/images/thumbnails/' . $data->thumbnail);
            }
        }

        $img = Image::make('assets/images/products/' . $data->photo)->resize(285, 285);
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
        $data = new Product;
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
        $path = 'assets/images/products/' . $image_name;
        file_put_contents($path, $image);
        $input['photo'] = $image_name;

        if ($request->type == "Physical" || $request->type == "Listing") {
            $rules = ['sku' => 'min:8|unique:products'];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }

            // Handle size data (belongs to products table)
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

            // Colors belong to merchant_products, not products
            // This will be handled separately in merchant product creation

            // whole_sell_qty and whole_sell_discount belong to merchant_products, not products
            // This will be handled separately in merchant product creation

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

        // Legacy fields removed - prices now handled via MerchantProduct
        // Store base product info only, vendor-specific data goes to merchant_products
        $basePrice = isset($input['price']) ? ($input['price'] / $sign->value) : 0;
        $basePreviousPrice = isset($input['previous_price']) ? ($input['previous_price'] / $sign->value) : null;

        // Remove legacy fields from product table
        unset($input['price'], $input['previous_price'], $input['stock'], $input['user_id']);
        if ($request->cross_products) {
            $input['cross_products'] = implode(',', $request->cross_products);
        }

        $attrArr = [];
        if (!empty($request->category_id)) {
            $catAttrs = Attribute::where('attributable_id', $request->category_id)->where('attributable_type', 'App\Models\Category')->get();
            if (!empty($catAttrs)) {
                foreach ($catAttrs as $key => $catAttr) {
                    $in_name = $catAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $catAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

        if (!empty($request->subcategory_id)) {
            $subAttrs = Attribute::where('attributable_id', $request->subcategory_id)->where('attributable_type', 'App\Models\Subcategory')->get();
            if (!empty($subAttrs)) {
                foreach ($subAttrs as $key => $subAttr) {
                    $in_name = $subAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $subAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

        if (!empty($request->childcategory_id)) {
            $childAttrs = Attribute::where('attributable_id', $request->childcategory_id)->where('attributable_type', 'App\Models\Childcategory')->get();
            if (!empty($childAttrs)) {
                foreach ($childAttrs as $key => $childAttr) {
                    $in_name = $childAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $childAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

        if (empty($attrArr)) {
            $input['attributes'] = null;
        } else {
            $jsonAttr = json_encode($attrArr);
            $input['attributes'] = $jsonAttr;
        }

        // Save base product data (without vendor-specific fields)
        $data->fill($input)->save();

        // Create merchant_product entry for the vendor
        $vendorId = (int) ($request->input('user_id') ?? 0);
        if ($vendorId > 0) {
            \App\Models\MerchantProduct::updateOrCreate(
                ['product_id' => $data->id, 'user_id' => $vendorId],
                [
                    'price' => $basePrice,
                    'previous_price' => $basePreviousPrice,
                    'stock' => (int) $request->input('stock', 0),
                    'minimum_qty' => $request->input('minimum_qty') ?: null,
                    'whole_sell_qty' => $input['whole_sell_qty'] ?? null,
                    'whole_sell_discount' => $input['whole_sell_discount'] ?? null,
                    'status' => 1
                ]
            );
        }

        // Set SLug
        $prod = Product::find($data->id);
        if ($prod->type != 'Physical' || $request->type != "Listing") {
            $prod->slug = Str::slug($data->name, '-') . '-' . strtolower(Str::random(3) . $data->id . Str::random(3));
        } else {
            $prod->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
        }

        // Set Thumbnail
        $img = Image::make('assets/images/products/' . $prod->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save('assets/images/thumbnails/' . $thumbnail);
        $prod->thumbnail = $thumbnail;
        $prod->update();

        // Add To Gallery If any
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                if (in_array($key, $request->galval)) {
                    $gallery = new Gallery;
                    $name = time() . \Str::random(8) . str_replace(' ', '', $file->getClientOriginalExtension());
                    $file->move('assets/images/galleries', $name);
                    $gallery['photo'] = $name;
                    $gallery['product_id'] = $lastid;
                    $gallery->save();
                }
            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __("New Product Added Successfully.") . '<a href="' . route('admin-prod-index') . '">' . __("View Product Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function import()
    {
        $cats = Category::all();
        $sign = $this->curr;
        return view('admin.product.productcsv', compact('cats', 'sign'));
    }

    //*** POST Request
    public function importSubmit(Request $request)
    {
        $log = "";
        //--- Validation Section
        $rules = [
            'csvfile' => 'required|mimes:csv,txt',
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

                if (!Product::where('sku', $line[0])->exists()) {
                    //--- Validation Section Ends

                    //--- Logic Section
                    $data = new Product;
                    $sign = Currency::where('is_default', '=', 1)->first();

                    $input['type'] = 'Physical';
                    $input['sku'] = $line[0];

                    $input['category_id'] = null;
                    $input['subcategory_id'] = null;
                    $input['childcategory_id'] = null;

                    $mcat = Category::where(DB::raw('lower(name)'), strtolower($line[1]));

                    if ($mcat->exists()) {
                        $input['category_id'] = $mcat->first()->id;

                        if ($line[2] != "") {
                            $scat = Subcategory::where(DB::raw('lower(name)'), strtolower($line[2]));

                            if ($scat->exists()) {
                                $input['subcategory_id'] = $scat->first()->id;
                            }
                        }
                        if ($line[3] != "") {
                            $chcat = Childcategory::where(DB::raw('lower(name)'), strtolower($line[3]));

                            if ($chcat->exists()) {
                                $input['childcategory_id'] = $chcat->first()->id;
                            }
                        }

                        $input['photo'] = $line[5];
                        $input['name'] = $line[4];
                        $input['details'] = $line[6];
                        $input['color'] = $line[13];
                        // Store prices temporarily for merchant_product creation
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
                        $input['product_type'] = $line[19];
                        $input['affiliate_link'] = $line[20];
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
                            $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
                            $input['photo'] = $fphoto;
                            $thumb_url = $line[5];
                        } else {
                            $fimg = Image::make(public_path() . '/assets/images/noimage.png')->resize(800, 800);
                            $fphoto = time() . Str::random(8) . '.jpg';
                            $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
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

                        // Save base product data (without vendor-specific fields)
                        $data->fill($input)->save();

                        // Create merchant_product entry for imported product (assume admin user_id = 1)
                        \App\Models\MerchantProduct::create([
                            'product_id' => $data->id,
                            'user_id' => 1, // Admin/default vendor
                            'price' => $convertedPrice,
                            'previous_price' => $convertedPreviousPrice,
                            'stock' => (int) $csvStock,
                            'status' => 1
                        ]);

                    } else {
                        $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('No Category Found!') . "<br>";
                    }

                } else {
                    $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('Duplicate Product Code!') . "<br>";
                }
            }

            $i++;
        }
        fclose($file);

        //--- Redirect Section
        $msg = __('Bulk Product File Imported Successfully.') . $log;
        return response()->json($msg);
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);
        $sign = $this->curr;

        if ($data->type == 'Digital') {
            return view('admin.product.edit.digital', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'License') {
            return view('admin.product.edit.license', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'Listing') {
            return view('admin.product.edit.listing', compact('cats', 'data', 'sign'));
        } else {
            return view('admin.product.edit.physical', compact('cats', 'data', 'sign'));
        }
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        // return $request;
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
        $data = Product::findOrFail($id);
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
            $rules = ['sku' => 'min:8|unique:products,sku,' . $id];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            // Check Condition
            if ($request->product_condition_check == "") {
                $input['product_condition'] = 0;
            }

            // Check Preorderd
            if ($request->preordered_check == "") {
                $input['preordered'] = 0;
            }

            // Check Minimum Qty
            if ($request->minimum_qty_check == "") {
                $input['minimum_qty'] = null;
            }

            // Check Shipping Time
            // ship belongs to merchant_products, not products
            // This will be handled separately in merchant product creation

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

            // Check Whole Sale
            // whole_sell_qty and whole_sell_discount belong to merchant_products, not products
            // This will be handled separately in merchant product creation

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

        // colors field removed - now handled in merchant_products as color_all and color_price

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

        // Legacy fields removed - prices now handled via MerchantProduct
        $basePrice = isset($input['price']) ? ($input['price'] / $sign->value) : 0;
        $basePreviousPrice = isset($input['previous_price']) ? ($input['previous_price'] / $sign->value) : null;

        // Remove legacy fields from product table
        unset($input['price'], $input['previous_price'], $input['stock'], $input['user_id']);

        // store filtering attributes for physical product
        $attrArr = [];
        if (!empty($request->category_id)) {
            $catAttrs = Attribute::where('attributable_id', $request->category_id)->where('attributable_type', 'App\Models\Category')->get();
            if (!empty($catAttrs)) {
                foreach ($catAttrs as $key => $catAttr) {
                    $in_name = $catAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $catAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

        if (!empty($request->subcategory_id)) {
            $subAttrs = Attribute::where('attributable_id', $request->subcategory_id)->where('attributable_type', 'App\Models\Subcategory')->get();
            if (!empty($subAttrs)) {
                foreach ($subAttrs as $key => $subAttr) {
                    $in_name = $subAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $subAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

        if (!empty($request->childcategory_id)) {
            $childAttrs = Attribute::where('attributable_id', $request->childcategory_id)->where('attributable_type', 'App\Models\Childcategory')->get();
            if (!empty($childAttrs)) {
                foreach ($childAttrs as $key => $childAttr) {
                    $in_name = $childAttr->input_name;
                    if ($request->has("$in_name")) {
                        $attrArr["$in_name"]["values"] = $request["$in_name"];
                        foreach ($request["$in_name" . "_price"] as $aprice) {
                            $ttt["$in_name" . "_price"][] = $aprice / $sign->value;
                        }
                        $attrArr["$in_name"]["prices"] = $ttt["$in_name" . "_price"];
                        $attrArr["$in_name"]["details_status"] = $childAttr->details_status ? 1 : 0;
                    }
                }
            }
        }

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

        // Update merchant_product entry for the vendor
        $vendorId = (int) ($request->input('user_id') ?? 0);
        if ($vendorId > 0) {
            \App\Models\MerchantProduct::updateOrCreate(
                ['product_id' => $data->id, 'user_id' => $vendorId],
                [
                    'price' => $basePrice,
                    'previous_price' => $basePreviousPrice,
                    'stock' => (int) $request->input('stock', 0),
                    'minimum_qty' => $request->input('minimum_qty') ?: null,
                    'whole_sell_qty' => $input['whole_sell_qty'] ?? null,
                    'whole_sell_discount' => $input['whole_sell_discount'] ?? null,
                    'status' => 1
                ]
            );
        }
        //-- Logic Section Ends

        //--- Redirect Section
        $msg = __("Product Updated Successfully.") . '<a href="' . route('admin-prod-index') . '">' . __("View Product Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function feature($id)
    {
        $data = Product::findOrFail($id);
        return view('admin.product.highlight', compact('data'));
    }

    //*** POST Request
    public function featuresubmit(Request $request, $id)
    {
        //-- Logic Section
        $data = Product::findOrFail($id);
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
        $data = Product::findOrFail($id);
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

        if ($data->ratings->count() > 0) {
            foreach ($data->ratings as $gal) {
                $gal->delete();
            }
        }
        if ($data->wishlists->count() > 0) {
            foreach ($data->wishlists as $gal) {
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
                if (file_exists(public_path() . '/assets/images/products/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/products/' . $data->photo);
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
        $msg = __('Product Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

        // PRODUCT DELETE ENDS
    }

    public function catalog($id1, $id2)
    {
        $data = Product::findOrFail($id1);
        $data->is_catalog = $id2;
        $data->update();
        if ($id2 == 1) {
            $msg = "Product added to catalog successfully.";
        } else {
            $msg = "Product removed from catalog successfully.";
        }
        return response()->json($msg);
    }

    public function settingUpdate(Request $request)
    {
        //--- Logic Section
        $input = $request->all();
        $data = \App\Models\Generalsetting::findOrFail(1);

        if (!empty($request->product_page)) {
            $input['product_page'] = implode(',', $request->product_page);
        } else {
            $input['product_page'] = null;
        }

        if (!empty($request->wishlist_page)) {
            $input['wishlist_page'] = implode(',', $request->wishlist_page);
        } else {
            $input['wishlist_page'] = null;
        }

        cache()->forget('generalsettings');

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

    public function getCrossProduct($catId)
    {
        $crossProducts = Product::where('category_id', $catId)->status(1)->get();
        return view('load.cross_product', compact('crossProducts'));
    }
}
