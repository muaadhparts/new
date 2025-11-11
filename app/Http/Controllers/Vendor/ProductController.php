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

        // Get Products that belong to this vendor via merchant_products
        // We return Product models with merchantProduct relationship loaded
        $datas = Product::whereHas('merchantProducts', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('product_type', 'normal')
            ->with(['merchantProducts' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
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

    //*** GET Request - Show create form based on product type
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

        switch ($slug) {
            case 'physical':
                return view('vendor.product.create.physical', compact('cats', 'sign'));
            case 'digital':
                return view('vendor.product.create.digital', compact('cats', 'sign'));
            case 'license':
                return view('vendor.product.create.license', compact('cats', 'sign'));
            case 'listing':
                return view('vendor.product.create.listing', compact('cats', 'sign'));
            default:
                Session::flash('unsuccess', __('Invalid product type.'));
                return redirect()->route('vendor-prod-types');
        }
    }

    //*** GET Request - Create merchant offer for existing product
    public function createOffer($product_id)
    {
        $user = $this->user;
        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                Session::flash('unsuccess', __('You must complete your verfication first.'));
                return redirect()->route('vendor-verify');
            }
        }

        // Check if product exists and is a catalog item
        $product = Product::where('id', $product_id)
            ->where('is_catalog', 1)
            ->firstOrFail();

        // Check if vendor already has an offer for this product
        $existingOffer = MerchantProduct::where('product_id', $product_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingOffer) {
            Session::flash('unsuccess', __('You already have an offer for this product.'));
            return redirect()->route('vendor-prod-catalogs');
        }

        $sign = $this->curr;
        return view('vendor.product.create.offer', compact('product', 'sign'));
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

        // Count merchant products
        $prods = $user->merchantProducts()->count();

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {
            $log = "";
            $successCount = 0;
            $errorCount = 0;

            //--- Validation Section
            $request->validate([
                'csvfile' => 'required',
            ]);

            $filename = '';
            if ($file = $request->file('csvfile')) {
                $extensions = ['csv'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return back()->with('unsuccess', __('Only CSV format is supported'));
                }
                $filename = time() . '-' . $file->getClientOriginalName();
                $file->move('assets/temp_files', $filename);
            }

            $file = fopen(public_path('assets/temp_files/' . $filename), "r");
            $i = 1;
            $sign = $this->curr;

            while (($line = fgetcsv($file)) !== false) {
                if ($i != 1) { // Skip header row
                    $partNumber = trim($line[0] ?? '');

                    if (empty($partNumber)) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Missing part number (SKU)') . "<br>";
                        $errorCount++;
                        $i++;
                        continue;
                    }

                    // 1. Find existing product by part_number (sku)
                    $product = Product::where('sku', $partNumber)->first();

                    if (!$product) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Product with part number') . " '{$partNumber}' " . __('not found in catalog') . "<br>";
                        $errorCount++;
                        $i++;
                        continue;
                    }

                    // 2. Validate required merchant fields
                    $price = floatval($line[1] ?? 0);
                    $stock = intval($line[2] ?? 0);

                    if ($price <= 0) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Invalid price for part number') . " '{$partNumber}'<br>";
                        $errorCount++;
                        $i++;
                        continue;
                    }

                    if ($stock < 0) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Invalid stock for part number') . " '{$partNumber}'<br>";
                        $errorCount++;
                        $i++;
                        continue;
                    }

                    // 3. Check if merchant already has this product
                    $existingMerchantProduct = MerchantProduct::where('product_id', $product->id)
                        ->where('user_id', $user->id)
                        ->first();

                    // 4. Prepare merchant product data
                    $merchantData = [
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'price' => $price / $sign->value,
                        'previous_price' => !empty($line[3]) ? (floatval($line[3]) / $sign->value) : null,
                        'stock' => $stock,
                        'product_condition' => intval($line[4] ?? 2), // Default to 'New'
                        'minimum_qty' => !empty($line[5]) ? $line[5] : null,
                        'ship' => !empty($line[6]) ? $line[6] : null,
                        'preordered' => intval($line[7] ?? 0),
                        'status' => 1,
                    ];

                    // Handle colors (comma-separated)
                    if (!empty($line[8])) {
                        $merchantData['color_all'] = $line[8];
                    }

                    // Handle color prices (comma-separated)
                    if (!empty($line[9])) {
                        $merchantData['color_price'] = $line[9];
                    }

                    // Handle wholesale quantities and discounts
                    if (!empty($line[10])) {
                        $merchantData['whole_sell_qty'] = $line[10];
                    }

                    if (!empty($line[11])) {
                        $merchantData['whole_sell_discount'] = $line[11];
                    }

                    // Handle policy/details override (features not available in merchant_products)
                    if (!empty($line[12])) {
                        $merchantData['policy'] = $line[12];
                    }
                    if (!empty($line[13])) {
                        $merchantData['details'] = $line[13];
                    }

                    // Handle license fields for license products
                    if ($product->type === 'License') {
                        if (!empty($line[14])) {
                            $merchantData['licence_type'] = $line[14];
                        }
                        if (!empty($line[15])) {
                            $merchantData['license'] = $line[15];
                        }
                        if (!empty($line[16])) {
                            $merchantData['license_qty'] = $line[16];
                        }
                    }

                    try {
                        // 5. Create or update merchant product
                        if ($existingMerchantProduct) {
                            $existingMerchantProduct->update($merchantData);
                            $log .= "<br>" . __('Row') . " {$i}: " . __('Updated merchant listing for') . " '{$partNumber}'<br>";
                        } else {
                            MerchantProduct::create($merchantData);
                            $log .= "<br>" . __('Row') . " {$i}: " . __('Created merchant listing for') . " '{$partNumber}'<br>";
                        }
                        $successCount++;
                    } catch (\Exception $e) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Error processing') . " '{$partNumber}': " . $e->getMessage() . "<br>";
                        $errorCount++;
                    }
                }
                $i++;
            }
            fclose($file);

            // Clean up temporary file
            if (file_exists(public_path('assets/temp_files/' . $filename))) {
                unlink(public_path('assets/temp_files/' . $filename));
            }

            //--- Redirect Section
            $summary = "<br><strong>" . __('Import Summary') . ":</strong><br>";
            $summary .= __('Successfully processed') . ": {$successCount}<br>";
            $summary .= __('Errors') . ": {$errorCount}<br>";

            $msg = __('Merchant product import completed.') . $summary . $log;

            if ($successCount > 0) {
                return back()->with('success', $msg);
            } else {
                return back()->with('unsuccess', $msg);
            }
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Products. Package limit reached.'));
        }
    }

    //*** POST Request - Store merchant offer for existing product
    public function storeOffer(Request $request)
    {
        $user = $this->user;
        $sign = $this->curr;

        // Validate required fields
        $rules = [
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_condition' => 'required|in:1,2',
            'brand_quality_id' => 'required|exists:quality_brands,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // Check if vendor already has an offer for this product with the same brand quality
        $existingOffer = MerchantProduct::where('product_id', $request->product_id)
            ->where('user_id', $user->id)
            ->where('brand_quality_id', $request->brand_quality_id)
            ->first();

        if ($existingOffer) {
            return response()->json(array('errors' => ['brand_quality_id' => ['You already have an offer for this product with this brand quality.']]));
        }

        // Prepare merchant product data
        $merchantData = [
            'product_id' => $request->product_id,
            'user_id' => $user->id,
            'brand_quality_id' => $request->brand_quality_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'product_condition' => $request->product_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
            'status' => 1,
        ];

        // Handle colors
        if ($request->color_check && !empty($request->color_all)) {
            $merchantData['color_all'] = implode(',', $request->color_all);
            if (!empty($request->color_price)) {
                $merchantData['color_price'] = implode(',', $request->color_price);
            }
        }

        // Handle wholesale
        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
            }
        }

        // Handle policy/features/details override
        if (!empty($request->policy)) {
            $merchantData['policy'] = $request->policy;
        }
        if (!empty($request->features)) {
            $merchantData['features'] = $request->features;
        }
        if (!empty($request->details)) {
            $merchantData['details'] = $request->details;
        }

        // Create merchant product
        MerchantProduct::create($merchantData);

        return response()->json(['status' => true, 'data' => [], 'error' => []]);
    }

    //*** PUT Request - Update merchant offer
    public function updateOffer(Request $request, $merchantProductId)
    {
        $user = $this->user;
        $sign = $this->curr;

        // Find the merchant product and ensure it belongs to this vendor
        $merchantProduct = MerchantProduct::where('id', $merchantProductId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Validate required fields
        $rules = [
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_condition' => 'required|in:1,2',
            'brand_quality_id' => 'required|exists:brand_qualities,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // Check for brand quality conflicts when editing
        $conflict = MerchantProduct::where('product_id', $merchantProduct->product_id)
            ->where('user_id', $merchantProduct->user_id)
            ->where('brand_quality_id', $request->brand_quality_id)
            ->where('id', '<>', $merchantProduct->id)
            ->exists();

        if ($conflict) {
            return response()->json(['errors' => ['brand_quality_id' => ['You already have an offer for this product with this brand quality.']]]);
        }

        // Prepare merchant product data
        $merchantData = [
            'brand_quality_id' => $request->brand_quality_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'product_condition' => $request->product_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
        ];

        // Handle colors
        if ($request->color_check && !empty($request->color_all)) {
            $merchantData['color_all'] = implode(',', $request->color_all);
            if (!empty($request->color_price)) {
                $merchantData['color_price'] = implode(',', $request->color_price);
            }
        } else {
            $merchantData['color_all'] = null;
            $merchantData['color_price'] = null;
        }

        // Handle wholesale
        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
            }
        } else {
            $merchantData['whole_sell_qty'] = null;
            $merchantData['whole_sell_discount'] = null;
        }

        // Handle policy/features/details override
        $merchantData['policy'] = $request->policy ?: null;
        $merchantData['features'] = $request->features ?: null;
        $merchantData['details'] = $request->details ?: null;

        // Update merchant product
        $merchantProduct->update($merchantData);

        return response()->json(['status' => true, 'data' => [], 'error' => []]);
    }

    //*** POST Request - New workflow for merchant products with part_number
    public function store(Request $request)
    {
        $user    = $this->user;
        $package = $user->subscribes()->latest('id')->first();
        $prods   = $user->merchantProducts()->count();

        if (Generalsetting::find(1)->verify_product == 1) {
            if (!$user->checkStatus()) {
                return back()->with('unsuccess', __('You must complete your verfication first.'));
            }
        }

        if ($prods < $package->allowed_products || $package->allowed_products == 0) {

            //--- Validation Section
            $rules = [
                'part_number' => 'required|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'product_condition' => 'required|in:1,2',
            ];
            $request->validate($rules);

            $sign = $this->curr;
            $partNumber = trim($request->part_number);

            // 1. Search for product by part_number (sku)
            $product = Product::where('sku', $partNumber)->first();

            if (!$product) {
                return back()->with('unsuccess', __('Product with part number "' . $partNumber . '" not found in catalog.'));
            }

            // 2. Check if merchant already has this product
            $existingMerchantProduct = MerchantProduct::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->first();

            // 3. Prepare merchant product data
            $merchantData = [
                'product_id' => $product->id,
                'user_id' => $user->id,
                'price' => $request->price / $sign->value,
                'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
                'stock' => $request->stock,
                'minimum_qty' => $request->minimum_qty ?: null,
                'product_condition' => $request->product_condition,
                'ship' => $request->ship ?: null,
                'preordered' => $request->preordered ? 1 : 0,
                'status' => 1,
            ];

            // Handle colors
            if ($request->color_check && !empty($request->color_all)) {
                $merchantData['color_all'] = implode(',', $request->color_all);
                if (!empty($request->color_price)) {
                    $merchantData['color_price'] = implode(',', $request->color_price);
                }
            }

            // Handle wholesale
            if ($request->whole_check && !empty($request->whole_sell_qty)) {
                $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
                if (!empty($request->whole_sell_discount)) {
                    $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
                }
            }

            // Handle policy/details override (features not available in merchant_products)
            if (!empty($request->policy)) {
                $merchantData['policy'] = $request->policy;
            }
            if (!empty($request->details)) {
                $merchantData['details'] = $request->details;
            }

            // 4. Create or update merchant product
            if ($existingMerchantProduct) {
                $existingMerchantProduct->update($merchantData);
                $msg = __('Merchant product updated successfully.');
            } else {
                MerchantProduct::create($merchantData);
                $msg = __('Merchant product created successfully.');
            }

            return back()->with('success', $msg);
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Product.'));
        }
    }

    //*** GET Request
    public function edit($merchantProductId)
    {
        // Get merchant product data for this vendor by merchant_product_id
        $merchantProduct = MerchantProduct::where('id', $merchantProductId)
            ->where('user_id', $this->user->id)
            ->with('product') // Load the product relationship
            ->firstOrFail(); // Vendor can only edit their own offers

        $data = $merchantProduct->product;
        $sign = $this->curr;

        // Use the new offer edit form (merchants only edit their offers, not catalog data)
        return view('vendor.product.edit.offer', compact('data', 'merchantProduct', 'sign'));
    }

    //*** GET Request CATALOG
    public function catalogedit($id)
    {
        $cats = Category::all();
        $data = Product::findOrFail($id);

        // Get merchant product data for this vendor
        $merchantProduct = MerchantProduct::where('product_id', $id)
            ->where('user_id', $this->user->id)
            ->first();

        $sign = $this->curr;

        if ($data->type == 'Digital') {
            return view('vendor.product.edit.catalog.digital', compact('cats', 'data', 'merchantProduct', 'sign'));
        } elseif ($data->type == 'License') {
            return view('vendor.product.edit.catalog.license', compact('cats', 'data', 'merchantProduct', 'sign'));
        } elseif ($data->type == 'Listing') {
            return view('vendor.product.edit.listing', compact('cats', 'data', 'merchantProduct', 'sign'));
        } else {
            return view('vendor.product.edit.catalog.physical', compact('cats', 'data', 'merchantProduct', 'sign'));
        }
    }

    //*** POST Request
    public function update(Request $request, $merchantProductId)
    {
        //--- Validation Section
        $rules = [
            'file' => 'mimes:zip',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        // عرض البائع
        $merchant = MerchantProduct::where('id', $merchantProductId)
            ->where('user_id', $this->user->id)
            ->with('product')
            ->firstOrFail();

        // تعريف المنتج (هوية)
        $data  = $merchant->product;
        $id    = $data->id; // للاستخدام في الكود القديم
        $sign  = $this->curr;
        $input = $request->all();

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

            // These fields belong to merchant_products, not products - remove from product input
            // They will be handled separately in merchant product update
            unset($input['product_condition'], $input['preordered'], $input['minimum_qty'], $input['ship']);

            // stock_check and inventory fields belong to merchant_products, not products
            // All inventory-related fields are handled in merchant_products
            unset($input['stock_check']);

            // color_all belongs to merchant_products, not products - remove from product input
            // This will be handled separately in merchant product update
            unset($input['color_all']);

            // whole_sell_qty and whole_sell_discount belong to merchant_products, not products
            unset($input['whole_sell_qty'], $input['whole_sell_discount']);

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

        // Features (product-level only, colors moved to merchant_products)
        if (!in_array(null, (array)$request->features)) {
            $input['features'] = implode(',', str_replace(',', ' ', $request->features));
        } else {
            if (in_array(null, (array)$request->features)) {
                $input['features'] = null;
            } else {
                $features = explode(',', (string)$data->features);
                $input['features'] = implode(',', $features);
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

        // Remove merchant-specific data from product input (will be saved in MerchantProduct)
        unset($input['price'], $input['previous_price'], $input['stock']);

        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->sku);
        $data->update($input);

        // تحديث عرض البائع (MerchantProduct)
        $mp = [];

        // المقاسات (MP)
        // All size-related fields (size, size_qty, size_price) belong to products table
        // These are handled in product update, not merchant_products

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

        // All size-related fields (size, size_qty, size_price) belong to products table
        // These are handled in product duplication, not merchant_products

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
