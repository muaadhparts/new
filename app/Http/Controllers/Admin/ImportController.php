<?php

namespace App\Http\Controllers\Admin;

use App\{
    Models\CatalogItem,
    Models\Currency,
    Models\Gallery
};
use App\Models\MerchantItem;

use Illuminate\{
    Http\Request,
    Support\Str
};

use Datatables;
use Validator;
use Image;
use DB;

class ImportController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        // فقط العناصر من نوع affiliate (product_type is now on merchant_items)
        $query = MerchantItem::with(['catalogItem.brand', 'user', 'qualityBrand'])
            ->where('product_type', 'affiliate');

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
                $product = $mp->product;
                if (!$product) return __('N/A');

                $prodLink = route('front.catalog-item', [
                    'slug' => $product->slug,
                    'merchant_id' => $mp->user_id,
                    'merchant_item_id' => $mp->id
                ]);

                $displayName = getLocalizedProductName($product);
                $sku = $product->sku ? '<br><small class="text-muted">' . __('SKU') . ': ' . $product->sku . '</small>' : '';
                $condition = $mp->product_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $sku . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
                $product = $mp->product;
                return $product && $product->brand ? getLocalizedBrandName($product->brand) : __('N/A');
            })
            ->addColumn('quality_brand', function (MerchantItem $mp) {
                return $mp->qualityBrand ? getLocalizedQualityName($mp->qualityBrand) : __('N/A');
            })
            ->addColumn('vendor', function (MerchantItem $mp) {
                if (!$mp->user) return __('N/A');
                $shopName = $mp->user->shop_name ?: $mp->user->name;
                return '<a href="' . route('admin-vendor-show', $mp->user_id) . '" target="_blank">' . $shopName . '</a>';
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
                        <option data-val="1" value="' . route('admin-merchant-product-status', ['id' => $mp->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('admin-merchant-product-status', ['id' => $mp->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mp) {
                $product = $mp->product;
                if (!$product) return '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('admin-import-edit', $product->id) . '"><i class="fas fa-edit"></i> ' . __("Edit") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $product->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>
                        <a data-href="' . route('admin-prod-feature', $product->id) . '" class="feature" data-bs-toggle="modal" data-bs-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a>
                        <a href="javascript:;" data-href="' . route('admin-affiliate-prod-delete', $product->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'vendor'])
            ->toJson();
    }

    public function index(){
        return view('admin.productimport.index');
    }

    //*** GET Request
    public function createImport()
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $sign = $this->curr;
        return view('admin.productimport.createone',compact('cats','sign'));
    }

    //*** GET Request
    public function importCSV()
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $sign = $this->curr;
        return view('admin.productimport.importcsv',compact('cats','sign'));
    }

    //*** POST Request
    public function uploadUpdate(Request $request,$id)
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
        list(, $image)      = explode(',', $image);
        $image = base64_decode($image);
        $image_name = time().Str::random(8).'.png';
        $path = 'assets/images/products/'.$image_name;
        file_put_contents($path, $image);

        if($data->photo != null)
        {
            if (file_exists(public_path().'/assets/images/products/'.$data->photo)) {
                unlink(public_path().'/assets/images/products/'.$data->photo);
            }
        }

        $input['photo'] = $image_name;
        $data->update($input);

        return response()->json(['status'=>true,'file_name' => $image_name]);
    }

    //*** POST Request
    public function store(Request $request)
    {
        if($request->image_source == 'file')
        {
            //--- Validation Section
            $rules = [
                   'photo'      => 'required',
                   'file'       => 'mimes:zip'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
              return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends
        }

        //--- Logic Section
        $data = new CatalogItem;
        $sign = $this->curr;
        $input = $request->all();

        // Check File
        if ($file = $request->file('file'))
        {
            $name = time().\Str::random(8).str_replace(' ', '', $file->getClientOriginalExtension());
            $file->move('assets/files',$name);
            $input['file'] = $name;
        }

        // Photo (file or link)
        $input['photo'] = "";
        if($request->photo != ""){
            $image = $request->photo;
            list($type, $image) = explode(';', $image);
            list(, $image)      = explode(',', $image);
            $image = base64_decode($image);
            $image_name = time().Str::random(8).'.png';
            $path = 'assets/images/products/'.$image_name;
            file_put_contents($path, $image);
            $input['photo'] = $image_name;
        }else{
            $input['photo'] = $request->photolink;
        }

        // Check Physical meta (identity only — لا نكتب price/stock/size في products)
        if($request->type == "Physical")
        {
            //--- Validation Section
            $rules = ['sku' => 'min:8|unique:products'];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            // Identity flags (لن تُكتب أسعار/مخزون على products)
            if ($request->product_condition_check == ""){ $input['product_condition'] = 0; }
            if ($request->preordered_check == ""){ $input['preordered'] = 0; }
            if ($request->shipping_time_check == ""){ $input['ship'] = null; }

            // لا تكتب هذه في products
            unset($input['stock_check'], $input['size'], $input['size_qty'], $input['size_price'], $input['color']);
            // Colors belong to merchant_products, not products
            // This will be handled separately in merchant product creation

            // قياسات عامة للهوية (إن أردت إبقاءها في products: size_all)
            if(empty($request->size_check)) {
                $input['size_all'] = null;
            } else {
                $input['size_all'] = implode(',', (array)$request->size_all);
            }

            // القياسات/الخصومات بالجملة ستذهب إلى MerchantItem لاحقًا
        }

        // Check Seo
        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', (array)$request->meta_tag);
            }
        }

        // License (هوية فقط)
        if($request->type == "License")
        {
            if(in_array(null, (array)$request->license) || in_array(null, (array)$request->license_qty)) {
                $input['license'] = null;
                $input['license_qty'] = null;
            } else {
                $input['license'] = implode(',,', $request->license);
                $input['license_qty'] = implode(',', $request->license_qty);
            }
        }

        // Features / Colors (هوية)
        if(in_array(null, (array)$request->features)) {
            $input['features'] = null;
        } else {
            $input['features'] = implode(',', str_replace(',',' ', $request->features));
        }

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', (array)$request->tags);
        }

        // لا نكتب price/previous_price/product_type إلى products
        unset($input['price'], $input['previous_price'], $input['stock'], $input['product_type']);

        // Save Product (هوية فقط)
        $data->fill($input)->save();

        // Set SLug
        $catalogItem = CatalogItem::find($data->id);
        if($prod->type != 'Physical'){
            $prod->slug = Str::slug($data->name,'-').'-'.strtolower(Str::random(3).$data->id.Str::random(3));
        }
        else {
            $prod->slug = Str::slug($data->name,'-').'-'.strtolower($data->sku);
        }

        // Thumbnail
        $fimageData = public_path().'/assets/images/products/'.$prod->photo;
        if(filter_var($prod->photo, FILTER_VALIDATE_URL)){
            $fimageData = $prod->photo;
        }

        // التحقق من وجود الصورة قبل إنشاء الـ thumbnail
        try {
            if (!empty($prod->photo) && (filter_var($prod->photo, FILTER_VALIDATE_URL) || file_exists($fimageData))) {
                $img = Image::make($fimageData)->resize(285, 285);
                $thumbnail = time().Str::random(8).'.jpg';
                $img->save(public_path().'/assets/images/thumbnails/'.$thumbnail);
                $prod->thumbnail = $thumbnail;
            } else {
                // استخدام صورة افتراضية
                $prod->thumbnail = 'noimage.png';
            }
        } catch (\Exception $e) {
            \Log::warning('Could not create thumbnail for product: ' . $prod->id . ' - ' . $e->getMessage());
            $prod->thumbnail = 'noimage.png';
        }
        $prod->update();

        // إنشاء/تحديث عرض البائع (MerchantItem)
        $merchantId = (int) ($request->input('user_id') ?? $request->input('merchant_id') ?? 0);
        // // dd(['vendorId' => $merchantId, 'product_id' => $prod->id]); // اختباري
        if ($merchantId <= 0) {
            return response()->json(['errors' => ['vendor' => 'Vendor (user) is required']], 422);
        }

        // حسابات الأسعار للمخزن الأساسي
        $mpPrice         = (float) ($request->input('price', 0)) / $sign->value;
        $mpPreviousPrice = $request->filled('previous_price') ? ((float)$request->input('previous_price') / $sign->value) : null;

        // المقاسات / الكميات / أسعار المقاسات (تحويل للقيمة الأساسية)
        $mpSize      = null;
        $mpSizeQty   = null;
        $mpSizePrice = null;

        if (!empty($request->stock_check)) {
            if (!in_array(null, (array)$request->size) &&
                !in_array(null, (array)$request->size_qty) &&
                !in_array(null, (array)$request->size_price)) {

                $mpSize    = implode(',', (array)$request->size);
                $mpSizeQty = implode(',', (array)$request->size_qty);

                $size_prices = (array)$request->size_price;
                $s_price = [];
                foreach ($size_prices as $key => $sPrice) {
                    $s_price[$key] = ((float)$sPrice) / $sign->value;
                }
                $mpSizePrice = implode(',', $s_price);
            }
        }

        MerchantItem::updateOrCreate(
            ['product_id' => $prod->id, 'user_id' => $merchantId],
            [
                'product_type'        => 'affiliate',
                'price'               => $mpPrice,
                'previous_price'      => $mpPreviousPrice,
                'stock'               => (int) $request->input('stock', 0),
                'size'                => $mpSize,
                'size_qty'            => $mpSizeQty,
                'size_price'          => $mpSizePrice,
                'minimum_qty'         => $request->input('minimum_qty') ?: null,
                'whole_sell_qty'      => !empty($request->whole_sell_qty) ? implode(',', (array)$request->whole_sell_qty) : null,
                'whole_sell_discount' => !empty($request->whole_sell_discount) ? implode(',', (array)$request->whole_sell_discount) : null,
                'preordered'          => (int) ($request->input('preordered') ?? 0),
                'product_condition'   => (int) ($request->input('product_condition') ?? 0),
                'ship'                => $request->input('ship') ?: null,
                'brand_quality_id'    => $request->input('brand_quality_id') ?: null,
                'status'              => 1,
            ]
        );

        // Add To Gallery If any
        $lastid = $data->id;
        if ($files = $request->file('gallery')){
            foreach ($files as  $key => $file){
                if(in_array($key, (array)$request->galval))
                {
                    $gallery = new Gallery;
                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $name = time() . Str::random(8) . '.' . $extension;
                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $img->save(public_path() . '/assets/images/galleries/' . $name);
                    $gallery['photo'] = $name;
                    $gallery['product_id'] = $lastid;
                    $gallery->save();
                }
            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __("New Affiliate Product Added Successfully.").'<a href="'.route('admin-import-index').'">'.__("View Product Lists.").'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = collect(); // Category system removed - using TreeCategories
        $data = CatalogItem::findOrFail($id);
        $sign = $this->curr;
        return view('admin.productimport.editone',compact('cats','data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        $catalogItem = CatalogItem::find($id);

        //--- Validation Section
        $rules = [
               'file' => 'mimes:zip'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //-- Logic Section
        $data = CatalogItem::findOrFail($id);
        $sign = $this->curr;
        $input = $request->all();

        //Check Types
        if($request->type_check == 1) {
            $input['link'] = null;
        } else {
            if($data->file!=null){
                if (file_exists(public_path().'/assets/files/'.$data->file)) {
                    unlink(public_path().'/assets/files/'.$data->file);
                }
            }
            $input['file'] = null;
        }

        // Photo source
        if($request->image_source == 'file'){
            $input['photo'] = $request->photo;
        }else{
            $input['photo'] = $request->photolink;
        }

        // Physical adjustments (هوية فقط)
        if($data->type == "Physical")
        {
            //--- Validation Section
            $rules = ['sku' => 'min:8|unique:products,sku,'.$id];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            if ($request->product_condition_check == ""){ $input['product_condition'] = 0; }
            if ($request->preordered_check == ""){ $input['preordered'] = 0; }
            if ($request->shipping_time_check == ""){ $input['ship'] = null; }

            // لا تكتب مقاسات/مخزون/أسعار على products
            if(empty($request->stock_check)) {
                $input['stock_check'] = 0;
                $input['size'] = null;
                $input['size_qty'] = null;
                $input['size_price'] = null;
                $input['color'] = null;
            } else {
                // لن نخزن على products — ستذهب إلى MP
                unset($input['size'], $input['size_qty'], $input['size_price'], $input['color']);
                $input['stock_check'] = 1; // لأغراض العرض فقط إن كان لازال مستخدمًا في الواجهات
            }

            // Colors belong to merchant_products, not products
            // This will be handled separately in merchant product update

            if(empty($request->size_check)) {
                $input['size_all'] = null;
            } else {
                $input['size_all'] = implode(',', (array)$request->size_all);
            }

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
                $input['meta_tag'] = implode(',', (array)$request->meta_tag);
            }
        }

        // License (هوية فقط)
        if($data->type == "License")
        {
            if(!in_array(null, (array)$request->license) && !in_array(null, (array)$request->license_qty)) {
                $input['license'] = implode(',,', $request->license);
                $input['license_qty'] = implode(',', $request->license_qty);
            } else {
                if(in_array(null, (array)$request->license) || in_array(null, (array)$request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $license     = explode(',,', (string)$prod->license);
                    $license_qty = explode(',',  (string)$prod->license_qty);
                    $input['license'] = implode(',,', $license);
                    $input['license_qty'] = implode(',', $license_qty);
                }
            }
        }

        // Features (هوية) - colors moved to merchant_products
        if(!in_array(null, (array)$request->features)) {
            $input['features'] = implode(',', str_replace(',',' ', $request->features));
        } else {
            if(in_array(null, (array)$request->features)) {
                $input['features'] = null;
            } else {
                $features = explode(',', (string)$data->features);
                $input['features'] = implode(',', $features);
            }
        }

        // Tags
        if (!empty($request->tags)) {
            $input['tags'] = implode(',', (array)$request->tags);
        } else {
            $input['tags'] = null;
        }

        // لا نكتب price/previous_price/stock إلى products
        unset($input['price'], $input['previous_price'], $input['stock']);

        $data->slug = Str::slug($data->name,'-').'-'.strtolower($data->sku);
        $data->update($input);
        //-- Logic Section Ends

        // تحديث الـ Thumbnail
        if($data->photo != null && !empty($data->thumbnail))
        {
            $oldThumb = public_path().'/assets/images/thumbnails/'.$data->thumbnail;
            if (file_exists($oldThumb) && $data->thumbnail != 'noimage.png') {
                @unlink($oldThumb);
            }
        }

        $fimageData = public_path().'/assets/images/products/'.$prod->photo;
        if(filter_var($prod->photo, FILTER_VALIDATE_URL)){
            $fimageData = $prod->photo;
        }

        // التحقق من وجود الصورة قبل إنشاء الـ thumbnail
        try {
            if (!empty($prod->photo) && (filter_var($prod->photo, FILTER_VALIDATE_URL) || file_exists($fimageData))) {
                $img = Image::make($fimageData)->resize(285, 285);
                $thumbnail = time().Str::random(8).'.jpg';
                $img->save(public_path().'/assets/images/thumbnails/'.$thumbnail);
                $prod->thumbnail = $thumbnail;
            } else {
                $prod->thumbnail = 'noimage.png';
            }
        } catch (\Exception $e) {
            \Log::warning('Could not create thumbnail for product: ' . $prod->id . ' - ' . $e->getMessage());
            $prod->thumbnail = 'noimage.png';
        }
        $prod->update();

        // تحديث/إنشاء سجل MerchantItem
        $merchantId = (int) ($request->input('user_id') ?? $request->input('merchant_id') ?? 0);
        if ($merchantId <= 0) {
            return response()->json(['errors' => ['vendor' => 'Vendor (user) is required']], 422);
        }

        $mpPrice         = $request->filled('price') ? ((float)$request->input('price') / $sign->value) : 0.0;
        $mpPreviousPrice = $request->filled('previous_price') ? ((float)$request->input('previous_price') / $sign->value) : null;

        $mpSize      = null;
        $mpSizeQty   = null;
        $mpSizePrice = null;
        if (!empty($request->stock_check)) {
            if(!in_array(null, (array)$request->size) &&
               !in_array(null, (array)$request->size_qty) &&
               !in_array(null, (array)$request->size_price)) {

                $mpSize    = implode(',', (array)$request->size);
                $mpSizeQty = implode(',', (array)$request->size_qty);

                $size_prices = (array)$request->size_price;
                $s_price = [];
                foreach($size_prices as $key => $sPrice){
                    $s_price[$key] = ((float)$sPrice) / $sign->value;
                }
                $mpSizePrice = implode(',', $s_price);
            }
        }

        MerchantItem::updateOrCreate(
            ['product_id' => $prod->id, 'user_id' => $merchantId],
            [
                'product_type'        => 'affiliate',
                'price'               => $mpPrice,
                'previous_price'      => $mpPreviousPrice,
                'stock'               => (int)$request->input('stock', 0),
                'size'                => $mpSize,
                'size_qty'            => $mpSizeQty,
                'size_price'          => $mpSizePrice,
                'minimum_qty'         => $request->input('minimum_qty') ?: null,
                'whole_sell_qty'      => !empty($request->whole_sell_qty) ? implode(',', (array)$request->whole_sell_qty) : null,
                'whole_sell_discount' => !empty($request->whole_sell_discount) ? implode(',', (array)$request->whole_sell_discount) : null,
                'preordered'          => (int) ($request->input('preordered') ?? 0),
                'product_condition'   => (int) ($request->input('product_condition') ?? 0),
                'ship'                => $request->input('ship') ?: null,
                'brand_quality_id'    => $request->input('brand_quality_id') ?: null,
                'affiliate_link'      => $request->input('affiliate_link') ?: null,
                'status'              => 1,
            ]
        );

        //--- Redirect Section
        $msg = __('Product Updated Successfully.').'<a href="'.route('admin-import-index').'">'.__("View Product Lists.").'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
