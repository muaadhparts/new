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
        $datas = CatalogItem::whereHas('merchantItems', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('item_type', 'normal');
            })
            ->with([
                'brand',
                'merchantItems' => function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->with(['qualityBrand', 'user']);
                }
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
        $catalogItem = CatalogItem::where('part_number', $part_number)
            ->with(['brand', 'category'])
            ->first();

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
                'brand' => $catalogItem->brand ? $catalogItem->brand->name : null,
                'photo' => $photoUrl,
            ]
        ]);
    }

    public function catalogs()
    {
        $user = $this->user;

        $datas = CatalogItem::whereHas('merchantItems', function($q) {
                $q->where('item_type', 'normal');
            })
            ->where('is_catalog', '=', 1)
            ->status(1)
            ->latest('id')
            ->get();

        return view('merchant.catalog-item.catalogs', compact('datas', 'user'));
    }

    //*** GET Request - Show create form for physical catalog item
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

        // Physical-only system - all items are physical
        if ($slug === 'physical') {
            return view('merchant.catalog-item.create.physical', compact('cats', 'sign'));
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

        // Check if catalog item exists and is in catalog
        $catalogItem = CatalogItem::where('id', $catalog_item_id)
            ->where('is_catalog', 1)
            ->firstOrFail();

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

    //*** POST Request
    public function import()
    {
        // TODO: Removed - old category system
        $cats = collect(); // Category::all();
        $sign = $this->curr;
        return view('merchant.catalog-item.catalogitemcsv', compact('cats', 'sign'));
    }

    public function importSubmit(Request $request)
    {
        $user = $this->user;
        $package = $user->membershipPlans()->orderBy('id', 'desc')->first();

        // Count merchant items
        $prods = $user->merchantItems()->count();

        if (Muaadhsetting::find(1)->verify_item == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                return back()->with('unsuccess', __('You must complete your trust badge first.'));
            }
        }

        if ($prods < $package->allowed_items || $package->allowed_items == 0) {
            $log = "";
            $successCount = 0;
            $errorCount = 0;

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
                if ($i != 1) {
                    $partNumber = trim($line[0] ?? '');

                    if (empty($partNumber)) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Missing part number (PART_NUMBER)') . "<br>";
                        $errorCount++;
                        $i++;
                        continue;
                    }

                    // 1. Find existing catalog item by part_number (part_number)
                    $catalogItem = CatalogItem::where('part_number', $partNumber)->first();

                    if (!$catalogItem) {
                        $log .= "<br>" . __('Row') . " {$i}: " . __('Catalog item with part number') . " '{$partNumber}' " . __('not found in catalog') . "<br>";
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

                    // 3. Check if merchant already has this catalog item
                    $existingMerchantItem = MerchantItem::where('catalog_item_id', $catalogItem->id)
                        ->where('user_id', $user->id)
                        ->first();

                    // 4. Prepare merchant item data
                    $merchantData = [
                        'catalog_item_id' => $catalogItem->id,
                        'user_id' => $user->id,
                        'price' => $price / $sign->value,
                        'previous_price' => !empty($line[3]) ? (floatval($line[3]) / $sign->value) : null,
                        'stock' => $stock,
                        'item_condition' => intval($line[4] ?? 2),
                        'minimum_qty' => !empty($line[5]) ? $line[5] : null,
                        'ship' => !empty($line[6]) ? $line[6] : null,
                        'preordered' => intval($line[7] ?? 0),
                        'status' => 1,
                    ];

                    if (!empty($line[8])) {
                        $merchantData['color_all'] = $line[8];
                    }

                    if (!empty($line[9])) {
                        $merchantData['color_price'] = $line[9];
                    }

                    if (!empty($line[10])) {
                        $merchantData['whole_sell_qty'] = $line[10];
                    }

                    if (!empty($line[11])) {
                        $merchantData['whole_sell_discount'] = $line[11];
                    }

                    if (!empty($line[12])) {
                        $merchantData['policy'] = $line[12];
                    }
                    if (!empty($line[13])) {
                        $merchantData['details'] = $line[13];
                    }


                    try {
                        // 5. Create or update merchant item
                        if ($existingMerchantItem) {
                            $existingMerchantItem->update($merchantData);
                            $log .= "<br>" . __('Row') . " {$i}: " . __('Updated merchant listing for') . " '{$partNumber}'<br>";
                        } else {
                            MerchantItem::create($merchantData);
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

            if (file_exists(public_path('assets/temp_files/' . $filename))) {
                unlink(public_path('assets/temp_files/' . $filename));
            }

            $summary = "<br><strong>" . __('Import Summary') . ":</strong><br>";
            $summary .= __('Successfully processed') . ": {$successCount}<br>";
            $summary .= __('Errors') . ": {$errorCount}<br>";

            $msg = __('Merchant item import completed.') . $summary . $log;

            if ($successCount > 0) {
                return back()->with('success', $msg);
            } else {
                return back()->with('unsuccess', $msg);
            }
        } else {
            return back()->with('unsuccess', __('You Can\'t Add More Items. Package limit reached.'));
        }
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
            'brand_quality_id' => 'required|exists:brand_qualities,id',
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
            'brand_quality_id' => $request->brand_quality_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'item_condition' => $request->item_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
            'status' => 1,
        ];

        if ($request->color_check && !empty($request->color_all)) {
            $merchantData['color_all'] = implode(',', $request->color_all);
            if (!empty($request->color_price)) {
                $merchantData['color_price'] = implode(',', $request->color_price);
            }
        }

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
            'brand_quality_id' => 'required|exists:brand_qualities,id',
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
            ->where('brand_quality_id', $request->brand_quality_id)
            ->where('id', '<>', $merchantItem->id)
            ->exists();

        if ($conflict) {
            return redirect()->back()->withErrors(['brand_quality_id' => __('You already have an offer for this catalog item in this branch with this brand quality.')])->withInput();
        }

        $merchantData = [
            'merchant_branch_id' => $request->merchant_branch_id,
            'brand_quality_id' => $request->brand_quality_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'item_condition' => $request->item_condition,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
        ];

        if ($request->color_check && !empty($request->color_all)) {
            $merchantData['color_all'] = implode(',', $request->color_all);
            if (!empty($request->color_price)) {
                $merchantData['color_price'] = implode(',', $request->color_price);
            }
        } else {
            $merchantData['color_all'] = null;
            $merchantData['color_price'] = null;
        }

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

            if ($request->color_check && !empty($request->color_all)) {
                $merchantData['color_all'] = implode(',', $request->color_all);
                if (!empty($request->color_price)) {
                    $merchantData['color_price'] = implode(',', $request->color_price);
                }
            }

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

        return view('merchant.catalog-item.edit.catalog.physical', compact('cats', 'data', 'merchantItem', 'sign'));
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
        unset($input['color_all']);
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

        if (!empty($request->color_all)) {
            $mp['color_all'] = implode(',', (array)$request->color_all);
        }
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
