<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemService;
use App\Domain\Merchant\Services\MerchantItemDisplayService;
use App\Domain\Merchant\Services\MerchantItemDuplicateCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Validator;

class CatalogItemController extends MerchantBaseController
{
    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemService $itemService,
        private MerchantItemDisplayService $displayService,
        private MerchantItemDuplicateCheckService $duplicateChecker
    ) {
        parent::__construct();
    }

    /**
     * Display merchant items list
     */
    public function index()
    {
        $user = $this->user;

        $merchantItems = $this->itemQuery::make()
            ->forMerchant($user->id)
            ->active()
            ->withRelations()
            ->latest()
            ->paginate(10);

        $merchantItemsDisplay = collect($merchantItems->items())
            ->map(fn($item) => $this->displayService->formatForDashboard($item))
            ->toArray();

        return view('merchant.catalog-item.index', [
            'merchantItems' => $merchantItems,
            'merchantItemsDisplay' => $merchantItemsDisplay,
        ]);
    }

    /**
     * Show create form
     */
    public function create($slug)
    {
        $user = $this->user;

        $catalogItem = CatalogItem::where('slug', $slug)->firstOrFail();

        $branches = MerchantBranch::where('user_id', $user->id)
            ->where('status', 1)
            ->get();

        $qualityBrands = QualityBrand::all();

        return view('merchant.catalog-item.create', compact(
            'catalogItem',
            'branches',
            'qualityBrands'
        ));
    }

    /**
     * Search catalog items
     */
    public function searchItem(Request $request)
    {
        $term = $request->get('term', '');

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $items = CatalogItem::where('part_number', 'like', $term . '%')
            ->orWhere('name', 'like', '%' . $term . '%')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'text' => $item->part_number . ' - ' . $item->name,
                'part_number' => $item->part_number,
            ]);

        return response()->json($items);
    }

    /**
     * Toggle item status
     */
    public function status($id, $status)
    {
        $user = $this->user;

        $item = $this->itemQuery::make()
            ->forMerchant($user->id)
            ->getQuery()
            ->findOrFail($id);

        $item->update(['status' => $status]);

        Session::flash('success', __('Status updated successfully'));
        return redirect()->back();
    }

    /**
     * Store new merchant item
     */
    public function store(Request $request)
    {
        $user = $this->user;

        $validator = Validator::make($request->all(), [
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'merchant_branch_id' => 'nullable|exists:merchant_branches,id',
            'quality_brand_id' => 'nullable|exists:quality_brands,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'item_condition' => 'required|integer|in:1,2,3,4',
            'details' => 'nullable|string|max:1000',
            'policy' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for duplicates
        $isDuplicate = $this->duplicateChecker->isDuplicate(
            $user->id,
            $request->catalog_item_id,
            $request->merchant_branch_id,
            $request->quality_brand_id
        );

        if ($isDuplicate) {
            Session::flash('error', __('This item already exists in your inventory'));
            return redirect()->back()->withInput();
        }

        $this->itemService->createMerchantItem($user->id, $request->all());

        Session::flash('success', __('Item added successfully'));
        return redirect()->route('merchant-catalog-item');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = $this->user;

        $item = $this->itemQuery::make()
            ->forMerchant($user->id)
            ->withRelations()
            ->getQuery()
            ->findOrFail($id);

        $branches = MerchantBranch::where('user_id', $user->id)
            ->where('status', 1)
            ->get();

        $qualityBrands = QualityBrand::all();

        $itemDisplay = $this->displayService->format($item);

        return view('merchant.catalog-item.edit', compact(
            'item',
            'itemDisplay',
            'branches',
            'qualityBrands'
        ));
    }

    /**
     * Update merchant item
     */
    public function update(Request $request, $id)
    {
        $user = $this->user;

        $item = $this->itemQuery::make()
            ->forMerchant($user->id)
            ->getQuery()
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'merchant_branch_id' => 'nullable|exists:merchant_branches,id',
            'quality_brand_id' => 'nullable|exists:quality_brands,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'item_condition' => 'required|integer|in:1,2,3,4',
            'details' => 'nullable|string|max:1000',
            'policy' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->itemService->updateMerchantItem($item, $request->all());

        Session::flash('success', __('Item updated successfully'));
        return redirect()->route('merchant-catalog-item');
    }

    /**
     * Delete merchant item
     */
    public function destroy($id)
    {
        $user = $this->user;

        $item = $this->itemQuery::make()
            ->forMerchant($user->id)
            ->getQuery()
            ->findOrFail($id);

        $this->itemService->deleteMerchantItem($item);

        Session::flash('success', __('Item deleted successfully'));
        return redirect()->route('merchant-catalog-item');
    }
}
