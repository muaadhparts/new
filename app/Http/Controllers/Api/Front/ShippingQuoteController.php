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
 *
 * REQUIRES browser geolocation coordinates - same as checkout flow
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
     *
     * REQUIRES coordinates from browser geolocation
     */
    public function getQuote(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|exists:users,id',
            'branch_id' => 'required|integer|exists:merchant_branches,id',
            'weight' => 'nullable|numeric|min:0.01|max:1000',
            'catalog_item_id' => 'nullable|integer',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $merchantId = (int) $request->merchant_id;
        $branchId = (int) $request->branch_id;
        $weight = null;

        // Weight MUST come from catalog_item - NO DEFAULT
        if ($request->catalog_item_id) {
            $catalogItem = DB::table('catalog_items')
                ->where('id', $request->catalog_item_id)
                ->first();

            if ($catalogItem && $catalogItem->weight > 0) {
                $weight = (float) $catalogItem->weight;
            }
        }

        // If weight provided directly, use it
        if ($request->has('weight') && $request->weight > 0) {
            $weight = (float) $request->weight;
        }

        // NO FALLBACK - weight is required
        if (!$weight || $weight <= 0) {
            return response()->json([
                'success' => false,
                'error_code' => 'WEIGHT_REQUIRED',
                'message' => __('وزن المنتج غير محدد. لا يمكن حساب الشحن بدون وزن حقيقي.'),
                'message_en' => 'Product weight is not set. Cannot calculate shipping without real weight.',
            ]);
        }

        $coordinates = [
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
        ];

        $result = $this->quoteService->getCatalogItemQuote($merchantId, $branchId, $weight, $coordinates);

        return response()->json($result);
    }

    /**
     * Get quick estimate (cheapest option only)
     * Used for catalogItem cards
     *
     * REQUIRES coordinates from browser geolocation
     */
    public function quickEstimate(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|exists:users,id',
            'branch_id' => 'required|integer|exists:merchant_branches,id',
            'weight' => 'required|numeric|min:0.01|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $merchantId = (int) $request->merchant_id;
        $branchId = (int) $request->branch_id;
        $weight = (float) $request->weight;

        // NO FALLBACK - all data must be real
        if ($weight <= 0) {
            return response()->json([
                'success' => false,
                'error_code' => 'WEIGHT_REQUIRED',
                'message' => __('وزن المنتج غير محدد'),
                'message_en' => 'Product weight is not set',
            ]);
        }

        $coordinates = [
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
        ];

        $result = $this->quoteService->getCatalogItemQuote($merchantId, $branchId, $weight, $coordinates);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'requires_location' => $result['requires_location'] ?? false,
                'location_type' => $result['location_type'] ?? 'coordinates',
                'message' => $result['message'] ?? __('غير متوفر'),
                'message_en' => $result['message_en'] ?? 'Not available',
            ]);
        }

        $cheapest = $this->quoteService->getCheapestOption($result);

        return response()->json([
            'success' => true,
            'price' => $cheapest ? $cheapest['price'] : null,
            'formatted_price' => $cheapest ? number_format($cheapest['price'], 2) . ' ' . __('ر.س') : null,
            'name' => $cheapest ? $cheapest['name'] : null,
            'estimated_days' => $cheapest ? $cheapest['estimated_days'] : null,
            'origin' => $result['origin'] ?? null,
            'destination' => $result['destination'] ?? null,
        ]);
    }

    /**
     * Store user's location from browser geolocation
     * Called when user grants location permission
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
            'message' => $result['resolved_city']
                ? __('تم تحديد موقعك: :city', ['city' => $result['resolved_city']])
                : __('تم حفظ موقعك'),
        ]);
    }

    /**
     * Get stored location status
     */
    public function getLocationStatus(): JsonResponse
    {
        $coords = $this->quoteService->getStoredCoordinates();

        if (!$coords) {
            return response()->json([
                'has_location' => false,
                'requires_location' => true,
                'message' => __('يرجى تفعيل خدمة الموقع في المتصفح'),
                'message_en' => 'Please enable location services in your browser',
            ]);
        }

        return response()->json([
            'has_location' => true,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'resolved_city' => $coords['resolved_city'] ?? null,
        ]);
    }

    /**
     * Clear stored location
     */
    public function clearLocation(): JsonResponse
    {
        $this->quoteService->clearStoredCoordinates();

        return response()->json([
            'success' => true,
            'message' => __('تم مسح موقعك'),
        ]);
    }
}
