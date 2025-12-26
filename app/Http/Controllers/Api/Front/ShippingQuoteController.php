<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Services\ShippingQuoteService;
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
     * Get shipping quote for a product
     */
    public function getQuote(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|integer|exists:users,id',
            'weight' => 'nullable|numeric|min:0.01|max:100',
            'product_id' => 'nullable|integer',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $vendorId = (int) $request->vendor_id;
        $weight = (float) ($request->weight ?? 0.5);
        $cityId = $request->city_id ? (int) $request->city_id : null;

        // If product_id provided, get weight from product
        if ($request->product_id) {
            $product = DB::table('products')
                ->where('id', $request->product_id)
                ->first();

            if ($product && $product->weight > 0) {
                $weight = (float) $product->weight;
            }
        }

        $result = $this->quoteService->getProductQuote($vendorId, $weight, $cityId);

        return response()->json($result);
    }

    /**
     * Get quick estimate (cheapest option only)
     * Used for product cards
     */
    public function quickEstimate(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|integer|exists:users,id',
            'weight' => 'nullable|numeric|min:0.01|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        $vendorId = (int) $request->vendor_id;
        $weight = (float) ($request->weight ?? 0.5);
        $cityId = $request->city_id ? (int) $request->city_id : null;

        $result = $this->quoteService->getProductQuote($vendorId, $weight, $cityId);

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
