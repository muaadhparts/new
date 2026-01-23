<?php

namespace App\Http\Controllers\Merchant;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\MerchantBranch;
use App\Models\QualityBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Validator;

class CatalogItemController extends MerchantBaseController
{
    public function index()
    {
        $user = $this->user;

        // MerchantItem is the primary entity - merchant manages their offers
        $merchantItems = MerchantItem::where('user_id', $user->id)
            ->where('item_type', 'normal')
            ->with([
                'catalogItem.fitments.brand',
                'qualityBrand',
                'merchantBranch',
            ])
            ->latest('id')
            ->paginate(10);

        return view('merchant.catalog-item.index', compact('merchantItems'));
    }

    //*** GET Request - Create form
    public function create($slug)
    {
        $user = $this->user;

        if (setting('verify_item') == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                Session::flash('unsuccess', __('You must complete your trust badge first.'));
                return redirect()->route('merchant-trust-badge');
            }
        }

        $sign = $this->curr;

        if ($slug === 'items') {
            return view('merchant.catalog-item.create.items', compact('sign'));
        }

        Session::flash('unsuccess', __('Invalid catalog item type.'));
        return redirect()->route('merchant-catalog-item-types');
    }

    //*** GET Request - SEARCH CATALOG ITEM BY PART_NUMBER (AJAX)
    public function searchItem(Request $request)
    {
        $user = $this->user;
        $part_number = trim($request->input('part_number'));

        if (empty($part_number)) {
            return response()->json([
                'success' => false,
                'message' => __('Please enter a Part Number')
            ]);
        }

        // Search for catalog item by part_number
        $catalogItem = CatalogItem::where('part_number', 'LIKE', '%' . $part_number . '%')->first();

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('Catalog item with part number "') . $part_number . __('" not found.')
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

    //*** POST Request - Store new merchant item
    public function store(Request $request)
    {
        $user = $this->user;

        if (setting('verify_item') == 1) {
            if (!$user->isTrustBadgeTrusted()) {
                return back()->with('unsuccess', __('You must complete your trust badge first.'));
            }
        }

        $rules = [
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
            'quality_brand_id' => 'required|exists:quality_brands,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validate branch belongs to merchant
        $branch = MerchantBranch::where('id', $request->merchant_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$branch) {
            return back()->with('unsuccess', __('Invalid branch selected.'));
        }

        // Check if merchant already has this catalog item in this branch
        $existingItem = MerchantItem::where('catalog_item_id', $request->catalog_item_id)
            ->where('user_id', $user->id)
            ->where('merchant_branch_id', $request->merchant_branch_id)
            ->first();

        if ($existingItem) {
            return redirect()->route('merchant-catalog-item-edit', $existingItem->id)
                ->with('unsuccess', __('You already have an offer for this catalog item in this branch.'));
        }

        $sign = $this->curr;

        $merchantData = [
            'catalog_item_id' => $request->catalog_item_id,
            'user_id' => $user->id,
            'merchant_branch_id' => $request->merchant_branch_id,
            'quality_brand_id' => $request->quality_brand_id,
            'price' => $request->price / $sign->value,
            'previous_price' => $request->previous_price ? ($request->previous_price / $sign->value) : null,
            'stock' => $request->stock,
            'minimum_qty' => $request->minimum_qty ?: null,
            'item_condition' => $request->item_condition ?: 2,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
            'item_type' => $request->item_type ?: 'normal',
            'affiliate_link' => $request->affiliate_link ?: null,
            'status' => $request->status ?? 1,
            'details' => $request->details ?: null,
            'policy' => $request->policy ?: null,
        ];

        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', array_filter($request->whole_sell_qty));
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', array_filter($request->whole_sell_discount));
            }
        }

        MerchantItem::create($merchantData);

        return redirect()->route('merchant-catalog-item-index')
            ->with('success', __('Merchant Item Created Successfully.'));
    }

    //*** GET Request - Edit form
    public function edit($merchantItemId)
    {
        $merchantItem = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $this->user->id)
            ->with('catalogItem')
            ->firstOrFail();

        $data = $merchantItem->catalogItem;
        $sign = $this->curr;

        return view('merchant.catalog-item.edit.items', compact('data', 'merchantItem', 'sign'));
    }

    //*** POST Request - Update merchant item
    public function update(Request $request, $merchantItemId)
    {
        $user = $this->user;
        $sign = $this->curr;

        $merchantItem = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $rules = [
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
            'quality_brand_id' => 'required|exists:quality_brands,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validate branch belongs to merchant
        $branch = MerchantBranch::where('id', $request->merchant_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$branch) {
            return redirect()->back()->withErrors(['merchant_branch_id' => __('Invalid branch selected.')])->withInput();
        }

        // Check for conflict (same item + branch + quality brand combination)
        $conflict = MerchantItem::where('catalog_item_id', $merchantItem->catalog_item_id)
            ->where('user_id', $user->id)
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
            'item_condition' => $request->item_condition ?: 2,
            'ship' => $request->ship ?: null,
            'preordered' => $request->preordered ? 1 : 0,
            'item_type' => $request->item_type ?: 'normal',
            'affiliate_link' => $request->affiliate_link ?: null,
            'status' => $request->status ?? $merchantItem->status,
            'details' => $request->details ?: null,
            'policy' => $request->policy ?: null,
        ];

        if ($request->whole_check && !empty($request->whole_sell_qty)) {
            $merchantData['whole_sell_qty'] = implode(',', array_filter($request->whole_sell_qty));
            if (!empty($request->whole_sell_discount)) {
                $merchantData['whole_sell_discount'] = implode(',', array_filter($request->whole_sell_discount));
            }
        } else {
            $merchantData['whole_sell_qty'] = null;
            $merchantData['whole_sell_discount'] = null;
        }

        $merchantItem->update($merchantData);

        return back()->with('success', __('Merchant Item Updated Successfully.'));
    }

    //*** GET Request - Delete merchant item
    public function destroy($id)
    {
        $user = $this->user;
        $merchantItem = MerchantItem::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($merchantItem) {
            $merchantItem->delete();
            return back()->with('success', __('Merchant Item Deleted Successfully.'));
        }

        return back()->with('unsuccess', __('Merchant Item Not Found.'));
    }
}
