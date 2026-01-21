<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantItem;
use App\Models\SkuAlternative;
use App\Models\CatalogItem;
use App\Models\Catalog;
use App\Models\Brand;
use App\Services\AlternativeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogItemApiController extends Controller
{
    protected AlternativeService $alternativeService;

    public function __construct(AlternativeService $alternativeService)
    {
        $this->alternativeService = $alternativeService;
    }

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
        // 1) Get the base PART_NUMBER record
        $skuAlternative = SkuAlternative::where('part_number', $part_number)->first();

        if (!$skuAlternative || !$skuAlternative->group_id) {
            return response()->json([
                'success' => true,
                'part_number' => $part_number,
                'alternatives' => [],
                'count' => 0,
            ]);
        }

        // 2) Get all SKUs in the same group (excluding self)
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

        // 3) Get merchant items for these SKUs
        $listings = MerchantItem::with([
                'catalogItem' => function ($q) {
                    $q->select('id', 'part_number', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_merchant',
            ])
            ->where('status', 1)
            ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
            ->whereHas('catalogItem', fn($q) => $q->whereIn('part_number', $alternativeSkus))
            ->get();

        // 4) Sort: in stock with price > 0 first, then by price ascending
        $sorted = $listings->sortBy(function (MerchantItem $mp) {
            $vp = method_exists($mp, 'merchantSizePrice') ? (float) $mp->merchantSizePrice() : (float) $mp->price;
            $has = ($mp->stock > 0 && $vp > 0) ? 0 : 1;
            return [$has, $vp];
        })->values();

        return response()->json([
            'success' => true,
            'part_number' => $part_number,
            'alternatives' => $sorted,
            'count' => $sorted->count(),
        ]);
    }

    /**
     * Render alternatives partial HTML
     * ✅ محدث: يشمل الصنف الأصلي + كل البدائل التي لها عروض
     */
    public function getAlternativesHtml(Request $request, string $part_number)
    {
        // ✅ دائماً نضمن الصنف الأصلي مع البدائل
        $alternatives = $this->alternativeService->getAlternatives(
            $part_number,
            includeSelf: true,  // ✅ تضمين الصنف نفسه
            returnSelfIfNoAlternatives: true  // ✅ إرجاع الصنف نفسه إذا لم يكن له بدائل
        );

        $html = view('partials.api.alternatives', [
            'alternatives' => $alternatives,
            'part_number' => $part_number,
        ])->render();

        $response = [
            'success' => true,
            'html' => $html,
            'count' => $alternatives->count(),
        ];

        // If only one alternative, return its part_number for direct navigation
        if ($alternatives->count() === 1) {
            $single = $alternatives->first();
            $response['single_part_number'] = $single->part_number ?? null;
        }

        return response()->json($response);
    }

    /**
     * Get fitment details for a catalog item
     * Returns brands and vehicles (catalogs) that this part fits
     */
    public function getFitmentDetails(Request $request, int $catalogItemId)
    {
        // Get fitment records for this catalog item
        $fitments = DB::table('catalog_item_fitments')
            ->where('catalog_item_id', $catalogItemId)
            ->get();

        if ($fitments->isEmpty()) {
            return response()->json([
                'success' => true,
                'brands' => [],
                'html' => view('partials.api.fitment-details', [
                    'brands' => collect(),
                    'catalogItem' => null,
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

        $html = view('partials.api.fitment-details', [
            'brands' => collect($brandData),
            'catalogItem' => $catalogItem,
        ])->render();

        return response()->json([
            'success' => true,
            'brands' => $brandData,
            'html' => $html,
        ]);
    }
}
