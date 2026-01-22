<?php

namespace App\Http\Controllers\Operator;

use App\Models\CatalogItem;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogItemImageController extends OperatorBaseController
{
    protected ImageService $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = new ImageService();
    }

    /**
     * Display the catalog item images management page
     */
    public function index()
    {
        return view('operator.catalog-item.images');
    }

    /**
     * Autocomplete search for part numbers (AJAX)
     */
    public function autocomplete(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = CatalogItem::where('part_number', 'LIKE', "%{$query}%")
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
     * Get catalog item details by ID (AJAX)
     */
    public function show($id)
    {
        $catalogItem = CatalogItem::find($id);

        if (!$catalogItem) {
            return response()->json([
                'success' => false,
                'message' => __('CatalogItem not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $catalogItem->id,
                'name' => $catalogItem->localized_name,
                'part_number' => $catalogItem->part_number,
                'photo' => $catalogItem->photo,
                'photo_url' => $catalogItem->photo ? $this->imageService->getUrl($catalogItem->photo) : null,
                'thumbnail' => $catalogItem->thumbnail,
                'thumbnail_url' => $catalogItem->thumbnail ? $this->imageService->getUrl($catalogItem->thumbnail) : null,
            ],
        ]);
    }

    /**
     * Update catalog item images (AJAX)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $catalogItem = CatalogItem::findOrFail($id);

        $updated = [];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($catalogItem->photo) {
                $this->imageService->delete($catalogItem->photo);
            }

            $path = $this->imageService->uploadCatalogImage(
                $request->file('photo'),
                $catalogItem->id,
                $catalogItem->part_number,
                'photo'
            );

            $catalogItem->photo = $path;
            $updated['photo'] = $this->imageService->getUrl($path);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($catalogItem->thumbnail) {
                $this->imageService->delete($catalogItem->thumbnail);
            }

            $path = $this->imageService->uploadCatalogImage(
                $request->file('thumbnail'),
                $catalogItem->id,
                $catalogItem->part_number,
                'thumbnail'
            );

            $catalogItem->thumbnail = $path;
            $updated['thumbnail'] = $this->imageService->getUrl($path);
        }

        // Handle photo deletion
        if ($request->has('delete_photo') && $request->delete_photo == '1') {
            if ($catalogItem->photo) {
                $this->imageService->delete($catalogItem->photo);
            }
            $catalogItem->photo = null;
            $updated['photo'] = null;
        }

        // Handle thumbnail deletion
        if ($request->has('delete_thumbnail') && $request->delete_thumbnail == '1') {
            if ($catalogItem->thumbnail) {
                $this->imageService->delete($catalogItem->thumbnail);
            }
            $catalogItem->thumbnail = null;
            $updated['thumbnail'] = null;
        }

        $catalogItem->save();

        return response()->json([
            'success' => true,
            'message' => __('Images updated successfully'),
            'data' => $updated,
        ]);
    }
}
