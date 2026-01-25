<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Services\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ShippingQuoteController
 *
 * API for shipping quote calculations
 * Quote only - no shipment creation
 */
class ShippingQuoteController extends Controller
{
    protected ShippingQuoteService $quoteService;

    public function __construct(ShippingQuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    /**
     * Get shipping quote for a catalogItem
     */
    public function getQuote(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|exists:users,id',
            'weight' => 'nullable|numeric|min:0.01|max:100',
            'catalog_item_id' => 'nullable|integer',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $merchantId = (int) $request->merchant_id;
        $weight = (float) ($request->weight ?? 0.5);
        $cityId = $request->city_id ? (int) $request->city_id : null;

        // If catalog_item_id provided, get weight from catalogItem
        if ($request->catalog_item_id) {
            $catalogItem = DB::table('catalog_items')
                ->where('id', $request->catalog_item_id)
                ->first();

            if ($catalogItem && $catalogItem->weight > 0) {
                $weight = (float) $catalogItem->weight;
            }
        }

        $result = $this->quoteService->getCatalogItemQuote($merchantId, $weight, $cityId);

        return response()->json($result);
    }

    /**
     * Get quick estimate (cheapest option only)
     * Used for catalogItem cards
     */
    public function quickEstimate(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|exists:users,id',
            'weight' => 'nullable|numeric|min:0.01|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $merchantId = (int) $request->merchant_id;
        $weight = (float) ($request->weight ?? 0.5);
        $cityId = $request->city_id ? (int) $request->city_id : null;

        $result = $this->quoteService->getCatalogItemQuote($merchantId, $weight, $cityId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'requires_location' => $result['requires_location'] ?? false,
                'message' => $result['message'] ?? __('غير متوفر'),
            ]);
        }

        $cheapest = $this->quoteService->getCheapestOption($result);

        return response()->json([
            'success' => true,
            'price' => $cheapest ? $cheapest['price'] : null,
            'formatted_price' => $cheapest ? number_format($cheapest['price'], 2) . ' ' . __('ر.س') : null,
            'name' => $cheapest ? $cheapest['name'] : null,
            'estimated_days' => $cheapest ? $cheapest['estimated_days'] : null,
        ]);
    }
}
