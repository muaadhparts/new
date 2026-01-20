<?php

namespace App\Http\Controllers\Operator;

use App\Models\Spec;
use App\Models\SpecValue;
use App\Models\MonetaryUnit;
use App\Models\MerchantPhoto;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\MerchantCommission;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use Validator;

class CatalogItemController extends OperatorBaseController
{
    //*** JSON Request
    // داخل class CatalogItemController extends OperatorBaseController

    public function datatables(Request $request)
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        // item_type is now on merchant_items, not catalog_items
        // Brand data comes from catalogItem->fitments
        $query = MerchantItem::with(['catalogItem.fitments.brand', 'user', 'qualityBrand'])
            ->where('item_type', 'normal');

        if ($request->type == 'deactive') {
            $query->where('status', 0);
        }

        $datas = $query->latest('id');

        return \Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereHas('catalogItem', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('part_number', 'like', "%{$keyword}%")
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

                $prodLink = $catalogItem->part_number
                    ? route('front.part-result', $catalogItem->part_number)
                    : '#';

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $part_number = $catalogItem->part_number ? '<br><small class="text-muted">' . __('PART_NUMBER') . ': ' . $catalogItem->part_number . '</small>' : '';
                $condition = $mp->item_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $part_number . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
                // All brands from catalog_item_fitments (vehicle compatibility)
                $fitments = $mp->catalogItem?->fitments ?? collect();
                $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                $count = $brands->count();
                if ($count === 0) return __('N/A');
                if ($count === 1) return getLocalizedBrandName($brands->first());
                return __('Fits') . ' ' . $count . ' ' . __('brands');
            })
            ->addColumn('quality_brand', function (MerchantItem $mp) {
                return $mp->qualityBrand ? getLocalizedQualityName($mp->qualityBrand) : __('N/A');
            })
            ->addColumn('merchant', function (MerchantItem $mp) {
                if (!$mp->user) return __('N/A');
                $shopName = $mp->user->shop_name ?: $mp->user->name;
                return '<span name="' . $mp->user->name . '">' . $shopName . '</span>';
            })
            ->addColumn('price', function (MerchantItem $mp) {
                $price = (float) $mp->price;

                // استخدام عمولة التاجر الخاصة بدلاً من العمولة العامة
                $commission = MerchantCommission::where('user_id', $mp->user_id)
                    ->where('is_active', true)
                    ->first();

                if ($commission) {
                    $base = $commission->getPriceWithCommission($price);
                } else {
                    $base = $price; // لا عمولة إذا لم يتم تعيينها
                }

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

                // حالة السجل التجاري
                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('operator-merchant-item-status', ['id' => $mp->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('operator-merchant-item-status', ['id' => $mp->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('operator-catalog-item-edit', $mp->id) . '"><i class="fas fa-edit"></i> ' . __("Edit CatalogItem") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $catalogItem->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>
                        <a href="javascript:;" data-href="' . route('operator-catalog-item-delete', $catalogItem->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete CatalogItem") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant'])
            ->toJson();
    }

    //*** JSON Request
    public function catalogdatatables()
    {
        // الاستعلام على السجلات التجارية مباشرة - كل سجل تجاري = صف مستقل
        // Brand data comes from catalogItem->fitments
        $query = MerchantItem::with(['catalogItem.fitments.brand', 'user', 'qualityBrand'])
            ->where('status', 1);

        $datas = $query->latest('id');

        return \Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereHas('catalogItem', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('part_number', 'like', "%{$keyword}%")
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

                $prodLink = $catalogItem->part_number
                    ? route('front.part-result', $catalogItem->part_number)
                    : '#';

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $part_number = $catalogItem->part_number ? '<br><small class="text-muted">' . __('PART_NUMBER') . ': ' . $catalogItem->part_number . '</small>' : '';
                $condition = $mp->item_condition == 1 ? '<span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $part_number . ' ' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
                // All brands from catalog_item_fitments (vehicle compatibility)
                $fitments = $mp->catalogItem?->fitments ?? collect();
                $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                $count = $brands->count();
                if ($count === 0) return __('N/A');
                if ($count === 1) return getLocalizedBrandName($brands->first());
                return __('Fits') . ' ' . $count . ' ' . __('brands');
            })
            ->addColumn('quality_brand', function (MerchantItem $mp) {
                return $mp->qualityBrand ? getLocalizedQualityName($mp->qualityBrand) : __('N/A');
            })
            ->addColumn('merchant', function (MerchantItem $mp) {
                if (!$mp->user) return __('N/A');
                $shopName = $mp->user->shop_name ?: $mp->user->name;
                return '<span name="' . $mp->user->name . '">' . $shopName . '</span>';
            })
            ->addColumn('price', function (MerchantItem $mp) {
                $price = (float) $mp->price;

                // استخدام عمولة التاجر الخاصة بدلاً من العمولة العامة
                $commission = MerchantCommission::where('user_id', $mp->user_id)
                    ->where('is_active', true)
                    ->first();

                if ($commission) {
                    $base = $commission->getPriceWithCommission($price);
                } else {
                    $base = $price; // لا عمولة إذا لم يتم تعيينها
                }

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
                        <option data-val="1" value="' . route('operator-merchant-item-status', ['id' => $mp->id, 'status' => 1]) . '" ' . $s . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('operator-merchant-item-status', ['id' => $mp->id, 'status' => 0]) . '" ' . $ns . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return '';

                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . route('operator-catalog-item-edit', $mp->id) . '"><i class="fas fa-edit"></i> ' . __("Edit CatalogItem") . '</a>
                        <a href="javascript" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="' . $catalogItem->id . '"><i class="fas fa-eye"></i> ' . __("View Gallery") . '</a>
                        <a href="javascript:;" data-href="' . route('operator-catalog-item-catalog', ['id1' => $catalogItem->id, 'id2' => 0]) . '" data-bs-toggle="modal" data-bs-target="#catalog-modal"><i class="fas fa-trash-alt"></i> ' . __("Remove Catalog") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant'])
            ->toJson();
    }

    public function catalogItemsCatalog()
    {
        return view('operator.catalog-item.catalog');
    }

    public function index()
    {
        return view('operator.catalog-item.index');
    }

    public function deactive()
    {
        return view('operator.catalog-item.deactive');
    }

    public function catalogItemSettings()
    {
        return view('operator.catalog-item.settings');
    }

    //*** GET Request
    public function create()
    {
        $cats = collect();
        $sign = $this->curr;
        return view('operator.catalog-item.create.items', compact('cats', 'sign'));
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
        //--- Logic Section
        $data = new CatalogItem;
        $sign = $this->curr;
        $input = $request->all();

        // Handle photo - optional, use default if not provided
        if (!empty($request->photo)) {
            $image = $request->photo;
            list($type, $image) = explode(';', $image);
            list(, $image) = explode(',', $image);
            $image = base64_decode($image);
            $image_name = time() . Str::random(8) . '.png';
            $path = 'assets/images/catalogItems/' . $image_name;
            file_put_contents($path, $image);
            $input['photo'] = $image_name;
        } else {
            $input['photo'] = null; // Will use default noimage.png in views
        }

        // Validate part_number
        $rules = ['part_number' => 'min:8|unique:catalog_items'];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        if ($request->mesasure_check == "") {
            $input['measure'] = null;
        }

        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', $request->meta_tag);
            }
        }

        // Features now go to merchant_items, not catalog_items
        $merchantFeatures = null;
        if (!empty($request->features) && !in_array(null, $request->features)) {
            $merchantFeatures = implode(',', str_replace(',', ' ', $request->features));
        }

        // Details and Policy now go to merchant_items
        $merchantDetails = $request->input('details');
        $merchantPolicy = $request->input('policy');

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        }

        // Legacy fields removed - prices now handled via MerchantItem
        $basePrice = isset($input['price']) ? ($input['price'] / $sign->value) : 0;
        $basePreviousPrice = isset($input['previous_price']) ? ($input['previous_price'] / $sign->value) : null;

        // Store merchant-specific data before removing from input
        $merchantId = (int) ($request->input('user_id') ?? 0);
        $qualityBrandId = $request->input('quality_brand_id') ?: null;

        // Remove merchant-specific fields from catalog item table input
        unset(
            $input['price'],
            $input['previous_price'],
            $input['stock'],
            $input['user_id'],
            $input['quality_brand_id'],
            $input['merchant_id'],
            $input['details'],
            $input['policy'],
            $input['features'],
            $input['colors']
        );
        if ($request->cross_items) {
            $input['cross_items'] = implode(',', $request->cross_items);
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
            // Process checkbox-dependent values
            $itemCondition = $request->item_condition_check == "" ? 0 : ($request->input('item_condition') ?? 0);
            $preordered = $request->preordered_check == "" ? 0 : ($request->input('preordered') ?? 0);
            $minimumQty = $request->minimum_qty_check == "" ? null : $request->input('minimum_qty');
            $shipTime = $request->shipping_time_check == "" ? null : $request->input('ship');
            $wholeSellQty = !empty($request->whole_sell_qty) && $request->whole_check ? implode(',', $request->whole_sell_qty) : null;
            $wholeSellDiscount = !empty($request->whole_sell_discount) && $request->whole_check ? implode(',', $request->whole_sell_discount) : null;

            MerchantItem::create([
                'catalog_item_id' => $data->id,
                'user_id' => $merchantId,
                'quality_brand_id' => $qualityBrandId,
                'price' => $basePrice,
                'previous_price' => $basePreviousPrice,
                'stock' => (int) $request->input('stock', 0),
                'minimum_qty' => $minimumQty,
                'whole_sell_qty' => $wholeSellQty,
                'whole_sell_discount' => $wholeSellDiscount,
                'ship' => $shipTime,
                'item_condition' => $itemCondition,
                'preordered' => $preordered,
                'details' => $merchantDetails,
                'policy' => $merchantPolicy,
                'features' => $merchantFeatures,
                'status' => 1
            ]);
        }

        // Set Slug
        $catalogItem = CatalogItem::find($data->id);
        $catalogItem->slug = Str::slug($data->name, '-') . '-' . strtolower($data->part_number);

        // Set Thumbnail
        $img = Image::make('assets/images/catalogItems/' . $catalogItem->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save('assets/images/thumbnails/' . $thumbnail);
        $catalogItem->thumbnail = $thumbnail;
        $catalogItem->update();

        // Add To Merchant Photos If any
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                if (in_array($key, $request->galval)) {
                    $merchantPhoto = new MerchantPhoto;
                    $name = time() . \Str::random(8) . str_replace(' ', '', $file->getClientOriginalExtension());
                    $file->move('assets/images/merchant-photos', $name);
                    $merchantPhoto['photo'] = $name;
                    $merchantPhoto['catalog_item_id'] = $lastid;
                    $merchantPhoto->save();
                }
            }
        }
        //logic Section Ends

        //--- Redirect Section
        $msg = __("New CatalogItem Added Successfully.") . '<a href="' . route('operator-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($merchantItemId)
    {
        $cats = collect();
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->findOrFail($merchantItemId);
        $data = $merchantItem->catalogItem;
        $sign = $this->curr;

        // Get merchants list for dropdown - only verified active merchants
        // is_merchant=2 means verified, status=2 means active - admin is NOT a merchant
        $merchants = \App\Models\User::where('is_merchant', 2)->where('status', 2)->get();

        // Get quality brands for dropdown
        $qualityBrands = \App\Models\QualityBrand::all();

        return view('operator.catalog-item.edit.items', compact('cats', 'data', 'merchantItem', 'sign', 'merchants', 'qualityBrands'));
    }

    //*** POST Request
    public function update(Request $request, $merchantItemId)
    {
        //-- Logic Section
        $merchantItem = MerchantItem::findOrFail($merchantItemId);
        $data = CatalogItem::findOrFail($merchantItem->catalog_item_id);
        $sign = $this->curr;
        $input = $request->all();

        //--- Validation Section
        $rules = ['part_number' => 'min:8|unique:catalog_items,part_number,' . $data->id];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        // Prepare merchant-specific values before processing
        $itemCondition = $request->item_condition_check == "" ? 0 : ($request->input('item_condition') ?? 0);
        $preordered = $request->preordered_check == "" ? 0 : ($request->input('preordered') ?? 0);
        $minimumQty = $request->minimum_qty_check == "" ? null : $request->input('minimum_qty');
        $shipTime = $request->shipping_time_check == "" ? null : $request->input('ship');
        $wholeSellQty = !empty($request->whole_sell_qty) ? implode(',', $request->whole_sell_qty) : null;
        $wholeSellDiscount = !empty($request->whole_sell_discount) ? implode(',', $request->whole_sell_discount) : null;

        // Check Measure (this stays on CatalogItem)
        if ($request->measure_check == "") {
            $input['measure'] = null;
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

        // Prepare merchant-specific text fields
        $details = $request->input('details');
        $policy = $request->input('policy');
        $features = null;
        if (!empty($request->features) && !in_array(null, $request->features)) {
            $features = implode(',', str_replace(',', ' ', $request->features));
        }

        // Remove merchant-specific fields from catalog item table
        // These fields belong ONLY to merchant_items table
        unset(
            $input['price'],
            $input['previous_price'],
            $input['stock'],
            $input['user_id'],
            $input['merchant_id'],
            $input['quality_brand_id'],
            $input['item_condition'],
            $input['preordered'],
            $input['minimum_qty'],
            $input['ship'],
            $input['whole_sell_qty'],
            $input['whole_sell_discount'],
            $input['item_condition_check'],
            $input['preordered_check'],
            $input['minimum_qty_check'],
            $input['shipping_time_check'],
            $input['whole_check'],
            $input['details'],
            $input['policy'],
            $input['features'],
            $input['colors']
        );

        // store filtering attributes for physical catalog item
        // Old category attribute system removed - categories now linked via TreeCategories
        $attrArr = [];
        if (empty($attrArr)) {
            $input['attributes'] = null;
        } else {
            $jsonAttr = json_encode($attrArr);
            $input['attributes'] = $jsonAttr;
        }
        if ($request->cross_items) {
            $input['cross_items'] = implode(',', $request->cross_items);
        }
        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->part_number);

        $data->update($input);

        // Update merchant_item entry
        $merchantItem->update([
            'user_id' => (int) ($request->input('merchant_id') ?? $merchantItem->user_id),
            'quality_brand_id' => $request->input('quality_brand_id') ?: null,
            'price' => $basePrice,
            'previous_price' => $basePreviousPrice,
            'stock' => $request->input('stock') !== null ? (int) $request->input('stock') : null,
            'minimum_qty' => $minimumQty,
            'whole_sell_qty' => $wholeSellQty,
            'whole_sell_discount' => $wholeSellDiscount,
            'ship' => $shipTime,
            'item_condition' => $itemCondition,
            'preordered' => $preordered,
            'details' => $details,
            'policy' => $policy,
            'features' => $features,
        ]);
        //-- Logic Section Ends

        //--- Redirect Section
        $msg = __("CatalogItem Updated Successfully.") . '<a href="' . route('operator-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function destroy($id)
    {
        $data = CatalogItem::findOrFail($id);
        if ($data->merchantPhotos->count() > 0) {
            foreach ($data->merchantPhotos as $photo) {
                if (file_exists(public_path() . '/assets/images/merchant-photos/' . $photo->photo)) {
                    unlink(public_path() . '/assets/images/merchant-photos/' . $photo->photo);
                }
                $photo->delete();
            }
        }

        if ($data->abuseFlags->count() > 0) {
            foreach ($data->abuseFlags as $gal) {
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
        if ($data->buyerNotes->count() > 0) {
            foreach ($data->buyerNotes as $gal) {
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

        $data->delete();
        //--- Redirect Section
        $msg = __('CatalogItem Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    /**
     * @deprecated is_catalog column removed (2026-01-20)
     * This feature is no longer available - all items with merchant listings are catalog items
     */
    public function catalog($id1, $id2)
    {
        // is_catalog column has been removed from catalog_items table
        // All items with active merchant listings are now considered catalog items
        return response()->json(__('This feature has been removed. All items with active merchant listings are automatically in the catalog.'));
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

    public function getSpecs(Request $request)
    {
        $model = '';
        if ($request->type == 'category') {
            $model = 'App\Models\Category';
        } elseif ($request->type == 'subcategory') {
            $model = 'App\Models\Subcategory';
        } elseif ($request->type == 'childcategory') {
            $model = 'App\Models\Childcategory';
        }

        $specs = Spec::where('specable_id', $request->id)->where('specable_type', $model)->get();
        $specOptions = [];
        foreach ($specs as $key => $spec) {
            $options = SpecValue::where('spec_id', $spec->id)->get();
            $specOptions[] = ['spec' => $spec, 'options' => $options];
        }
        return response()->json($specOptions);
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
