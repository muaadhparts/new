<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantProduct;
use App\Models\SkuAlternative;
use App\Services\AlternativeService;
use App\Services\CompatibilityService;
use Illuminate\Http\Request;

class ProductApiController extends Controller
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
     * Get alternatives for a SKU
     */
    public function getAlternatives(Request $request, string $sku)
    {
        $includeSelf = $request->boolean('include_self', false);

        $alternatives = $this->alternativeService->getAlternatives($sku, $includeSelf);

        return response()->json([
            'success' => true,
            'sku' => $sku,
            'alternatives' => $alternatives,
            'count' => $alternatives->count(),
        ]);
    }

    /**
     * Get alternative related products (merchant products for alternatives)
     */
    public function getAlternativeRelatedProducts(Request $request, string $sku)
    {
        // 1) Get the base SKU record
        $skuAlternative = SkuAlternative::where('sku', $sku)->first();

        if (!$skuAlternative || !$skuAlternative->group_id) {
            return response()->json([
                'success' => true,
                'sku' => $sku,
                'alternatives' => [],
                'count' => 0,
            ]);
        }

        // 2) Get all SKUs in the same group (excluding self)
        $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
            ->where('sku', '<>', $sku)
            ->pluck('sku')
            ->toArray();

        if (empty($alternativeSkus)) {
            return response()->json([
                'success' => true,
                'sku' => $sku,
                'alternatives' => [],
                'count' => 0,
            ]);
        }

        // 3) Get merchant products for these SKUs
        $listings = MerchantProduct::with([
                'product' => function ($q) {
                    $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_vendor',
            ])
            ->where('status', 1)
            ->whereHas('user', fn($u) => $u->where('is_vendor', 2))
            ->whereHas('product', fn($q) => $q->whereIn('sku', $alternativeSkus))
            ->get();

        // 4) Sort: in stock with price > 0 first, then by price ascending
        $sorted = $listings->sortBy(function (MerchantProduct $mp) {
            $vp = method_exists($mp, 'vendorSizePrice') ? (float) $mp->vendorSizePrice() : (float) $mp->price;
            $has = ($mp->stock > 0 && $vp > 0) ? 0 : 1;
            return [$has, $vp];
        })->values();

        return response()->json([
            'success' => true,
            'sku' => $sku,
            'alternatives' => $sorted,
            'count' => $sorted->count(),
        ]);
    }

    /**
     * Get compatibility (catalogs) for a SKU
     */
    public function getCompatibility(Request $request, string $sku)
    {
        $results = $this->compatibilityService->getCompatibleCatalogs($sku);

        return response()->json([
            'success' => true,
            'sku' => $sku,
            'catalogs' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Render alternatives partial HTML
     */
    public function getAlternativesHtml(Request $request, string $sku)
    {
        $includeSelf = $request->boolean('include_self', false);
        $alternatives = $this->alternativeService->getAlternatives($sku, $includeSelf);

        $html = view('partials.api.alternatives', [
            'alternatives' => $alternatives,
            'sku' => $sku,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Render compatibility partial HTML
     */
    public function getCompatibilityHtml(Request $request, string $sku)
    {
        $displayMode = $request->get('display_mode', 'tabs');
        $results = $this->compatibilityService->getCompatibleCatalogs($sku);

        $viewName = $displayMode === 'tabs'
            ? 'partials.api.compatibility-tabs'
            : 'partials.api.compatibility';

        $html = view($viewName, [
            'results' => $results,
            'sku' => $sku,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
