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

    /**
     * Get fitment details for a catalog item
     */
    public function getFitmentDetails(Request $request, int $catalogItemId)
    {
        // Get fitment records for this catalog item
        $fitments = \DB::table('catalog_item_fitments')
            ->where('catalog_item_id', $catalogItemId)
            ->get();

        if ($fitments->isEmpty()) {
            return response()->json([
                'success' => true,
                'brands' => [],
                'html' => view('partials.api.fitment-details', [
                    'brands' => collect(),
                    'catalogItem' => null,
                    'brandCount' => 0,
                    'totalVehicles' => 0,
                    'hasMultipleBrands' => false,
                    'uniqueId' => 'fitment_' . uniqid(),
                ])->render(),
            ]);
        }

        // Get catalog item info
        $catalogItem = CatalogItem::find($catalogItemId);

        // Group by brand_id
        $brandIds = $fitments->pluck('brand_id')->unique();
        $catalogIds = $fitments->pluck('catalog_id')->unique();

        // Get brands
        $brands = Brand::whereIn('id', $brandIds)
            ->select('id', 'name', 'slug', 'photo')
            ->get()
            ->keyBy('id');

        // Get catalogs (vehicles)
        $catalogs = Catalog::whereIn('id', $catalogIds)
            ->with('brand:id,name,slug,photo')
            ->select('id', 'brand_id', 'name', 'name_ar', 'code', 'beginDate', 'endDate')
            ->get();

        // Group catalogs by brand
        $brandData = [];
        foreach ($fitments as $fitment) {
            $brandId = $fitment->brand_id;
            $catalogId = $fitment->catalog_id;

            if (!isset($brandData[$brandId])) {
                $brand = $brands->get($brandId);
                if (!$brand) continue; // Skip if brand not found

                $brandData[$brandId] = [
                    'id' => $brandId,
                    'name' => $brand->name ?? 'Unknown',
                    'slug' => $brand->slug ?? '',
                    'logo' => $brand->photo_url ?? null,
                    'vehicles' => [],
                ];
            }

            $catalog = $catalogs->firstWhere('id', $catalogId);
            if ($catalog && isset($brandData[$brandId])) {
                $brandData[$brandId]['vehicles'][] = [
                    'id' => $catalog->id,
                    'name' => $catalog->name,
                    'name_ar' => $catalog->name_ar,
                    'code' => $catalog->code,
                    'begin_date' => $catalog->beginDate,
                    'end_date' => $catalog->endDate,
                ];
            }
        }

        // Reset array keys to 0, 1, 2...
        $brandData = array_values($brandData);

        // Pre-compute statistics
        $isArabic = str_starts_with(app()->getLocale(), 'ar');
        $brandCount = count($brandData);
        $totalVehicles = array_reduce($brandData, fn($sum, $b) => $sum + count($b['vehicles'] ?? []), 0);

        // Pre-compute localized names and formatted years for each vehicle
        foreach ($brandData as &$brand) {
            foreach ($brand['vehicles'] as &$vehicle) {
                $vehicle['localized_name'] = $isArabic
                    ? ($vehicle['name_ar'] ?? $vehicle['name'] ?? '—')
                    : ($vehicle['name'] ?? $vehicle['name_ar'] ?? '—');
                $vehicle['formatted_begin'] = empty($vehicle['begin_date']) ? '—' : (substr((string)$vehicle['begin_date'], 0, 4) ?: '—');
                $vehicle['formatted_end'] = empty($vehicle['end_date']) ? '—' : (substr((string)$vehicle['end_date'], 0, 4) ?: '—');
            }
        }
        unset($brand, $vehicle);

        $html = view('partials.api.fitment-details', [
            'brands' => collect($brandData),
            'catalogItem' => $catalogItem,
            'brandCount' => $brandCount,
            'totalVehicles' => $totalVehicles,
            'hasMultipleBrands' => $brandCount > 1,
            'uniqueId' => 'fitment_' . uniqid(),
        ])->render();

        return response()->json([
            'success' => true,
            'brands' => $brandData,
            'html' => $html,
        ]);
    }
}
