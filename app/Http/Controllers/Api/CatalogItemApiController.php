<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantItem;
use App\Models\SkuAlternative;
use App\Services\AlternativeService;
use App\Services\CompatibilityService;
use Illuminate\Http\Request;

class CatalogItemApiController extends Controller
{
    protected AlternativeService $alternativeService;
    protected CompatibilityService $compatibilityService;

    public function __construct(
        AlternativeService $alternativeService,
        CompatibilityService $compatibilityService
    ) {
        $this->alternativeService = $alternativeService;
        $this->compatibilityService = $compatibilityService;
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
     * Get compatibility (catalogs) for a PART_NUMBER
     */
    public function getCompatibility(Request $request, string $part_number)
    {
        $results = $this->compatibilityService->getCompatibleCatalogs($part_number);

        return response()->json([
            'success' => true,
            'part_number' => $part_number,
            'catalogs' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Render alternatives partial HTML
     * إذا القطعة ما لها بدائل، نعرض القطعة نفسها حتى يقدر المستخدم يشوف العروض
     */
    public function getAlternativesHtml(Request $request, string $part_number)
    {
        $includeSelf = $request->boolean('include_self', false);

        // جلب البدائل مع إرجاع القطعة نفسها إذا ما لها بدائل
        $alternatives = $this->alternativeService->getAlternatives(
            $part_number,
            includeSelf: $includeSelf,
            returnSelfIfNoAlternatives: true
        );

        $html = view('partials.api.alternatives', [
            'alternatives' => $alternatives,
            'part_number' => $part_number,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Render compatibility partial HTML
     */
    public function getCompatibilityHtml(Request $request, string $part_number)
    {
        $displayMode = $request->get('display_mode', 'tabs');
        $results = $this->compatibilityService->getCompatibleCatalogs($part_number);

        $viewName = $displayMode === 'tabs'
            ? 'partials.api.compatibility-tabs'
            : 'partials.api.compatibility';

        $html = view($viewName, [
            'results' => $results,
            'part_number' => $part_number,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
