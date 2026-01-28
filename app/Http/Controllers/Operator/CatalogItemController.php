<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\CatalogItemDeletionService;
use Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Validator;

class CatalogItemController extends OperatorBaseController
{
    public function __construct(
        private CatalogItemDeletionService $deletionService
    ) {
        parent::__construct();
    }

    /**
     * Datatables for catalog items listing
     * Shows catalog item data only (not merchant-specific data)
     */
    public function datatables(Request $request)
    {
        $query = CatalogItem::with(['fitments.brand'])
            ->select('catalog_items.*');

        $datas = $query->latest('id');

        return Datatables::of($datas)
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                      ->orWhere('part_number', 'like', "%{$keyword}%")
                      ->orWhere('label_ar', 'like', "%{$keyword}%")
                      ->orWhere('label_en', 'like', "%{$keyword}%");
            })
            ->addColumn('photo', function (CatalogItem $item) {
                $photo = filter_var($item->photo, FILTER_VALIDATE_URL)
                    ? $item->photo
                    : ($item->photo ? Storage::url($item->photo) : asset('assets/images/noimage.png'));
                return '<img src="' . $photo . '" alt="Image" class="img-thumbnail" style="width:80px">';
            })
            ->addColumn('part_number', function (CatalogItem $item) {
                return '<code>' . ($item->part_number ?? __('N/A')) . '</code>';
            })
            ->addColumn('name', function (CatalogItem $item) {
                $prodLink = $item->part_number
                    ? route('front.part-result', $item->part_number)
                    : '#';

                $displayName = getLocalizedCatalogItemName($item);

                return '<a href="' . $prodLink . '" target="_blank">' . $displayName . '</a>';
            })
            ->addColumn('brand', function (CatalogItem $item) {
                $fitments = $item->fitments ?? collect();
                $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                $count = $brands->count();
                if ($count === 0) return __('N/A');
                if ($count === 1) return getLocalizedBrandName($brands->first());
                return __('Fits') . ' ' . $count . ' ' . __('brands');
            })
            ->addColumn('offers_count', function (CatalogItem $item) {
                $count = $item->merchantItems()->where('status', 1)->count();
                if ($count === 0) {
                    return '<span class="badge badge-secondary">' . __('No Offers') . '</span>';
                }
                return '<span class="badge badge-success">' . $count . ' ' . __('Offers') . '</span>';
            })
            ->addColumn('action', function (CatalogItem $item) {
                $viewUrl = $item->part_number ? route('front.part-result', $item->part_number) : '#';
                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button>
                    <div class="action-list">
                        <a href="' . $viewUrl . '" target="_blank"><i class="fas fa-eye"></i> ' . __("View") . '</a>
                        <a href="' . route('operator-catalog-item-edit', $item->id) . '"><i class="fas fa-edit"></i> ' . __("Edit") . '</a>
                        <a href="javascript:;" data-href="' . route('operator-catalog-item-delete', $item->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a>
                    </div></div>';
            })
            ->rawColumns(['name', 'action', 'photo', 'part_number', 'offers_count'])
            ->toJson();
    }

    public function index()
    {
        return view('operator.catalog-item.index');
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
        ];

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
        ];

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
        // Check if item can be deleted
        $check = $this->deletionService->canDelete($id);

        if (!$check['can_delete']) {
            return response()->json([
                'error' => true,
                'message' => __('Cannot delete this item. It has active merchant offers.')
            ], 422);
        }

        $this->deletionService->delete($id);

        return response()->json(__('CatalogItem Deleted Successfully.'));
    }

    /**
     * Update catalog item settings
     */
    public function settingUpdate(Request $request)
    {
        $input = $request->only(['wholesell', 'page_count', 'favorite_count']);

        foreach ($input as $key => $value) {
            \App\Domain\Platform\Models\PlatformSetting::set('catalog', $key, $value);
        }

        cache()->forget('platform_settings_context');

        return response()->json(__('Data Updated Successfully.'));
    }
}
