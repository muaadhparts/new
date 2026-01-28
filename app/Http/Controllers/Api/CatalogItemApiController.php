<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\SkuAlternative;
use App\Domain\Catalog\Services\AlternativeService;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemDisplayService;
use Illuminate\Http\Request;

class CatalogItemApiController extends Controller
{
    public function __construct(
        private AlternativeService $alternativeService,
        private MerchantItemQuery $itemQuery,
        private MerchantItemDisplayService $displayService,
    ) {}

    /**
     * Get alternatives for a PART_NUMBER
     */
    public function getAlternatives(Request $request, string $part_number)
    {
        $includeSelf = $request->boolean('include_self', false);

        $alternatives = $this->alternativeService->getAlternatives($part_number, $includeSelf);

        return response()->json([
            'success' => true,
            'part_number' => $part_number,
            'alternatives' => $alternatives,
            'count' => $alternatives->count(),
        ]);
    }

    /**
     * Get alternative related catalog items (merchant items for alternatives)
     */
    public function getAlternativeRelatedCatalogItems(Request $request, string $part_number)
    {
        $skuAlternative = SkuAlternative::where('part_number', $part_number)->first();

        if (!$skuAlternative || !$skuAlternative->group_id) {
            return response()->json([
                'success' => true,
                'part_number' => $part_number,
                'alternatives' => [],
                'count' => 0,
            ]);
        }

        $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
            ->where('part_number', '<>', $part_number)
            ->pluck('part_number')
            ->toArray();

        if (empty($alternativeSkus)) {
            return response()->json([
                'success' => true,
                'part_number' => $part_number,
                'alternatives' => [],
                'count' => 0,
            ]);
        }

        $catalogItems = CatalogItem::whereIn('part_number', $alternativeSkus)->get();
        $catalogItemIds = $catalogItems->pluck('id')->toArray();

        $merchantItems = $this->itemQuery::make()
            ->available()
            ->getQuery()
            ->whereIn('catalog_item_id', $catalogItemIds)
            ->with(['catalogItem', 'user', 'qualityBrand'])
            ->get();

        $itemsDisplay = $merchantItems->map(fn($item) => $this->displayService->format($item));

        return response()->json([
            'success' => true,
            'part_number' => $part_number,
            'alternatives' => $itemsDisplay,
            'count' => $itemsDisplay->count(),
        ]);
    }

    /**
     * Get merchant items for catalog item
     */
    public function getMerchantItems(Request $request, $catalogItemId)
    {
        $merchantItems = $this->itemQuery::make()
            ->available()
            ->forCatalogItem($catalogItemId)
            ->withRelations()
            ->cheapestFirst()
            ->get();

        $itemsDisplay = $merchantItems->map(fn($item) => $this->displayService->format($item));

        return response()->json([
            'success' => true,
            'catalog_item_id' => $catalogItemId,
            'items' => $itemsDisplay,
            'count' => $itemsDisplay->count(),
        ]);
    }

    /**
     * Get catalog items by brand
     */
    public function getCatalogItemsByBrand(Request $request, $brandId)
    {
        $brand = Brand::findOrFail($brandId);

        $catalogItems = CatalogItem::whereHas('fitments', function ($q) use ($brandId) {
            $q->where('brand_id', $brandId);
        })->paginate(20);

        return response()->json([
            'success' => true,
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
            ],
            'items' => $catalogItems,
        ]);
    }

    /**
     * Search catalog items
     */
    public function search(Request $request)
    {
        $term = $request->get('q', '');

        if (strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'items' => [],
                'count' => 0,
            ]);
        }

        $catalogItems = CatalogItem::where('part_number', 'like', $term . '%')
            ->orWhere('name', 'like', '%' . $term . '%')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'items' => $catalogItems,
            'count' => $catalogItems->count(),
        ]);
    }
}
