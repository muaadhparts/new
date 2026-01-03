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
        // Query merchant items directly - each merchant item = independent row
        // Only affiliate type items (item_type is now on merchant_items)
        $query = MerchantItem::with(['catalogItem.brand', 'user', 'qualityBrand'])
            ->where('item_type', 'affiliate');

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
            ->addColumn('photo', function (MerchantItem $mi) {
                $catalogItem = $mi->catalogItem;
                if (!$catalogItem) return '<img src="' . asset('assets/images/noimage.png') . '" class="img-thumbnail" style="width:80px">';

                $photo = filter_var($catalogItem->photo, FILTER_VALIDATE_URL)
                    ? $catalogItem->photo
                    : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png'));
                return '<img src="' . $photo . '" alt="Image" class="img-thumbnail" style="width:80px">';
            })
            ->addColumn('name', function (MerchantItem $mi) {
                $catalogItem = $mi->catalogItem;
                if (!$catalogItem) return __('N/A');

                $itemLink = route('front.catalog-item', [
                    'slug' => $catalogItem->slug,
                    'merchant_id' => $mi->user_id,
                    'merchant_item_id' => $mi->id
                ]);

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $sku = $catalogItem->sku ? '<br><small class="text-muted">' . __('SKU') . ': ' . $catalogItem->sku . '</small>' : '';
                $condition = $mi->item_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $itemLink . '" target="_blank">' . $displayName . '</a>' . $sku . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mi) {
                $catalogItem = $mi->catalogItem;
                return $catalogItem && $catalogItem->brand ? getLocalizedBrandName($catalogItem->brand) : __('N/A');
            })
            ->addColumn('quality_brand', function (MerchantItem $mi) {
                return $mi->qualityBrand ? getLocalizedQualityName($mi->qualityBrand) : __('N/A');
            })
            ->addColumn('merchant', function (MerchantItem $mi) {
                // Display merchant info
                if (!$mi->user) return __('N/A');
                $shopName = $mi->user->shop_name ?: $mi->user->name;
                return '<a href="' . route('admin-merchant-show', $mi->user_id) . '" target="_blank">' . $shopName . '</a>';
            })
            ->addColumn('price', function (MerchantItem $mi) {
                $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());

                $price = (float) $mi->price;
                $base = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

                return \PriceHelper::showAdminCurrencyPrice($base * $this->curr->value);
            })
            ->addColumn('stock', function (MerchantItem $mi) {
                if ($mi->stock === null) return __('Unlimited');
                if ((int) $mi->stock === 0) return '<span class="text-danger">' . __('Out Of Stock') . '</span>';
                return $mi->stock;
            })
            ->addColumn('status', function (MerchantItem $mi) {
                $class = $mi->status == 1 ? 'drop-success' : 'drop-danger';
                $s = $mi->status == 1 ? 'selected' : '';
                $ns = $mi->status == 0 ? 'selected' : '';

                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('admin-merchant-catalogItem-status', ['id' => $mi->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('admin-merchant-catalogItem-status', ['id' => $mi->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mi) {
                $catalogItem = $mi->catalogItem;
                if (!$catalogItem) return '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('admin-import-edit', $catalogItem->id) . '"><i class="fas fa-edit"></i> ' . __("Edit") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $catalogItem->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>
                        <a data-href="' . route('admin-prod-feature', $catalogItem->id) . '" class="feature" data-bs-toggle="modal" data-bs-target="#modal2"> <i class="fas fa-star"></i> ' . __("Highlight") . '</a>
                        <a href="javascript:;" data-href="' . route('admin-affiliate-prod-delete', $catalogItem->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant'])
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
        $path = 'assets/images/catalogItems/'.$image_name;
        file_put_contents($path, $image);

        if($data->photo != null)
        {
            if (file_exists(public_path().'/assets/images/catalogItems/'.$data->photo)) {
                unlink(public_path().'/assets/images/catalogItems/'.$data->photo);
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
            $path = 'assets/images/catalogItems/'.$image_name;
            file_put_contents($path, $image);
            $input['photo'] = $image_name;
        }else{
            $input['photo'] = $request->photolink;
        }

        // Check Physical meta (identity only — لا نكتب price/stock/size في catalogItems)
        if($request->type == "Physical")
        {
            //--- Validation Section
            $rules = ['sku' => 'min:8|unique:catalogItems'];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            // Identity flags (لن تُكتب أسعار/مخزون على catalogItems)
            if ($request->item_condition_check == ""){ $input['item_condition'] = 0; }
            if ($request->preordered_check == ""){ $input['preordered'] = 0; }
            if ($request->shipping_time_check == ""){ $input['ship'] = null; }

            // لا تكتب هذه في catalogItems
            unset($input['stock_check'], $input['size'], $input['size_qty'], $input['size_price'], $input['color']);
            // Colors belong to merchant_items, not catalogItems
            // This will be handled separately in merchant item creation

            // قياسات عامة للهوية (إن أردت إبقاءها في catalogItems: size_all)
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

        // لا نكتب price/previous_price/item_type إلى catalogItems
        unset($input['price'], $input['previous_price'], $input['stock'], $input['item_type']);

        // Save catalog item (identity only)
        $data->fill($input)->save();

        // Set slug
        $catalogItem = CatalogItem::find($data->id);
        if($catalogItem->type != 'Physical'){
            $catalogItem->slug = Str::slug($data->name,'-').'-'.strtolower(Str::random(3).$data->id.Str::random(3));
        }
        else {
            $catalogItem->slug = Str::slug($data->name,'-').'-'.strtolower($data->sku);
        }

        // Thumbnail
        $fimageData = public_path().'/assets/images/catalogItems/'.$catalogItem->photo;
        if(filter_var($catalogItem->photo, FILTER_VALIDATE_URL)){
            $fimageData = $catalogItem->photo;
        }

        // Check if image exists before creating thumbnail
        try {
            if (!empty($catalogItem->photo) && (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) || file_exists($fimageData))) {
                $img = Image::make($fimageData)->resize(285, 285);
                $thumbnail = time().Str::random(8).'.jpg';
                $img->save(public_path().'/assets/images/thumbnails/'.$thumbnail);
                $catalogItem->thumbnail = $thumbnail;
            } else {
                // Use default image
                $catalogItem->thumbnail = 'noimage.png';
            }
        } catch (\Exception $e) {
            \Log::warning('Could not create thumbnail for catalog item: ' . $catalogItem->id . ' - ' . $e->getMessage());
            $catalogItem->thumbnail = 'noimage.png';
        }
        $catalogItem->update();

        // Create/update merchant item
        $merchantId = (int) ($request->input('user_id') ?? $request->input('merchant_id') ?? 0);
        if ($merchantId <= 0) {
            return response()->json(['errors' => ['merchant' => 'Merchant (user) is required']], 422);
        }

        // Price calculations for base store
        $mpPrice         = (float) ($request->input('price', 0)) / $sign->value;
        $mpPreviousPrice = $request->filled('previous_price') ? ((float)$request->input('previous_price') / $sign->value) : null;

        // Sizes / quantities / size prices (convert to base value)
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
            ['catalog_item_id' => $catalogItem->id, 'user_id' => $merchantId],
            [
                'item_type'        => 'affiliate',
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
                'item_condition'   => (int) ($request->input('item_condition') ?? 0),
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
                    $gallery['catalog_item_id'] = $lastid;
                    $gallery->save();
                }
            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __("New Affiliate CatalogItem Added Successfully.").'<a href="'.route('admin-import-index').'">'.__("View CatalogItem Lists.").'</a>';
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
            $rules = ['sku' => 'min:8|unique:catalogItems,sku,'.$id];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends

            if ($request->item_condition_check == ""){ $input['item_condition'] = 0; }
            if ($request->preordered_check == ""){ $input['preordered'] = 0; }
            if ($request->shipping_time_check == ""){ $input['ship'] = null; }

            // لا تكتب مقاسات/مخزون/أسعار على catalogItems
            if(empty($request->stock_check)) {
                $input['stock_check'] = 0;
                $input['size'] = null;
                $input['size_qty'] = null;
                $input['size_price'] = null;
                $input['color'] = null;
            } else {
                // لن نخزن على catalogItems — ستذهب إلى MP
                unset($input['size'], $input['size_qty'], $input['size_price'], $input['color']);
                $input['stock_check'] = 1; // لأغراض العرض فقط إن كان لازال مستخدمًا في الواجهات
            }

            // Colors belong to merchant_items, not catalogItems
            // This will be handled separately in merchant item update

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

        // License (identity only)
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
                    $license     = explode(',,', (string)$catalogItem->license);
                    $license_qty = explode(',',  (string)$catalogItem->license_qty);
                    $input['license'] = implode(',,', $license);
                    $input['license_qty'] = implode(',', $license_qty);
                }
            }
        }

        // Features (identity) - colors moved to merchant_items
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

        // Don't write price/previous_price/stock to catalog_items
        unset($input['price'], $input['previous_price'], $input['stock']);

        $data->slug = Str::slug($data->name,'-').'-'.strtolower($data->sku);
        $data->update($input);
        //-- Logic Section Ends

        // Update Thumbnail
        if($data->photo != null && !empty($data->thumbnail))
        {
            $oldThumb = public_path().'/assets/images/thumbnails/'.$data->thumbnail;
            if (file_exists($oldThumb) && $data->thumbnail != 'noimage.png') {
                @unlink($oldThumb);
            }
        }

        $fimageData = public_path().'/assets/images/catalogItems/'.$catalogItem->photo;
        if(filter_var($catalogItem->photo, FILTER_VALIDATE_URL)){
            $fimageData = $catalogItem->photo;
        }

        // Check if image exists before creating thumbnail
        try {
            if (!empty($catalogItem->photo) && (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) || file_exists($fimageData))) {
                $img = Image::make($fimageData)->resize(285, 285);
                $thumbnail = time().Str::random(8).'.jpg';
                $img->save(public_path().'/assets/images/thumbnails/'.$thumbnail);
                $catalogItem->thumbnail = $thumbnail;
            } else {
                $catalogItem->thumbnail = 'noimage.png';
            }
        } catch (\Exception $e) {
            \Log::warning('Could not create thumbnail for catalog item: ' . $catalogItem->id . ' - ' . $e->getMessage());
            $catalogItem->thumbnail = 'noimage.png';
        }
        $catalogItem->update();

        // Update/create MerchantItem record
        $merchantId = (int) ($request->input('user_id') ?? $request->input('merchant_id') ?? 0);
        if ($merchantId <= 0) {
            return response()->json(['errors' => ['merchant' => 'Merchant (user) is required']], 422);
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
            ['catalog_item_id' => $catalogItem->id, 'user_id' => $merchantId],
            [
                'item_type'        => 'affiliate',
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
                'item_condition'   => (int) ($request->input('item_condition') ?? 0),
                'ship'                => $request->input('ship') ?: null,
                'brand_quality_id'    => $request->input('brand_quality_id') ?: null,
                'affiliate_link'      => $request->input('affiliate_link') ?: null,
                'status'              => 1,
            ]
        );

        //--- Redirect Section
        $msg = __('CatalogItem Updated Successfully.').'<a href="'.route('admin-import-index').'">'.__("View Catalog Item Lists.").'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
