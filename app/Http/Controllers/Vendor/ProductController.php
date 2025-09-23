<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Currency;
use App\Models\Gallery;
use App\Models\Generalsetting;
use App\Models\Product;
// عرض البائع (المصدر الوحيد للسعر/المخزون/المقاسات)
use App\Models\MerchantProduct;
use App\Models\Subcategory;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Image;
use Validator;

class ProductController extends VendorBaseController
{

    public function index()
    {
        $user = $this->user;

        // السِجلّات الخاصة بالبائع عبر merchant_products (وليس products)
        $datas = $user->merchantProducts()
            ->whereHas('product', function($query){
                $query->where('product_type', 'normal');
            })
            ->with('product')
            ->latest('id')
            ->paginate(10);

        return view('vendor.product.index', compact('datas'));
    }

    public function types()
    {
        return view('vendor.product.types');
    }

    public function catalogs()
    {
        $user = $this->user;

        // المنتجات المتاحة في الكاتالوج (تعريف فقط)، ومفعلة عبر عروض نشطة
        $datas = Product::where('product_type', 'normal')
            ->where('is_catalog', '=', 1)
            ->status(1) // السكوب محوّل داخليًا إلى merchant_products.status
            ->latest('id')
            ->get();

        return view('vendor.product.catalogs', compact('datas', 'user'));
    }

