<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Services\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShippingQuoteController
 *
 * API لحساب تكلفة الشحن
 * يتطلب: merchant_id, branch_id, weight, coordinates
 */
class ShippingQuoteController extends Controller
{
    public function __construct(protected ShippingQuoteService $quoteService)
    {
    }

    /**
     * Full quote - جميع الخيارات
     */
    public function getQuote(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'weight' => 'required|numeric|min:0.01',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return response()->json(
            $this->quoteService->getCatalogItemQuote(
                (int) $request->merchant_id,
                (int) $request->branch_id,
                (float) $request->weight,
                ['latitude' => $request->latitude, 'longitude' => $request->longitude]
            )
        );
    }

    /**
     * Quick estimate - أرخص خيار فقط
     */
    public function quickEstimate(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'weight' => 'required|numeric|min:0.01',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $result = $this->quoteService->getCatalogItemQuote(
            (int) $request->merchant_id,
            (int) $request->branch_id,
            (float) $request->weight,
            ['latitude' => $request->latitude, 'longitude' => $request->longitude]
        );

        if (!$result['success']) {
            return response()->json($result);
        }

        $cheapest = $this->quoteService->getCheapestOption($result);

        return response()->json([
            'success' => true,
            'price' => $cheapest['price'] ?? null,
            'formatted_price' => $cheapest ? number_format($cheapest['price'], 2) . ' ' . __('ر.س') : null,
            'name' => $cheapest['name'] ?? null,
            'estimated_days' => $cheapest['estimated_days'] ?? null,
        ]);
    }

    /**
     * حفظ الموقع
     */
    public function storeLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $result = $this->quoteService->storeCoordinates(
            (float) $request->latitude,
            (float) $request->longitude
        );

        return response()->json([
            'success' => true,
            'resolved_city' => $result['resolved_city'] ?? null,
        ]);
    }

    /**
     * حالة الموقع
     */
    public function getLocationStatus(): JsonResponse
    {
        $coords = $this->quoteService->getStoredCoordinates();

        return response()->json([
            'has_location' => (bool) $coords,
            'latitude' => $coords['latitude'] ?? null,
            'longitude' => $coords['longitude'] ?? null,
            'resolved_city' => $coords['resolved_city'] ?? null,
        ]);
    }

    /**
     * مسح الموقع
     */
    public function clearLocation(): JsonResponse
    {
        $this->quoteService->clearStoredCoordinates();
        return response()->json(['success' => true]);
    }
}
