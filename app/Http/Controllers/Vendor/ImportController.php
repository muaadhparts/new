<?php

namespace App\Http\Controllers\Vendor;

use App\{
    Models\Gallery,
    Models\Product,
    Models\Category,
    Models\Generalsetting
};

// عرض البائع (MerchantProduct) — المصدر الوحيد للسعر/المخزون/المقاسات
use App\Models\MerchantProduct;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use Validator;
use Datatables;

class ImportController extends VendorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $user = $this->user;

        // عروض هذا البائع فقط + تحميل تعريف المنتج
        $datas = MerchantProduct::where('user_id', $user->id)
            ->whereHas('product', function ($q) {
                $q->where('product_type', 'affiliate');
            })
            ->with('product')
            ->latest('id')
            ->get();

        return Datatables::of($datas)
            ->editColumn('name', function (MerchantProduct $data) {
                $product = $data->product;
                $name = mb_strlen(strip_tags($product->name), 'UTF-8') > 50
                    ? mb_substr(strip_tags($product->name), 0, 50, 'UTF-8') . '...'
                    : strip_tags($product->name);

                // تمرير {vendor_id} و {merchant_product_id} في الرابط
                $id = '<small>' . __('Product ID') . ': <a href="' .
                    route('front.product', ['slug' => $product->slug, 'vendor_id' => $data->user_id, 'merchant_product_id' => $data->id]) .
                    '" target="_blank">' . sprintf("%'.08d", $product->id) . '</a></small>';

                return $name . '<br>' . $id;
            })
            ->editColumn('price', function (MerchantProduct $data) {
                $price = round($data->price * $this->curr->value, 2);
                return \PriceHelper::showAdminCurrencyPrice($price);
            })
            ->addColumn('status', function (MerchantProduct $data) {
                // نفترض أن المسار vendor-prod-status يقوم بتحديث حالة عرض البائع لهذا المنتج
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                $s     = $data->status == 1 ? 'selected' : '';
                $ns    = $data->status == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('vendor-prod-status', ['id1' => $data->product_id, 'id2' => 1]) . '" ' . $s . '>' . __('Activated') . '</option><option data-val="0" value="' . route('vendor-prod-status', ['id1' => $data->product_id, 'id2' => 0]) . '" ' . $ns . '>' . __('Deactivated') . '</option></select></div>';
            })
            ->addColumn('action', function (MerchantProduct $data) {
                return '<div class="action-list">
                            <a href="' . route('vendor-import-edit', $data->product_id) . '">
                                <i class="fas fa-edit"></i>' . __('Edit') . '
                            </a>
                            <a href="javascript" class="set-gallery" data-toggle="modal" data-target="#setgallery">
                                <input type="hidden" value="' . $data->product_id . '">
                                <i class="fas fa-eye"></i> ' . __('View Gallery') . '
                            </a>
                            <a href="javascript:;" data-href="' . route('vendor-prod-delete', $data->product_id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>';
            })
            ->rawColumns(['name', 'status', 'action'])
            ->toJson();
    }

    public function index()
    {
        if ($this->gs->affilite == 1) {
            return view('vendor.productimport.index');
        } else {
            return back();
        }
    }

    //*** GET Request
    public function createImport()
    {
        $cats = Category::all();
        $sign = $this->curr;
        if ($this->gs->affilite == 1) {
            return view('vendor.productimport.createone', compact('cats', 'sign'));
        } else {
            return back();
        }
    }

    //*** GET Request
    public function importCSV()
    {
        $cats = Category::all();
        $sign = $this->curr;
        return view('vendor.productimport.importcsv', compact('cats', 'sign'));
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
        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request
    public function store(Request $request)
    {
        // BEGIN NEW MERCHANT PRODUCT HANDLING
        $user = $this->user;
        $package = $user->subscribes()->latest('id')->first();
        // عدد العروض الخاصة بالبائع (على merchant_products)
        $prods = $user->merchantProducts()->latest('id')->count();
        // // dd(['vendor_products_count' => $prods]); // اختباري

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return response()->json(array('errors' => [0 => __('You must complete your verfication first.')]));
            }
        }

        if ($prods < $package->allowed_products) {
            // تحقق الصورة والملف إن لزم
            if ($request->image_source == 'file') {
                $rules = [
                    'photo' => 'required',
                    'file'  => 'mimes:zip'
                ];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
                }
            }

            $sign = $this->curr;

            // تعريف المنتج (هوية فقط — بدون سعر/مخزون/مقاسات)
            $productInput = $request->only([
                'type', 'sku', 'category_id', 'subcategory_id', 'childcategory_id',
                'attributes', 'name', 'details', 'weight', 'policy', 'tags',
                'features', 'colors', 'is_meta', 'meta_tag', 'meta_description',
                'youtube', 'link', 'platform', 'region', 'measure', 'is_catalog',
                'catalog_id', 'cross_products'
            ]);

            // ملف رقمي (إن وجد)
            if ($file = $request->file('file')) {
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/files', $name);
                $productInput['file'] = $name;
            }

            // الصورة الرئيسية (base64 أو رابط)
            if ($request->photo != "") {
                $image = $request->photo;
                list($type, $image) = explode(';', $image);
                list(, $image) = explode(',', $image);
                $image = base64_decode($image);
                $image_name = time() . Str::random(8) . '.png';
                $path = 'assets/images/products/' . $image_name;
                file_put_contents($path, $image);
                $productInput['photo'] = $image_name;
            } else {
                $productInput['photo'] = $request->photolink;
            }

            // إنشاء تعريف المنتج (product_type=affiliate)
            $product = new Product;
            $product->fill($productInput);
            $product->product_type = 'affiliate';
            $product->save();

            // مدخلات عرض البائع (MerchantProduct) — السعر/المخزون/المقاسات
            $merchantInput = $request->only([
                'stock', 'is_discount', 'discount_date', 'whole_sell_qty',
                'whole_sell_discount', 'preordered', 'minimum_qty', 'stock_check',
                'popular', 'status', 'is_popular', 'licence_type', 'license_qty',
                'license', 'ship', 'product_condition'
            ]);

            // المقاسات على MP
            if (!empty($request->size)) {
                $merchantInput['size'] = implode(',', $request->size);
            }
            if (!empty($request->size_qty)) {
                $merchantInput['size_qty'] = implode(',', $request->size_qty);
            }
            if (!empty($request->size_price)) {
                $size_prices = [];
                foreach ($request->size_price as $key => $sPrice) {
                    $size_prices[$key] = $sPrice / $sign->value;
                }
                $merchantInput['size_price'] = implode(',', $size_prices);
            }
            if (!empty($request->color_all)) {
                $merchantInput['color_all'] = implode(',', $request->color_all);
            }
            if (!empty($request->size_all)) {
                $merchantInput['size_all'] = implode(',', $request->size_all);
            }

            // تحويل العملة للسعر
            $merchantInput['price'] = $request->price / $sign->value;
            $merchantInput['previous_price'] = $request->previous_price / $sign->value;
            $merchantInput['product_id'] = $product->id;
            $merchantInput['user_id'] = $user->id;

            // إنشاء عرض البائع
            $merchantProduct = MerchantProduct::create($merchantInput);
            // // dd(['mp_created' => $merchantProduct->id]); // اختباري

            // حفظ صور المعرض إن وجدت
            if ($files = $request->file('gallery')) {
                foreach ($files as $key => $file) {
                    if (in_array($key, (array) $request->galval)) {
                        $gallery = new Gallery;
                        $name = \PriceHelper::ImageCreateName($file);
                        $file->move('assets/images/galleries', $name);
                        $gallery['photo'] = $name;
                        $gallery['product_id'] = $product->id;
                        $gallery->save();
                    }
                }
            }

            // توليد slug و thumbnail
            $product->slug = Str::slug($product->name, '-') . '-' . strtolower(Str::random(3) . $product->id . Str::random(3));
            $product->save();

            $fimageData = public_path() . '/assets/images/products/' . $product->photo;
            if (filter_var($product->photo, FILTER_VALIDATE_URL)) {
                $fimageData = $product->photo;
            }
            $img = Image::make($fimageData)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $product->thumbnail = $thumbnail;
            $product->save();

            $msg = __('New Affiliate Product Added Successfully.') . '<a href="' . route('vendor-import-index') . '">' . __('View Product Lists.') . '</a>';
            return response()->json($msg);
        } else {
            return response()->json(array('errors' => [0 => __('You Can\'t Add More Product.')]));
        }
        // END NEW MERCHANT PRODUCT HANDLING
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);

        // Get merchant product data for this vendor
        $merchantProduct = MerchantProduct::where('product_id', $id)
            ->where('user_id', $this->user->id)
            ->first();

        $sign = $this->curr;
        return view('vendor.productimport.editone', compact('cats', 'data', 'merchantProduct', 'sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        // تعريف المنتج + عرض البائع الحالي
        $product = Product::findOrFail($id);
        $merchant = MerchantProduct::where('product_id', $id)
            ->where('user_id', $this->user->id)
            ->firstOrFail();

        $sign = $this->curr;

        // تحقق من الملف المضغوط
        $rules = [
            'file' => 'mimes:zip'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // حقول تعريف المنتج (هوية فقط)
        $productInput = $request->only([
            'type', 'sku', 'category_id', 'subcategory_id', 'childcategory_id',
            'attributes', 'name', 'slug', 'photo', 'thumbnail', 'file', 'details',
            'weight', 'policy', 'tags', 'features', 'colors', 'is_meta', 'meta_tag',
            'meta_description', 'youtube', 'type', 'link', 'platform', 'region',
            'measure', 'is_catalog', 'catalog_id', 'cross_products'
        ]);

        // مصدر الصورة
        if ($request->image_source == 'file') {
            $productInput['photo'] = $request->photo;
        } else {
            $productInput['photo'] = $request->photolink;
        }

        // حقول عرض البائع (MP)
        $merchantInput = $request->only([
            'stock', 'is_discount', 'discount_date', 'whole_sell_qty',
            'whole_sell_discount', 'preordered', 'minimum_qty', 'stock_check',
            'popular', 'status', 'is_popular', 'licence_type', 'license_qty',
            'license', 'ship', 'product_condition'
        ]);

        // المقاسات على MP
        if (!empty($request->size)) {
            $merchantInput['size'] = implode(',', $request->size);
        } else {
            $merchantInput['size'] = null;
        }
        if (!empty($request->size_qty)) {
            $merchantInput['size_qty'] = implode(',', $request->size_qty);
        } else {
            $merchantInput['size_qty'] = null;
        }
        if (!empty($request->size_price)) {
            $size_prices = [];
            foreach ($request->size_price as $key => $sPrice) {
                $size_prices[$key] = $sPrice / $sign->value;
            }
            $merchantInput['size_price'] = implode(',', $size_prices);
        } else {
            $merchantInput['size_price'] = null;
        }
        if (!empty($request->color_all)) {
            $merchantInput['color_all'] = implode(',', $request->color_all);
        } else {
            $merchantInput['color_all'] = null;
        }
        if (!empty($request->size_all)) {
            $merchantInput['size_all'] = implode(',', $request->size_all);
        } else {
            $merchantInput['size_all'] = null;
        }

        // تحويل العملة للسعر
        $merchantInput['price'] = $request->price / $sign->value;
        $merchantInput['previous_price'] = $request->previous_price / $sign->value;

        // احتفظ بالصورة القديمة قبل التحديث لتحديد ما إذا تغيّرت
        $oldPhoto = $product->photo;

        // تحديث تعريف المنتج
        $product->update($productInput);

        // تحديث عرض البائع
        $merchant->update($merchantInput);
        // // dd(['vendor_mp_updated' => true]); // اختباري

        // لو تغيّرت الصورة الرئيسية — أعِد توليد الـ Thumbnail
        if (!empty($productInput['photo']) && $oldPhoto !== $productInput['photo']) {
            if ($product->thumbnail && file_exists(public_path() . '/assets/images/thumbnails/' . $product->thumbnail)) {
                @unlink(public_path() . '/assets/images/thumbnails/' . $product->thumbnail);
            }

            $fimageData = public_path() . '/assets/images/products/' . $productInput['photo'];
            if (filter_var($productInput['photo'], FILTER_VALIDATE_URL)) {
                $fimageData = $productInput['photo'];
            }

            $img = Image::make($fimageData)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $product->thumbnail = $thumbnail;
            $product->save();
        }

        $msg = __('Product Updated Successfully.') . '<a href="' . route('vendor-import-index') . '">' . __('View Product Lists.') . '</a>';
        return response()->json($msg);
    }
}