    //*** GET Request
    public function create($slug)
    {
        $user = $this->user;
        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                Session::flash('unsuccess', __('You must complete your verfication first.'));
                return redirect()->route('vendor-verify');
            }
        }

        $cats = Category::all();
        $sign = $this->curr;
        if ($slug == 'physical') {
            if ($this->gs->physical == 1) {
                return view('vendor.product.create.physical', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if ($slug == 'digital') {
            if ($this->gs->digital == 1) {
                return view('vendor.product.create.digital', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if (($slug == 'license')) {
            if ($this->gs->license == 1) {
                return view('vendor.product.create.license', compact('cats', 'sign'));
            } else {
                return back();
            }
        } else if (($slug == 'listing')) {
            if ($this->gs->listing == 1) {
                return view('vendor.product.create.listing', compact('cats', 'sign'));
            } else {
                return back();
            }
        }
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        // تحديث حالة عرض البائع (merchant_products)
        $userId = $this->user->id;
        $merchantProduct = MerchantProduct::where('product_id', $id1)
            ->where('user_id', $userId)
            ->firstOrFail();

        $merchantProduct->status = (int) $id2;
        $merchantProduct->save();

        return back()->with("success", __('Status Updated Successfully.'));
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

        $img = Image::make(public_path() . '/assets/images/products/' . $data->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
        $data->thumbnail = $thumbnail;
        $data->update();

        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request
    public function import()
    {
        $cats = Category::all();
        $sign = $this->curr;
        return view('vendor.product.productcsv', compact('cats', 'sign'));
    }

    public function importSubmit(Request $request)
    {
        $user = $this->user;
        $package = $user->subscribes()->orderBy('id', 'desc')->first();

        // عدّ عروض البائع لا عدد المنتجات
        $prods = $user->merchantProducts()->count();

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {
            $log = "";
            //--- Validation Section
            $request->validate([
                'csvfile' => 'required',
            ]);

            $filename = '';
            if ($file = $request->file('csvfile')) {
                $extensions = ['csv'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $filename = time() . '-' . $file->getClientOriginalName();
                $file->move('assets/temp_files', $filename);
            }

            $file = fopen(public_path('assets/temp_files/' . $filename), "r");
            $i = 1;

            $defaultCurr = Currency::where('is_default', 1)->first();

            while (($line = fgetcsv($file)) !== false) {
                if ($i != 1) {
                    $sku = $line[0] ?? null;
                    if (!$sku) {
                        $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('Missing SKU!') . "<br>";
                        $i++;
                        continue;
                    }

                    // 1) جهّز تعريف المنتج (هوية فقط)
                    $data  = Product::firstOrNew(['sku' => $sku]);
                    $isNew = !$data->exists;

                    $input = [];
                    $input['type']            = 'Physical';
                    $input['name']            = $line[4] ?? $sku;
                    $input['details']         = $line[6] ?? null;
                    $input['product_type']    = $line[19] ?? 'normal';
                    $input['affiliate_link']  = $line[20] ?? null;

                    // تصنيف
                    $input['category_id']     = null;
                    $input['subcategory_id']  = null;
                    $input['childcategory_id']= null;

                    $mcat = Category::where(DB::raw('lower(name)'), strtolower($line[1] ?? ''));
                    if ($mcat->exists()) {
                        $input['category_id'] = $mcat->first()->id;

                        if (!empty($line[2])) {
                            $scat = Subcategory::where(DB::raw('lower(name)'), strtolower($line[2]));
                            if ($scat->exists()) {
                                $input['subcategory_id'] = $scat->first()->id;
                            }
                        }
                        if (!empty($line[3])) {
                            $chcat = Childcategory::where(DB::raw('lower(name)'), strtolower($line[3]));
                            if ($chcat->exists()) {
                                $input['childcategory_id'] = $chcat->first()->id;
                            }
                        }
                    } else {
                        $log .= "<br>" . __('Row No') . ": " . $i . " - " . __('No Category Found!') . "<br>";
                        $i++;
                        continue;
                    }

                    // صورة
                    $image_url = $line[5] ?? null;
                    $thumb_url = '';
                    if ($image_url) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_URL, $image_url);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'curl');
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_HEADER, true);
                        curl_setopt($ch, CURLOPT_NOBODY, true);

                        $content = curl_exec($ch);
                        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

                        if (strpos((string)$contentType, 'image/') !== false) {
                            $fimg = Image::make($image_url)->resize(800, 800);
                            $fphoto = time() . Str::random(8) . '.jpg';
                            $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
                            $input['photo'] = $fphoto;
                            $thumb_url = $image_url;
                        } else {
                            $fimg = Image::make(public_path() . '/assets/images/noimage.png')->resize(800, 800);
                            $fphoto = time() . Str::random(8) . '.jpg';
                            $fimg->save(public_path() . '/assets/images/products/' . $fphoto);
                            $input['photo'] = $fphoto;
                            $thumb_url = public_path() . '/assets/images/noimage.png';
                        }
                    }

                    if ($thumb_url) {
                        $timg = Image::make($thumb_url)->resize(285, 285);
                        $thumbnail = time() . Str::random(8) . '.jpg';
                        $timg->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
                        $input['thumbnail'] = $thumbnail;
                    }

                    // Slug
                    $input['slug'] = Str::slug($input['name'], '-') . '-' . strtolower($sku);

                    // الحقول التي لا نكتبها في products وفق السياسة:
                    // (price, previous_price, stock, size, size_qty, size_price, color) ← إلى MerchantProduct فقط
                    // ملاحظة: نسمح بحقل color_all الهوياتي إن كان موجودًا
                    $input['color_all'] = $line[13] ?? null;

                    $data->fill($input)->save();

                    // 2) إنشاء/تحديث عرض البائع (MerchantProduct)
                    // تحويل العملة
                    $mpPrice         = isset($line[7]) && $line[7] !== '' ? ((float)$line[7] / ($defaultCurr->value ?: 1)) : 0.0;
                    $mpPreviousPrice = isset($line[8]) && $line[8] !== '' ? ((float)$line[8] / ($defaultCurr->value ?: 1)) : null;

                    $mpSize      = $line[10] ?? null;
                    $mpSizeQty   = $line[11] ?? null;
                    $mpSizePrice = $line[12] ?? null;

                    if (!is_null($mpSizePrice) && $mpSizePrice !== '') {
                        // لو كانت قائمة أسعار مفصولة بفواصل نحاول تحويل كل قيمة
                        $parts = explode(',', $mpSizePrice);
                        $parts = array_map(function ($v) use ($defaultCurr) {
                            $v = trim($v);
                            return ($v === '' ? '' : ((float)$v / ($defaultCurr->value ?: 1)));
                        }, $parts);
                        $mpSizePrice = implode(',', $parts);
                    }

                    MerchantProduct::updateOrCreate(
                        ['product_id' => $data->id, 'user_id' => $user->id],
                        [
                            'price'               => $mpPrice,
                            'previous_price'      => $mpPreviousPrice,
                            'stock'               => (int)($line[9] ?? 0),
                            'size'                => $mpSize,
                            'size_qty'            => $mpSizeQty,
                            'size_price'          => $mpSizePrice,
                            'color_all'           => $line[13] ?? null,
                            'status'              => 1,
                        ]
                    );

                    $log .= "<br>" . __('Row No') . ": " . $i . " - " . ($isNew ? __('Created product & vendor listing.') : __('Attached vendor listing to existing product.')) . "<br>";
                }
                $i++;
            }
            fclose($file);

            //--- Redirect Section
            $msg = __("New Product Added Successfully.") . $log;
            return back()->with('success', $msg);
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));
        }
    }

    //*** POST Request
    public function store(Request $request)
    {
        $user    = $this->user;
        $package = $user->subscribes()->latest('id')->first();
        $prods   = $user->merchantProducts()->count(); // عدّ العروض لا المنتجات

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {

            //--- Validation Section
            $rules = [
                'photo' => 'required',
                'file'  => 'mimes:zip',
            ];
            $request->validate($rules);

            //--- Logic Section
            // تعريف المنتج (هوية فقط)
            $data  = new Product;
            $sign  = $this->curr;
            $input = $request->all();

            // ملف رقمي (إن وجد)
            if ($file = $request->file('file')) {
                $extensions = ['zip'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/files', $name);
                $input['file'] = $name;
            }

            // صورة رئيسية (base64)
            $image = $request->photo;
            list($type, $image) = explode(';', $image);
            list(, $image) = explode(',', $image);
            $image = base64_decode($image);
            $image_name = time() . Str::random(8) . '.png';
            $path = 'assets/images/products/' . $image_name;
            file_put_contents($path, $image);
            $input['photo'] = $image_name;

            // تحقق SKU للمنتجات الفيزيائية/القوائم
            if ($request->type == "Physical" || $request->type == "Listing") {
                $rules = ['sku' => 'min:8|unique:products'];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
                }

                // حقول هويوية فقط
                if ($request->product_condition_check == "") { $input['product_condition'] = 0; }
                if ($request->preordered_check == "")       { $input['preordered']        = 0; }
                if ($request->minimum_qty_check == "")      { $input['minimum_qty']       = null; }
                if ($request->shipping_time_check == "")    { $input['ship']              = null; }

                // مقاسات/مخزون/أسعار → إلى MerchantProduct لاحقًا، لذا لا نسجلها هنا
                $input['stock_check'] = isset($request->stock_check) ? 1 : 0;

                // الألوان العامة التعريفية (اختياري)
                if (empty($request->color_check)) {
                    $input['color_all'] = null;
                } else {
                    $input['color_all'] = implode(',', (array)$request->color_all);
                }

                // خصومات الجملة تُسجل لاحقًا في MP
                if ($request->mesasure_check == "") {
                    $input['measure'] = null;
                }
            }

            // SEO
            if (empty($request->seo_check)) {
                $input['meta_tag'] = null;
                $input['meta_description'] = null;
            } else {
                if (!empty($request->meta_tag)) {
                    $input['meta_tag'] = $request->meta_tag;
                    $input['is_meta']  = 1;
                }
            }

            // License (هوية)
            if ($request->type == "License") {
                if (in_array(null, (array)$request->license) || in_array(null, (array)$request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $input['license'] = implode(',,', $request->license);
                    $input['license_qty'] = implode(',', $request->license_qty);
                }
            }

            // Features/Colors (هوية)
            if (in_array(null, (array)$request->features) || in_array(null, (array)$request->colors)) {
                $input['features'] = null;
                $input['colors']   = null;
            } else {
                $input['features'] = implode(',', str_replace(',', ' ', $request->features));
                $input['colors']   = implode(',', str_replace(',', ' ', $request->colors));
            }

            // Tags (هوية)
            if (!empty($request->tags)) {
                $input['tags'] = $request->tags;
            }

            // سعر/مخزون/مقاسات → لا تُكتب في products (سيتم تسجيلها في MP)
            unset($input['price'], $input['previous_price'], $input['stock'], $input['size'], $input['size_qty'], $input['size_price'], $input['color']);

            // Attributes (هوية)
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

            $input['attributes'] = empty($attrArr) ? null : json_encode($attrArr);

            // حفظ تعريف المنتج
            $data->fill($input)->save();

            // توليد الـ Slug + Thumbnail
            $prod = Product::find($data->id);
            if ($prod->type != 'Physical') {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower(Str::random(3) . $data->id . Str::random(3));
            } else {
                $prod->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
            }

            $img = Image::make(public_path() . '/assets/images/products/' . $prod->photo)->resize(285, 285);
            $thumbnail = time() . Str::random(8) . '.jpg';
            $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
            $prod->thumbnail = $thumbnail;
            $prod->update();

            // إنشاء/تحديث عرض البائع (MerchantProduct)
            $merchantInput = [];

            // المقاسات/الكميات/أسعار المقاسات (MP)
            if (!empty($request->size)) {
                $merchantInput['size'] = implode(',', (array)$request->size);
            }
            if (!empty($request->size_qty)) {
                $merchantInput['size_qty'] = implode(',', (array)$request->size_qty);
            }
            if (!empty($request->size_price)) {
                $size_prices = [];
                foreach ((array)$request->size_price as $key => $sPrice) {
                    $size_prices[$key] = $sPrice / $sign->value;
                }
                $merchantInput['size_price'] = implode(',', $size_prices);
            }

            if (!empty($request->color_all)) {
                $merchantInput['color_all'] = implode(',', (array)$request->color_all);
            }

            if (!empty($request->whole_sell_qty)) {
                $merchantInput['whole_sell_qty'] = implode(',', (array)$request->whole_sell_qty);
            }
            if (!empty($request->whole_sell_discount)) {
                $merchantInput['whole_sell_discount'] = implode(',', (array)$request->whole_sell_discount);
            }

            // الأسعار/المخزون (MP)
            $merchantInput['price']          = (float) ($request->input('price', 0) / $sign->value);
            $merchantInput['previous_price'] = $request->filled('previous_price') ? (float) ($request->input('previous_price') / $sign->value) : null;
            $merchantInput['stock']          = (int) $request->input('stock', 0);
            $merchantInput['preordered']     = $request->input('preordered', 0);
            $merchantInput['minimum_qty']    = $request->input('minimum_qty') ?: null;
            $merchantInput['stock_check']    = $request->input('stock_check', 0);
            $merchantInput['ship']           = $request->input('ship') ?: null;
            $merchantInput['product_condition'] = $request->input('product_condition', 0);
            $merchantInput['status']         = 1;

            $merchantInput['product_id']     = $prod->id;
            $merchantInput['user_id']        = $user->id;

            MerchantProduct::updateOrCreate(
                ['product_id' => $prod->id, 'user_id' => $user->id],
                $merchantInput
            );
            // // dd(['created_mp_for' => $prod->id, 'vendor' => $user->id]); // اختباري

            // المعرض
            $lastid = $data->id;
            if ($files = $request->file('gallery')) {
                foreach ($files as $key => $file) {
                    $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                    if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                        return response()->json(array('errors' => ['Image format not supported']));
                    }

                    $gallery = new Gallery;
                    $name = \PriceHelper::ImageCreateName($file);
                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $img->save(public_path() . '/assets/images/galleries/' . $name);
                    $gallery['photo'] = $name;
                    $gallery['product_id'] = $lastid;
                    $gallery->save();
                }
            }

            //--- Redirect Section
            $msg = __('New Product Added Successfully.');
            return back()->with('success', $msg);
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));
        }
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

        if ($data->type == 'Digital') {
            return view('vendor.product.edit.digital', compact('cats', 'data', 'merchantProduct', 'sign'));
        } elseif ($data->type == 'License') {
            return view('vendor.product.edit.license', compact('cats', 'data', 'merchantProduct', 'sign'));
        } elseif ($data->type == 'Listing') {
            return view('vendor.product.edit.listing', compact('cats', 'data', 'merchantProduct', 'sign'));
        } else {
            return view('vendor.product.edit.physical', compact('cats', 'data', 'merchantProduct', 'sign'));
        }
    }

    //*** GET Request CATALOG
    public function catalogedit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);
        $sign = $this->curr;

        if ($data->type == 'Digital') {
            return view('vendor.product.edit.catalog.digital', compact('cats', 'data', 'sign'));
        } elseif ($data->type == 'License') {
            return view('vendor.product.edit.catalog.license', compact('cats', 'data', 'sign'));
        } else {
            return view('vendor.product.edit.catalog.physical', compact('cats', 'data', 'sign'));
        }
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'file' => 'mimes:zip',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // تعريف المنتج (هوية)
        $data  = Product::findOrFail($id);
        $sign  = $this->curr;
        $input = $request->all();

        // عرض البائع
        $merchant = MerchantProduct::where('product_id', $id)
            ->where('user_id', $this->user->id)
            ->firstOrFail();

        //Check Types (ملف/رابط)
        if ($request->type_check == 1) {
            $input['link'] = null;
        } else {
            if ($data->file != null) {
                if (file_exists(public_path() . '/assets/files/' . $data->file)) {
                    @unlink(public_path() . '/assets/files/' . $data->file);
                }
            }
            $input['file'] = null;
        }

        // حقول هويوية فقط على Product
        if ($data->type == "Physical") {
            // SKU (هوية)
            $rules = ['sku' => 'min:8|unique:products,sku,' . $id];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }

            if ($request->product_condition_check == "") { $input['product_condition'] = 0; }
            if ($request->preordered_check == "")       { $input['preordered']        = 0; }
            if ($request->minimum_qty_check == "")      { $input['minimum_qty']       = null; }
            if ($request->shipping_time_check == "")    { $input['ship']              = null; }

            // لا نكتب مقاسات/مخزون/أسعار في products
            if (empty($request->stock_check)) {
                $input['stock_check'] = 0;
                $input['size'] = null;
                $input['size_qty'] = null;
                $input['size_price'] = null;
                $input['color'] = null;
            } else {
                // إبقاء stock_check لأغراض العرض فقط إن كان لازال مستخدمًا بالواجهات
                $input['stock_check'] = 1;
                unset($input['size'], $input['size_qty'], $input['size_price'], $input['color']);
            }

            if (empty($request->color_check)) {
                $input['color_all'] = null;
            } else {
                $input['color_all'] = implode(',', (array)$request->color_all);
            }

            if (empty($request->whole_check)) {
                // ستُدار في MP
                $input['whole_sell_qty'] = null;
                $input['whole_sell_discount'] = null;
            }

            if ($request->measure_check == "") {
                $input['measure'] = null;
            }
        }

        // SEO
        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = $request->meta_tag;
                $input['is_meta']  = 1;
            }
        }

        // License (هوية)
        if ($data->type == "License") {
            if (!in_array(null, (array)$request->license) && !in_array(null, (array)$request->license_qty)) {
                $input['license']     = implode(',,', $request->license);
                $input['license_qty'] = implode(',',  $request->license_qty);
            } else {
                if (in_array(null, (array)$request->license) || in_array(null, (array)$request->license_qty)) {
                    $input['license'] = null;
                    $input['license_qty'] = null;
                } else {
                    $license     = explode(',,', (string)$data->license);
                    $license_qty = explode(',',  (string)$data->license_qty);
                    $input['license']     = implode(',,', $license);
                    $input['license_qty'] = implode(',',  $license_qty);
                }
            }
        }

        // Features/Colors
        if (!in_array(null, (array)$request->features) && !in_array(null, (array)$request->colors)) {
            $input['features'] = implode(',', str_replace(',', ' ', $request->features));
            $input['colors']   = implode(',', str_replace(',', ' ', $request->colors));
        } else {
            if (in_array(null, (array)$request->features) || in_array(null, (array)$request->colors)) {
                $input['features'] = null;
                $input['colors']   = null;
            } else {
                $features = explode(',', (string)$data->features);
                $colors   = explode(',', (string)$data->colors);
                $input['features'] = implode(',', $features);
                $input['colors']   = implode(',', $colors);
            }
        }

        // Tags
        if (!empty($request->tags)) { $input['tags'] = $request->tags; }
        if (empty($request->tags))  { $input['tags'] = null; }

        // خصائص التصفية (هوية)
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

        $input['attributes'] = empty($attrArr) ? null : json_encode($attrArr);

        // لا نكتب price/previous_price/stock في Product
        unset($input['price'], $input['previous_price'], $input['stock'], $input['size'], $input['size_qty'], $input['size_price'], $input['color']);

        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
        $data->update($input);

        // تحديث عرض البائع (MerchantProduct)
        $mp = [];

        // المقاسات (MP)
        if (!empty($request->size)) {
            $mp['size'] = implode(',', (array)$request->size);
        } else {
            $mp['size'] = null;
        }
        if (!empty($request->size_qty)) {
            $mp['size_qty'] = implode(',', (array)$request->size_qty);
        } else {
            $mp['size_qty'] = null;
        }
        if (!empty($request->size_price)) {
            $size_prices = [];
            foreach ((array)$request->size_price as $key => $sPrice) {
                $size_prices[$key] = $sPrice / $sign->value;
            }
            $mp['size_price'] = implode(',', $size_prices);
        } else {
            $mp['size_price'] = null;
        }

        if (!empty($request->color_all)) {
            $mp['color_all'] = implode(',', (array)$request->color_all);
        } else {
            $mp['color_all'] = null;
        }

        if (!empty($request->whole_sell_qty)) {
            $mp['whole_sell_qty'] = implode(',', (array)$request->whole_sell_qty);
        } else {
            $mp['whole_sell_qty'] = null;
        }

        if (!empty($request->whole_sell_discount)) {
            $mp['whole_sell_discount'] = implode(',', (array)$request->whole_sell_discount);
        } else {
            $mp['whole_sell_discount'] = null;
        }

        // أسعار/مخزون (MP)
        $mp['price']          = $request->filled('price') ? ($request->price / $sign->value) : $merchant->price;
        $mp['previous_price'] = $request->filled('previous_price') ? ($request->previous_price / $sign->value) : $merchant->previous_price;
        $mp['stock']          = (int) $request->input('stock', $merchant->stock);
        $mp['preordered']     = $request->input('preordered', $merchant->preordered);
        $mp['minimum_qty']    = $request->input('minimum_qty') ?: $merchant->minimum_qty;
        $mp['stock_check']    = $request->input('stock_check', $merchant->stock_check);
        $mp['ship']           = $request->input('ship') ?: $merchant->ship;
        $mp['product_condition'] = $request->input('product_condition', $merchant->product_condition);

        $merchant->update($mp);
        // // dd(['updated_mp' => $merchant->id]); // اختباري

        // المعرض
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }

                $gallery = new Gallery;
                $name = \PriceHelper::ImageCreateName($file);
                $img = Image::make($file->getRealPath())->resize(800, 800);
                $img->save(public_path() . '/assets/images/galleries/' . $name);
                $gallery['photo'] = $name;
                $gallery['product_id'] = $lastid;
                $gallery->save();
            }
        }

        //--- Redirect Section
        $msg = __('Product Updated Successfully.');
        return back()->with('success', $msg);
    }

    //*** POST Request CATALOG
    public function catalogupdate(Request $request, $id)
    {
        // بدلاً من إنشاء Product جديد، نربط/نحدّث عرض البائع (MP) للمنتج الكاتالوجي الموجود
        $user = $this->user;
        $package = $user->subscribes()->latest('id')->first();
        $prods = $user->merchantProducts()->count();

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if (!($prods < $package->allowed_products || $package->allowed_products == 0)) {
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));
        }

        $product = Product::findOrFail($id);
        $sign    = $this->curr;

        // تحقق من الملف (اختياري)
        $rules = [
            'file' => 'mimes:zip',
        ];
        $request->validate($rules);

        // أسعار/مخزون/مقاسات/خصومات → MP
        $mp = [
            'product_id' => $product->id,
            'user_id'    => $user->id,
            'price'          => $request->filled('price') ? ($request->price / $sign->value) : 0.0,
            'previous_price' => $request->filled('previous_price') ? ($request->previous_price / $sign->value) : null,
            'stock'          => (int) $request->input('stock', 0),
            'preordered'     => (int) $request->input('preordered', 0),
            'minimum_qty'    => $request->input('minimum_qty') ?: null,
            'stock_check'    => $request->input('stock_check', 0),
            'ship'           => $request->input('ship') ?: null,
            'product_condition' => (int) $request->input('product_condition', 0),
            'status'         => 1,
        ];

        if (!empty($request->size)) {
            $mp['size'] = implode(',', (array)$request->size);
        }
        if (!empty($request->size_qty)) {
            $mp['size_qty'] = implode(',', (array)$request->size_qty);
        }
        if (!empty($request->size_price)) {
            $size_prices = [];
            foreach ((array)$request->size_price as $key => $sPrice) {
                $size_prices[$key] = $sPrice / $sign->value;
            }
            $mp['size_price'] = implode(',', $size_prices);
        }
        if (!empty($request->color_all)) {
            $mp['color_all'] = implode(',', (array)$request->color_all);
        }
        if (!empty($request->whole_sell_qty)) {
            $mp['whole_sell_qty'] = implode(',', (array)$request->whole_sell_qty);
        }
        if (!empty($request->whole_sell_discount)) {
            $mp['whole_sell_discount'] = implode(',', (array)$request->whole_sell_discount);
        }

        MerchantProduct::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            $mp
        );
        // // dd(['catalog_mp_upsert' => true]); // اختباري

        // لا نعدّل تعريف المنتج/صورته في وضع الكاتالوج (هوية مشتركة)

        $msg = __('New Product Added Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request
    public function destroy($id)
    {
        // في سياسة الفصل: حذف البائع لعرضه فقط (MerchantProduct) وليس حذف تعريف المنتج
        $user = $this->user;
        $mp = MerchantProduct::where('product_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($mp) {
            $mp->delete();
        }

        // (اختياري) إن لم يعد هناك أي عروض للمنتج، يمكنك ترك المنتج (هوية) كما هو
        // وعدم حذفه لتجنب كسر روابط بائعين آخرين/الكاتالوج.

        $msg = __('Product Deleted Successfully.');
        return back()->with('success', $msg);
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
}
