<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantPhoto;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Catalog\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantItemImageController extends OperatorBaseController
{
    protected ImageService $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = new ImageService();
    }

    /**
     * Display the merchant item images management page
     */
    public function index()
    {
        return view('operator.merchant-item.images');
    }

    /**
     * Autocomplete search for part numbers that have merchant items (AJAX)
     */
    public function autocomplete(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Only show catalog items that have merchant items
        $items = CatalogItem::where('part_number', 'LIKE', "%{$query}%")
            ->whereHas('merchantItems')
            ->select('id', 'part_number', 'name', 'label_en', 'label_ar')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'part_number' => $item->part_number,
                    'name' => $item->localized_name,
                    'text' => $item->part_number . ' - ' . Str::limit($item->localized_name, 40),
                ];
            });

        return response()->json($items);
    }

    /**
     * Get merchants that have a specific catalog item (by part_number)
     */
    public function getMerchants(Request $request)
    {
        $partNumber = $request->get('part_number');
        $catalogItem = CatalogItem::where('part_number', $partNumber)->first();

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('CatalogItem not found'),
            ], 404);
        }

        $merchants = MerchantItem::where('catalog_item_id', $catalogItem->id)
            ->select('user_id')
            ->distinct()
            ->with('user:id,name,shop_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->user_id,
                    'name' => $item->user ? ($item->user->shop_name ?: $item->user->name) : 'Unknown',
                ];
            });

        return response()->json([
            'success' => true,
            'catalog_item_id' => $catalogItem->id,
            'catalog_item_name' => $catalogItem->localized_name,
            'data' => $merchants,
        ]);
    }

    /**
     * Get branches for a specific merchant and catalog item
     */
    public function getBranches(Request $request)
    {
        $partNumber = $request->get('part_number');
        $merchantId = $request->get('merchant_id');
        $catalogItem = CatalogItem::where('part_number', $partNumber)->first();

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('CatalogItem not found'),
            ], 404);
        }

        $branches = MerchantItem::where('catalog_item_id', $catalogItem->id)
            ->where('user_id', $merchantId)
            ->select('merchant_branch_id')
            ->distinct()
            ->with('merchantBranch:id,branch_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->merchant_branch_id,
                    'name' => $item->merchantBranch ? $item->merchantBranch->branch_name : 'Unknown',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /**
     * Get quality brands for a specific merchant, branch, and catalog item
     */
    public function getQualityBrands(Request $request)
    {
        $partNumber = $request->get('part_number');
        $merchantId = $request->get('merchant_id');
        $branchId = $request->get('branch_id');
        $catalogItem = CatalogItem::where('part_number', $partNumber)->first();

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('CatalogItem not found'),
            ], 404);
        }

        $qualityBrands = MerchantItem::where('catalog_item_id', $catalogItem->id)
            ->where('user_id', $merchantId)
            ->where('merchant_branch_id', $branchId)
            ->select('id', 'quality_brand_id')
            ->with('qualityBrand:id,name_en,name_ar')
            ->get()
            ->map(function ($item) {
                return [
                    'merchant_item_id' => $item->id,
                    'quality_brand_id' => $item->quality_brand_id,
                    'name' => $item->qualityBrand ? $item->qualityBrand->localized_name : __('No Quality Brand'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $qualityBrands,
        ]);
    }

    /**
     * Get photos for a specific merchant item
     */
    public function getPhotos($merchantItemId)
    {
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'merchantBranch', 'qualityBrand'])->find($merchantItemId);

        if (!$merchantItem) {
            return response()->json([
                'success' => false,
                'message' => __('MerchantItem not found'),
            ], 404);
        }

        $photos = MerchantPhoto::where('merchant_item_id', $merchantItemId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'photo' => $photo->photo,
                    'photo_url' => $photo->photo_url,
                    'sort_order' => $photo->sort_order,
                    'is_primary' => $photo->is_primary,
                    'status' => $photo->status,
                ];
            });

        return response()->json([
            'success' => true,
            'merchant_item' => [
                'id' => $merchantItem->id,
                'catalog_item_name' => $merchantItem->catalogItem ? $merchantItem->catalogItem->localized_name : '',
                'merchant_name' => $merchantItem->user ? ($merchantItem->user->shop_name ?: $merchantItem->user->name) : '',
                'branch_name' => $merchantItem->merchantBranch ? $merchantItem->merchantBranch->branch_name : '',
                'quality_brand_name' => $merchantItem->qualityBrand ? $merchantItem->qualityBrand->localized_name : '',
            ],
            'data' => $photos,
        ]);
    }

    /**
     * Store new photos for a merchant item
     */
    public function store(Request $request)
    {
        $request->validate([
            'merchant_item_id' => 'required|exists:merchant_items,id',
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $merchantItemId = $request->merchant_item_id;
        $savedPhotos = [];

        // Get MerchantItem with CatalogItem to get part_number
        $merchantItem = MerchantItem::with('catalogItem')->find($merchantItemId);
        $partNumber = $merchantItem->catalogItem ? $merchantItem->catalogItem->part_number : null;

        // Get max sort_order
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
                    'photo' => $photo->photo,
                    'photo_url' => $this->imageService->getUrl($path),
                    'sort_order' => $photo->sort_order,
                    'is_primary' => $photo->is_primary,
                ];
            }
        }

        // If no primary photo exists, set the first one as primary
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
     * Delete a photo
     */
    public function destroy($id)
    {
        $photo = MerchantPhoto::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => __('Photo not found'),
            ], 404);
        }

        // Delete from storage
        $this->imageService->delete($photo->photo);

        $wasPrimary = $photo->is_primary;
        $merchantItemId = $photo->merchant_item_id;

        $photo->delete();

        // If deleted photo was primary, set another one as primary
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

    /**
     * Update sort order for photos
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:merchant_photos,id',
            'photos.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->photos as $photoData) {
            MerchantPhoto::where('id', $photoData['id'])
                ->update(['sort_order' => $photoData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Order updated successfully'),
        ]);
    }
}
