<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Spec;
use App\Models\SpecValue;
use App\Models\MonetaryUnit;
use App\Models\MerchantPhoto;
use App\Models\Muaadhsetting;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Image;
use Validator;

class CatalogItemController extends MerchantBaseController
{

    public function index()
    {
        $user = $this->user;

        // Get CatalogItems that belong to this merchant via merchant_items
        // Note: brand relationship removed from CatalogItem (2026-01-20)
        // brand_id is now in merchant_items table
        $datas = CatalogItem::whereHas('merchantItems', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('item_type', 'normal');
            })
            ->with([
                'merchantItems' => function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->with(['qualityBrand', 'user']);
                },
                'fitments.brand'  // brand via catalog_item_fitments
            ])
            ->latest('id')
            ->paginate(10);

        return view('merchant.catalog-item.index', compact('datas'));
    }

    //*** GET Request - NEW SIMPLIFIED ADD CATALOG ITEM PAGE
    public function add()
    {
        $user = $this->user;

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                Session::flash('unsuccess', __('You must complete your trust badge first.'));
                return redirect()->route('merchant-trust-badge');
            }
        }

        $sign = $this->curr;
        return view('merchant.catalog-item.create.add', compact('sign'));
    }

    //*** GET Request - SEARCH CATALOG ITEM BY PART_NUMBER (AJAX)
    public function searchSku(Request $request)
    {
        $user = $this->user;
        $part_number = trim($request->input('part_number'));

        if (empty($part_number)) {
            return response()->json([
                'success' => false,
                'message' => __('Please enter a PART_NUMBER or Part Number')
            ]);
        }

        // Search for catalog item by PART_NUMBER
        // Note: brand relationship removed from CatalogItem (2026-01-20)
        $catalogItem = CatalogItem::where('part_number', $part_number)->first();

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('Catalog item with PART_NUMBER "') . $part_number . __('" not found in catalog.')
            ]);
        }

        // Check if merchant already has an offer for this catalog item
        $existingOffer = MerchantItem::where('catalog_item_id', $catalogItem->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingOffer) {
            return response()->json([
                'success' => true,
                'already_exists' => true,
                'edit_url' => route('merchant-catalog-item-edit', $existingOffer->id),
                'catalog_item' => [
                    'id' => $catalogItem->id,
                    'name' => $catalogItem->name,
                    'part_number' => $catalogItem->part_number,
                ]
            ]);
        }

        // Get photo URL
        $photoUrl = asset('assets/images/noimage.png');
        if ($catalogItem->photo) {
            if (filter_var($catalogItem->photo, FILTER_VALIDATE_URL)) {
                $photoUrl = $catalogItem->photo;
            } else {
                $photoUrl = asset('assets/images/catalogItems/' . $catalogItem->photo);
            }
        }

        return response()->json([
            'success' => true,
            'already_exists' => false,
            'catalog_item' => [
                'id' => $catalogItem->id,
                'name' => $catalogItem->name,
                'part_number' => $catalogItem->part_number,
                'photo' => $photoUrl,
            ]
        ]);
    }

    public function catalogs()
    {
        $user = $this->user;

        // Get catalog items that have active merchant listings
        // Note: is_catalog column removed (2026-01-20) - old tree system
        // Now all items with merchant listings are considered catalog items
        $datas = CatalogItem::whereHas('merchantItems', function($q) {
                $q->where('item_type', 'normal')
                  ->where('status', 1);
            })
            ->latest('id')
            ->get();

        return view('merchant.catalog-item.catalogs', compact('datas', 'user'));
    }

    //*** GET Request - Show create form for catalog item
    public function create($slug)
    {
        $user = $this->user;

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                Session::flash('unsuccess', __('You must complete your trust badge first.'));
                return redirect()->route('merchant-trust-badge');
            }
        }

        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $sign = $this->curr;

        if ($slug === 'items') {
            return view('merchant.catalog-item.create.items', compact('cats', 'sign'));
        }

        Session::flash('unsuccess', __('Invalid catalog item type.'));
        return redirect()->route('merchant-catalog-item-types');
    }

    //*** GET Request - Create merchant offer for existing catalog item
    public function createOffer($catalog_item_id)
    {
        $user = $this->user;
        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                Session::flash('unsuccess', __('You must complete your trust badge first.'));
                return redirect()->route('merchant-trust-badge');
            }
        }

        // Check if catalog item exists
        // Note: is_catalog column removed (2026-01-20)
        $catalogItem = CatalogItem::findOrFail($catalog_item_id);

        // Check if merchant already has an offer for this catalog item
        $existingOffer = MerchantItem::where('catalog_item_id', $catalog_item_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingOffer) {
            Session::flash('unsuccess', __('You already have an offer for this catalog item.'));
            return redirect()->route('merchant-catalog-item-catalogs');
        }

        // Get merchant branches for selection
        $branches = \App\Models\MerchantBranch::where('user_id', $user->id)
            ->where('status', 1)
            ->get();

        $sign = $this->curr;
        return view('merchant.catalog-item.create.offer', compact('catalogItem', 'sign', 'branches'));
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $userId = $this->user->id;
        $merchantItem = MerchantItem::where('id', $id1)
            ->where('user_id', $userId)
            ->firstOrFail();

        $merchantItem->status = (int) $id2;
        $merchantItem->save();

        return back()->with("success", __('Status Updated Successfully.'));
    }

    //*** POST Request
    public function uploadUpdate(Request $request, $id)
    {
        $rules = [
            'image' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $data = CatalogItem::findOrFail($id);

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

        $img = Image::make(public_path() . '/assets/images/catalogItems/' . $data->photo)->resize(285, 285);
        $thumbnail = time() . Str::random(8) . '.jpg';
        $img->save(public_path() . '/assets/images/thumbnails/' . $thumbnail);
        $data->thumbnail = $thumbnail;
        $data->update();

        return response()->json(['status' => true, 'file_name' => $image_name]);
    }

    //*** POST Request - Store merchant offer for existing catalog item
    public function storeOffer(Request $request)
    {
        $user = $this->user;
        $sign = $this->curr;

        $rules = [
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'item_condition' => 'required|in:1,2',
            'quality_brand_id' => 'required|exists:quality_brands,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validate branch belongs to merchant
        $branch = \App\Models\MerchantBranch::where('id', $request->merchant_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$branch) {
            Session::flash('unsuccess', __('Invalid branch selected.'));
            return redirect()->back()->withInput();
        }

        // Check if offer exists for same catalog_item + branch combination
        $existingOffer = MerchantItem::where('catalog_item_id', $request->catalog_item_id)
            ->where('user_id', $user->id)
            ->where('merchant_branch_id', $request->merchant_branch_id)
            ->first();

        if ($existingOffer) {
            Session::flash('unsuccess', __('You already have an offer for this catalog item in this branch.'));
            return redirect()->route('merchant-catalog-item-edit', $existingOffer->id);
        }

        $merchantData = [
            'catalog_item_id' => $request->catalog_item_id,
            'user_id' => $user->id,
            'merchant_branch_id' => $request->merchant_branch_id,
            'quality_brand_id' => $request->quality_brand_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'item_condition' => $request->item_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
            'status' => 1,
        ];

        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
            }
        }

        if (!empty($request->policy)) {
            $merchantData['policy'] = $request->policy;
        }
        if (!empty($request->features)) {
            $merchantData['features'] = $request->features;
        }
        if (!empty($request->details)) {
            $merchantData['details'] = $request->details;
        }

        MerchantItem::create($merchantData);

        Session::flash('success', __('Catalog item added successfully.'));
        return redirect()->route('merchant-catalog-item-index');
    }

    //*** PUT Request - Update merchant offer
    public function updateOffer(Request $request, $merchantItemId)
    {
        $user = $this->user;
        $sign = $this->curr;

        $merchantItem = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $rules = [
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'item_condition' => 'required|in:1,2',
            'quality_brand_id' => 'required|exists:quality_brands,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validate branch belongs to merchant
        $branch = \App\Models\MerchantBranch::where('id', $request->merchant_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$branch) {
            return redirect()->back()->withErrors(['merchant_branch_id' => __('Invalid branch selected.')])->withInput();
        }

        // Check for conflict (same item + branch + quality brand combination)
        $conflict = MerchantItem::where('catalog_item_id', $merchantItem->catalog_item_id)
            ->where('user_id', $merchantItem->user_id)
            ->where('merchant_branch_id', $request->merchant_branch_id)
            ->where('quality_brand_id', $request->quality_brand_id)
            ->where('id', '<>', $merchantItem->id)
            ->exists();

        if ($conflict) {
            return redirect()->back()->withErrors(['quality_brand_id' => __('You already have an offer for this catalog item in this branch with this quality brand.')])->withInput();
        }

        $merchantData = [
            'merchant_branch_id' => $request->merchant_branch_id,
            'quality_brand_id' => $request->quality_brand_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'item_condition' => $request->item_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
        ];

        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
            }
        } else {
            $merchantData['whole_sell_qty'] = null;
            $merchantData['whole_sell_discount'] = null;
        }

        $merchantData['policy'] = $request->policy ?: null;
        $merchantData['features'] = $request->features ?: null;
        $merchantData['details'] = $request->details ?: null;

        $merchantItem->update($merchantData);

        Session::flash('success', __('Offer updated successfully.'));
        return redirect()->route('merchant-catalog-item-index');
    }

    //*** POST Request - New workflow for merchant items with part_number
    public function store(Request $request)
    {
        $user    = $this->user;
        $package = $user->membershipPlans()->latest('id')->first();
        $prods   = $user->merchantItems()->count();

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                return back()->with('unsuccess', __('You must complete your trust badge first.'));
            }
        }

        if ($prods < $package->allowed_items || $package->allowed_items == 0) {

            $rules = [
                'part_number' => 'required|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'item_condition' => 'required|in:1,2',
            ];
            $request->validate($rules);

            $sign = $this->curr;
            $partNumber = trim($request->part_number);

            // 1. Search for catalog item by part_number (part_number)
            $catalogItem = CatalogItem::where('part_number', $partNumber)->first();

            if (!$catalogItem) {
                return back()->with('unsuccess', __('Catalog item with part number "' . $partNumber . '" not found in catalog.'));
            }

            // 2. Check if merchant already has this catalog item
            $existingMerchantItem = MerchantItem::where('catalog_item_id', $catalogItem->id)
                ->where('user_id', $user->id)
                ->first();

            // 3. Prepare merchant item data
            $merchantData = [
                'catalog_item_id' => $catalogItem->id,
                'user_id' => $user->id,
                'price' => $request->price / $sign->value,
                'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
                'stock' => $request->stock,
                'minimum_qty' => $request->minimum_qty ?: null,
                'item_condition' => $request->item_condition,
                'ship' => $request->ship ?: null,
                'preordered' => $request->preordered ? 1 : 0,
                'status' => 1,
            ];

            if ($request->whole_check && !empty($request->whole_sell_qty)) {
                $merchantData['whole_sell_qty'] = implode(',', $request->whole_sell_qty);
                if (!empty($request->whole_sell_discount)) {
                    $merchantData['whole_sell_discount'] = implode(',', $request->whole_sell_discount);
                }
            }

            if (!empty($request->policy)) {
                $merchantData['policy'] = $request->policy;
            }
            if (!empty($request->details)) {
                $merchantData['details'] = $request->details;
            }

            // 4. Create or update merchant item
            if ($existingMerchantItem) {
                $existingMerchantItem->update($merchantData);
                $msg = __('Merchant item updated successfully.');
            } else {
                MerchantItem::create($merchantData);
                $msg = __('Merchant item created successfully.');
            }

            return back()->with('success', $msg);
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Items.'));
        }
    }

    //*** GET Request
    public function edit($merchantItemId)
    {
        $merchantItem = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $this->user->id)
            ->with('catalogItem')
            ->firstOrFail();

        $data = $merchantItem->catalogItem;
        $sign = $this->curr;

        // Get merchant branches for selection
        $branches = \App\Models\MerchantBranch::where('user_id', $this->user->id)
            ->where('status', 1)
            ->get();

        return view('merchant.catalog-item.edit.offer', compact('data', 'merchantItem', 'sign', 'branches'));
    }

    //*** GET Request CATALOG
    public function catalogedit($id)
    {
        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $data = CatalogItem::findOrFail($id);

        $merchantItem = MerchantItem::where('catalog_item_id', $id)
            ->where('user_id', $this->user->id)
            ->first();

        $sign = $this->curr;

        return view('merchant.catalog-item.edit.items', compact('cats', 'data', 'merchantItem', 'sign'));
    }

    //*** POST Request
    public function update(Request $request, $merchantItemId)
    {
        $rules = [
            'file' => 'mimes:zip',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $merchant = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $this->user->id)
            ->with('catalogItem')
            ->firstOrFail();

        $data  = $merchant->catalogItem;
        $id    = $data->id;
        $sign  = $this->curr;
        $input = $request->all();

        // Physical-only system - validate part_number
        $rules = ['part_number' => 'min:8|unique:catalog_items,part_number,' . $id];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        unset($input['item_condition'], $input['preordered'], $input['minimum_qty'], $input['ship']);
        unset($input['stock_check']);
        unset($input['whole_sell_qty'], $input['whole_sell_discount']);

        if ($request->measure_check == "") {
            $input['measure'] = null;
        }

        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = $request->meta_tag;
                $input['is_meta']  = 1;
            }
        }

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

        if (!empty($request->tags)) { $input['tags'] = $request->tags; }
        if (empty($request->tags))  { $input['tags'] = null; }

        // Old category attribute system removed - categories now linked via TreeCategories
        $attrArr = [];
        $input['attributes'] = empty($attrArr) ? null : json_encode($attrArr);

        unset($input['price'], $input['previous_price'], $input['stock']);

        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->part_number);
        $data->update($input);

        // Update merchant item
        $mp = [];

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

        $mp['price']          = $request->filled('price') ? ($request->price / $sign->value) : $merchant->price;
        $mp['previous_price'] = $request->filled('previous_price') ? ($request->previous_price / $sign->value) : $merchant->previous_price;
        $mp['stock']          = (int) $request->input('stock', $merchant->stock);
        $mp['preordered']     = $request->input('preordered', $merchant->preordered);
        $mp['minimum_qty']    = $request->input('minimum_qty') ?: $merchant->minimum_qty;
        $mp['stock_check']    = $request->input('stock_check', $merchant->stock_check);
        $mp['ship']           = $request->input('ship') ?: $merchant->ship;
        $mp['item_condition'] = $request->input('item_condition', $merchant->item_condition);

        $merchant->update($mp);

        // Merchant Photos
        $lastid = $data->id;
        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $extensions = ['jpeg', 'jpg', 'png', 'svg'];
                if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                    return response()->json(array('errors' => ['Image format not supported']));
                }

                $merchantPhoto = new MerchantPhoto;
                $name = \PriceHelper::ImageCreateName($file);
                $img = Image::make($file->getRealPath())->resize(800, 800);
                $img->save(public_path() . '/assets/images/merchant-photos/' . $name);
                $merchantPhoto['photo'] = $name;
                $merchantPhoto['catalog_item_id'] = $lastid;
                $merchantPhoto->save();
            }
        }

        $msg = __('Catalog Item Updated Successfully.');
        return back()->with('success', $msg);
    }

    //*** POST Request CATALOG
    public function catalogupdate(Request $request, $id)
    {
        $user = $this->user;
        $package = $user->membershipPlans()->latest('id')->first();
        $prods = $user->merchantItems()->count();

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                return back()->with('unsuccess', __('You must complete your trust badge first.'));
            }
        }

        if (!($prods < $package->allowed_items || $package->allowed_items == 0)) {
            return back()->with('unsuccess', __('You Can\'t Add More Items.'));
        }

        $catalogItem = CatalogItem::findOrFail($id);
        $sign    = $this->curr;

        $rules = [
            'file' => 'mimes:zip',
        ];
        $request->validate($rules);

        $mp = [
            'catalog_item_id' => $catalogItem->id,
            'user_id'    => $user->id,
            'price'          => $request->filled('price') ? ($request->price / $sign->value) : 0.0,
            'previous_price' => $request->filled('previous_price') ? ($request->previous_price / $sign->value) : null,
            'stock'          => (int) $request->input('stock', 0),
            'preordered'     => (int) $request->input('preordered', 0),
            'minimum_qty'    => $request->input('minimum_qty') ?: null,
            'stock_check'    => $request->input('stock_check', 0),
            'ship'           => $request->input('ship') ?: null,
            'item_condition' => (int) $request->input('item_condition', 0),
            'status'         => 1,
        ];

        if (!empty($request->whole_sell_qty)) {
            $mp['whole_sell_qty'] = implode(',', (array)$request->whole_sell_qty);
        }
        if (!empty($request->whole_sell_discount)) {
            $mp['whole_sell_discount'] = implode(',', (array)$request->whole_sell_discount);
        }

        MerchantItem::updateOrCreate(
            ['catalog_item_id' => $catalogItem->id, 'user_id' => $user->id],
            $mp
        );

        $msg = __('New Catalog Item Added Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request
    public function destroy($id)
    {
        $user = $this->user;
        $mp = MerchantItem::where('catalog_item_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($mp) {
            $mp->delete();
        }

        $msg = __('Catalog Item Deleted Successfully.');
        return back()->with('success', $msg);
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
}
