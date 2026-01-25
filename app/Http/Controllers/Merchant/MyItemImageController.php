<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantPhoto;
use App\Domain\Catalog\Services\ImageService;
use Illuminate\Http\Request;

class MyItemImageController extends MerchantBaseController
{
    protected ImageService $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = new ImageService();
    }

    /**
     * Display the merchant's items list with photo count
     */
    public function index()
    {
        return view('merchant.my-item.images');
    }

    /**
     * Get merchant items via DataTables (AJAX)
     */
    public function datatables(Request $request)
    {
        $user = $this->user;

        $query = MerchantItem::where('user_id', $user->id)
            ->with(['catalogItem', 'merchantBranch', 'qualityBrand'])
            ->withCount('photos');

        // Part Number Search (custom search box)
        if ($request->has('part_number_search') && $request->part_number_search) {
            $partSearch = $request->part_number_search;
            $query->whereHas('catalogItem', function ($q) use ($partSearch) {
                $q->where('part_number', 'like', "%{$partSearch}%");
            });
        }

        // DataTables default search
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->whereHas('catalogItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%");
            });
        }

        $totalRecords = MerchantItem::where('user_id', $user->id)->count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'part_number' => $item->catalogItem ? $item->catalogItem->part_number : '',
                'name' => $item->catalogItem ? $item->catalogItem->localized_name : '',
                'branch' => $item->merchantBranch ? $item->merchantBranch->branch_name : '',
                'quality_brand' => $item->qualityBrand ? $item->qualityBrand->localized_name : '',
                'photos_count' => $item->photos_count ?? 0,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get photos for a specific merchant item (AJAX)
     */
    public function show($id)
    {
        $user = $this->user;

        $merchantItem = MerchantItem::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['catalogItem', 'merchantBranch', 'qualityBrand'])
            ->first();

        if (!$merchantItem) {
            return response()->json([
                'success' => false,
                'message' => __('Item not found'),
            ], 404);
        }

        $photos = MerchantPhoto::where('merchant_item_id', $id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'photo' => $photo->photo,
                    'photo_url' => $photo->photo_url,
                    'sort_order' => $photo->sort_order,
                    'is_primary' => $photo->is_primary,
                ];
            });

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $merchantItem->id,
                'name' => $merchantItem->catalogItem ? $merchantItem->catalogItem->localized_name : '',
                'part_number' => $merchantItem->catalogItem ? $merchantItem->catalogItem->part_number : '',
                'branch' => $merchantItem->merchantBranch ? $merchantItem->merchantBranch->branch_name : '',
                'quality_brand' => $merchantItem->qualityBrand ? $merchantItem->qualityBrand->localized_name : '',
            ],
            'photos' => $photos,
        ]);
    }

    /**
     * Store new photos for a merchant item
     */
    public function store(Request $request)
    {
        $request->validate([
            'merchant_item_id' => 'required|integer',
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $this->user;
        $merchantItemId = $request->merchant_item_id;

        // Verify ownership and get part_number
        $merchantItem = MerchantItem::where('id', $merchantItemId)
            ->where('user_id', $user->id)
            ->with('catalogItem')
            ->first();

        if (!$merchantItem) {
            return response()->json([
                'success' => false,
                'message' => __('Item not found or you do not have permission'),
            ], 403);
        }

        $partNumber = $merchantItem->catalogItem ? $merchantItem->catalogItem->part_number : null;
        $savedPhotos = [];
        $maxOrder = MerchantPhoto::where('merchant_item_id', $merchantItemId)->max('sort_order') ?? 0;

        if ($files = $request->file('photos')) {
            foreach ($files as $file) {
                $path = $this->imageService->uploadMerchantPhoto($file, $merchantItemId, $partNumber);

                $maxOrder++;
                $photo = MerchantPhoto::create([
                    'merchant_item_id' => $merchantItemId,
                    'photo' => $path,
                    'sort_order' => $maxOrder,
                    'is_primary' => false,
                    'status' => 1,
                ]);

                $savedPhotos[] = [
                    'id' => $photo->id,
                    'photo_url' => $this->imageService->getUrl($path),
                    'sort_order' => $photo->sort_order,
                    'is_primary' => $photo->is_primary,
                ];
            }
        }

        // Set first photo as primary if none exists
        $hasPrimary = MerchantPhoto::where('merchant_item_id', $merchantItemId)
            ->where('is_primary', true)
            ->exists();

        if (!$hasPrimary && count($savedPhotos) > 0) {
            MerchantPhoto::where('id', $savedPhotos[0]['id'])->update(['is_primary' => true]);
            $savedPhotos[0]['is_primary'] = true;
        }

        return response()->json([
            'success' => true,
            'message' => __('Photos uploaded successfully'),
            'data' => $savedPhotos,
        ]);
    }

    /**
     * Update photo (set as primary, update order)
     */
    public function update(Request $request, $id)
    {
        $user = $this->user;

        $photo = MerchantPhoto::with('merchantItem')->find($id);

        if (!$photo || !$photo->merchantItem || $photo->merchantItem->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('Photo not found or you do not have permission'),
            ], 403);
        }

        // Handle set as primary
        if ($request->has('is_primary') && $request->is_primary) {
            // Remove primary from others
            MerchantPhoto::where('merchant_item_id', $photo->merchant_item_id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);

            $photo->is_primary = true;
            $photo->save();
        }

        // Handle sort order update
        if ($request->has('sort_order')) {
            $photo->sort_order = $request->sort_order;
            $photo->save();
        }

        // Handle batch order update
        if ($request->has('photos') && is_array($request->photos)) {
            foreach ($request->photos as $photoData) {
                // Verify ownership
                $p = MerchantPhoto::with('merchantItem')->find($photoData['id']);
                if ($p && $p->merchantItem && $p->merchantItem->user_id == $user->id) {
                    $p->sort_order = $photoData['sort_order'];
                    $p->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Photo updated successfully'),
        ]);
    }

    /**
     * Delete a photo
     */
    public function destroy($id)
    {
        $user = $this->user;

        $photo = MerchantPhoto::with('merchantItem')->find($id);

        if (!$photo || !$photo->merchantItem || $photo->merchantItem->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('Photo not found or you do not have permission'),
            ], 403);
        }

        // Delete from storage
        $this->imageService->delete($photo->photo);

        $wasPrimary = $photo->is_primary;
        $merchantItemId = $photo->merchant_item_id;

        $photo->delete();

        // If deleted was primary, set another one
        if ($wasPrimary) {
            $nextPhoto = MerchantPhoto::where('merchant_item_id', $merchantItemId)
                ->orderBy('sort_order')
                ->first();

            if ($nextPhoto) {
                $nextPhoto->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Photo deleted successfully'),
        ]);
    }
}
