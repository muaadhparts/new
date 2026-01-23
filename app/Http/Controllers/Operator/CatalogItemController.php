<?php

namespace App\Http\Controllers\Operator;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

class CatalogItemController extends OperatorBaseController
{
    /**
     * Datatables for merchant items listing (shows merchant offers)
     */
    public function datatables(Request $request)
    {
        $query = MerchantItem::with(['catalogItem.fitments.brand', 'user', 'qualityBrand', 'merchantBranch'])
            ->where('item_type', 'normal');

        if ($request->type == 'deactive') {
            $query->where('status', 0);
        }

        $datas = $query->latest('id');

        return Datatables::of($datas)
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
            ->addColumn('part_number', function (MerchantItem $mp) {
                return '<code>' . ($mp->catalogItem?->part_number ?? __('N/A')) . '</code>';
            })
            ->addColumn('name', function (MerchantItem $mp) {
                $catalogItem = $mp->catalogItem;
                if (!$catalogItem) return __('N/A');

                $prodLink = $catalogItem->part_number
                    ? route('front.part-result', $catalogItem->part_number)
                    : '#';

                $displayName = getLocalizedCatalogItemName($catalogItem);
                $condition = $mp->item_condition == 1 ? ' <span class="badge badge-warning">' . __('Used') . '</span>' : '';

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>' . $condition;
            })
            ->addColumn('brand', function (MerchantItem $mp) {
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
            ->addColumn('branch', function (MerchantItem $mp) {
                return $mp->merchantBranch?->warehouse_name ?? __('N/A');
            })
            ->addColumn('price', function (MerchantItem $mp) {
                $priceWithCommission = $mp->merchantSizePrice();
                return \PriceHelper::showAdminCurrencyPrice($priceWithCommission * $this->curr->value);
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
                        <a href="' . route('operator-catalog-item-edit', $catalogItem->id) . '"><i class="fas fa-edit"></i> ' . __("Edit CatalogItem") . '</a>
                        <a href="javascript:;" data-href="' . route('operator-catalog-item-delete', $catalogItem->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete CatalogItem") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'stock', 'status', 'action', 'photo', 'merchant', 'part_number'])
            ->toJson();
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

    /**
     * Show create form for catalog item
     */
    public function create()
    {
        return view('operator.catalog-item.create.items');
    }

    /**
     * Update catalog item status
     */
    public function status($id1, $id2)
    {
        $data = CatalogItem::findOrFail($id1);
        $data->status = $id2;
        $data->update();

        return response()->json(__('Status Updated Successfully.'));
    }

    /**
     * Update merchant item status (for activating/deactivating merchant offers)
     */
    public function merchantItemStatus($id, $status)
    {
        $merchantItem = MerchantItem::findOrFail($id);
        $merchantItem->status = $status;
        $merchantItem->update();

        return response()->json(__('Status Updated Successfully.'));
    }

    /**
     * Store new catalog item
     * Only saves to catalog_items table (merchant pricing handled separately)
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'part_number' => 'required|min:8|unique:catalog_items',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $input = [
            'name' => $request->input('name'),
            'part_number' => $request->input('part_number'),
            'label_en' => $request->input('label_en'),
            'label_ar' => $request->input('label_ar'),
            'weight' => $request->input('weight', 1.00),
            'youtube' => $request->input('youtube'),
            'measure' => $request->input('measure'),
        ];

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        }

        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', $request->meta_tag);
            }
            $input['meta_description'] = $request->input('meta_description');
        }

        $data = CatalogItem::create($input);

        $data->slug = Str::slug($data->name, '-') . '-' . strtolower($data->part_number);
        $data->save();

        $msg = __("New CatalogItem Added Successfully.") . '<a href="' . route('operator-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
    }

    /**
     * Show edit form for catalog item
     */
    public function edit($catalogItemId)
    {
        $data = CatalogItem::findOrFail($catalogItemId);
        return view('operator.catalog-item.edit.items', compact('data'));
    }

    /**
     * Update catalog item
     * Only updates catalog_items table (merchant pricing handled separately)
     */
    public function update(Request $request, $catalogItemId)
    {
        $data = CatalogItem::findOrFail($catalogItemId);

        $rules = [
            'name' => 'required|string|max:255',
            'part_number' => 'required|min:8|unique:catalog_items,part_number,' . $data->id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $input = [
            'name' => $request->input('name'),
            'part_number' => $request->input('part_number'),
            'label_en' => $request->input('label_en'),
            'label_ar' => $request->input('label_ar'),
            'weight' => $request->input('weight', $data->weight ?? 1.00),
            'youtube' => $request->input('youtube'),
            'measure' => $request->input('measure'),
        ];

        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        } else {
            $input['tags'] = null;
        }

        if (empty($request->seo_check)) {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        } else {
            if (!empty($request->meta_tag)) {
                $input['meta_tag'] = implode(',', $request->meta_tag);
            }
            $input['meta_description'] = $request->input('meta_description');
        }

        $input['slug'] = Str::slug($request->input('name'), '-') . '-' . strtolower($request->input('part_number'));

        $data->update($input);

        $msg = __("CatalogItem Updated Successfully.") . '<a href="' . route('operator-catalog-item-index') . '">' . __("View CatalogItem Lists.") . '</a>';
        return response()->json($msg);
    }

    /**
     * Delete catalog item and related data
     */
    public function destroy($id)
    {
        $data = CatalogItem::findOrFail($id);

        // Delete related merchant photos
        if ($data->merchantPhotos->count() > 0) {
            foreach ($data->merchantPhotos as $photo) {
                if (file_exists(public_path() . '/assets/images/merchant-photos/' . $photo->photo)) {
                    unlink(public_path() . '/assets/images/merchant-photos/' . $photo->photo);
                }
                $photo->delete();
            }
        }

        // Delete related flags
        if ($data->abuseFlags->count() > 0) {
            foreach ($data->abuseFlags as $gal) {
                $gal->delete();
            }
        }

        // Delete related reviews
        if ($data->catalogReviews->count() > 0) {
            foreach ($data->catalogReviews as $gal) {
                $gal->delete();
            }
        }

        // Delete favorites
        if ($data->favorites->count() > 0) {
            foreach ($data->favorites as $gal) {
                $gal->delete();
            }
        }

        // Delete clicks
        if ($data->clicks->count() > 0) {
            foreach ($data->clicks as $gal) {
                $gal->delete();
            }
        }

        // Delete buyer notes and replies
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

        // Delete photo files
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

        return response()->json(__('CatalogItem Deleted Successfully.'));
    }

    /**
     * Update catalog item settings
     */
    public function settingUpdate(Request $request)
    {
        $input = $request->only(['wholesell', 'page_count', 'favorite_count']);

        foreach ($input as $key => $value) {
            \App\Models\PlatformSetting::set('catalog', $key, $value);
        }

        cache()->forget('platform_settings_context');

        return response()->json(__('Data Updated Successfully.'));
    }
}
