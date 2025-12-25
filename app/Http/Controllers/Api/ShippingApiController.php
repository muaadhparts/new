<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\TryotoService;
use App\Services\TryotoLocationService;
use App\Services\VendorCartService;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ShippingApiController extends Controller
{
    protected TryotoService $tryotoService;

    public function __construct(TryotoService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
    }

    /**
     * Get Tryoto delivery options for a vendor
     */
    public function getTryotoOptions(Request $request)
    {
        try {
            $vendorId = $request->input('vendor_id');

            if (!$vendorId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vendor ID is required',
                ], 400);
            }

            // 1. Get cart items from session (NOT from request)
            $cart = Session::get('cart');
            if (!$cart || empty($cart->items)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cart is empty',
                ], 400);
            }

            // 2. Calculate shipping data using cart items
            $shippingData = VendorCartService::calculateVendorShipping($vendorId, $cart->items);

            Log::debug('ShippingApiController: Shipping data calculated', [
                'vendor_id' => $vendorId,
                'shipping_data' => $shippingData
            ]);

            // 2. Check if data is complete
            if (!$shippingData['has_complete_data']) {
                $missingFields = $shippingData['missing_data'] ?? [];
                return response()->json([
                    'success' => false,
                    'error' => 'Incomplete shipping data: ' . implode(', ', $missingFields),
                    'missing_data' => $missingFields,
                ]);
            }

            // 3. Get vendor city
            $vendorCityData = ShippingCalculatorService::getVendorCity($vendorId);

            if (!$vendorCityData || empty($vendorCityData['city_name'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vendor city not configured',
                ]);
            }

            $originCity = $this->normalizeCityName($vendorCityData['city_name']);

            // 4. Get destination city from session
            $destinationCity = $this->getDestinationCity($vendorId);

            if (!$destinationCity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer destination city is required',
                ]);
            }

            // 5. Get chargeable weight
            $weight = $shippingData['chargeable_weight'] ?? $shippingData['actual_weight'] ?? 0;
            $dimensions = $shippingData['dimensions'] ?? [];

            Log::debug('ShippingApiController: Calling Tryoto API', [
                'vendor_id' => $vendorId,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions
            ]);

            // 6. Call Tryoto API
            $result = $this->tryotoService->getDeliveryOptions(
                $originCity,
                $destinationCity,
                $weight,
                0,
                $dimensions
            );

            if (!$result['success']) {
                Log::error('ShippingApiController: Failed to get delivery options', [
                    'error' => $result['error'],
                    'vendor_id' => $vendorId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $this->translateTryotoError($result['error'] ?? 'Unknown error'),
                ]);
            }

            $deliveryCompany = $result['raw']['deliveryCompany'] ?? [];

            Log::debug('ShippingApiController: Got delivery options', [
                'vendor_id' => $vendorId,
                'options_count' => count($deliveryCompany),
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight
            ]);

            // ✅ إذا لم تكن هناك خيارات شحن، نرجع رسالة خطأ واضحة
            if (empty($deliveryCompany)) {
                Log::warning('ShippingApiController: No delivery options returned from Tryoto', [
                    'vendor_id' => $vendorId,
                    'origin' => $originCity,
                    'destination' => $destinationCity,
                    'weight' => $weight,
                    'raw_response' => $result['raw'] ?? null
                ]);

                return response()->json([
                    'success' => false,
                    'error' => "عذراً، لا تتوفر خيارات شحن من ({$originCity}) إلى ({$destinationCity}). يرجى التواصل مع التاجر.",
                    'debug' => [
                        'origin' => $originCity,
                        'destination' => $destinationCity,
                        'weight' => $weight
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'vendor_id' => $vendorId,
                'delivery_options' => $deliveryCompany,
                'count' => count($deliveryCompany),
                'weight' => $weight,
            ]);

        } catch (\Exception $e) {
            Log::error('ShippingApiController: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Shipping service is currently unavailable',
            ], 500);
        }
    }

    /**
     * Render Tryoto shipping options HTML
     */
    public function getTryotoHtml(Request $request)
    {
        $vendorId = $request->input('vendor_id');

        // Get currency - same logic as AppServiceProvider
        if (Session::has('currency')) {
            $curr = \App\Models\Currency::find(Session::get('currency'));
        } else {
            $curr = \App\Models\Currency::where('is_default', '=', 1)->first();
        }

        // ✅ Get free_above from vendor's Tryoto shipping entry
        $vendorTryotoShipping = \App\Models\Shipping::where('user_id', $vendorId)
            ->where('provider', 'tryoto')
            ->first();
        $freeAbove = $vendorTryotoShipping ? (float)$vendorTryotoShipping->free_above : 0;
        $freeAboveConverted = round($freeAbove * $curr->value, 2);

        // ✅ Calculate vendor's products total from cart
        $cart = Session::get('cart');
        $vendorProductsTotal = 0;
        if ($cart && !empty($cart->items)) {
            foreach ($cart->items as $item) {
                $itemVendorId = data_get($item, 'item.user_id') ?? data_get($item, 'item.vendor_user_id') ?? 0;
                if ($itemVendorId == $vendorId) {
                    $vendorProductsTotal += (float)($item['price'] ?? 0);
                }
            }
        }
        $vendorProductsTotalConverted = round($vendorProductsTotal * $curr->value, 2);

        // Get the API response (uses session cart internally)
        $apiResponse = $this->getTryotoOptions($request);
        $data = json_decode($apiResponse->getContent(), true);

        if (!$data['success']) {
            $html = view('partials.api.tryoto-error', [
                'error' => $data['error'] ?? 'Unknown error',
                'curr' => $curr,
            ])->render();

            return response()->json([
                'success' => false,
                'html' => $html,
                'error' => $data['error'],
            ]);
        }

        $html = view('partials.api.tryoto-options', [
            'deliveryCompany' => $data['delivery_options'],
            'vendorId' => $vendorId,
            'weight' => $data['weight'],
            'curr' => $curr,
            'freeAbove' => $freeAboveConverted,
            'vendorProductsTotal' => $vendorProductsTotalConverted,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * ========================================================================
     * Step 2: Resolve Destination City - Business Logic
     * ========================================================================
     *
     * مسؤوليات Step 2:
     * ✅ قراءة Session فقط (لا يعدل Session)
     * ✅ تحديد مدينة الشحن
     * ✅ Fallback لأقرب مدينة إذا فشل الـ geocoding
     * ✅ معالجة أخطاء الشحن
     *
     * Session Contract (ما يتوقعه من Step 1):
     * - latitude, longitude (مطلوبين دائماً)
     * - city_name, state_name, country_name (قد تكون null إذا فشل geocoding)
     * - geocoding_success (للتحقق من حالة الـ geocoding)
     * ========================================================================
     */
    protected function getDestinationCity(int $vendorId): ?string
    {
        // ====================================================================
        // 1. قراءة Session (READ-ONLY)
        // ====================================================================
        $step1 = Session::get('vendor_step1_' . $vendorId) ?? Session::get('step1');

        if (!$step1) {
            Log::warning('ShippingApiController: No step1 session found', [
                'vendor_id' => $vendorId
            ]);
            return null;
        }

        // ====================================================================
        // 2. استخراج الإحداثيات (مطلوبة دائماً)
        // ====================================================================
        $latitude = $step1['latitude'] ?? null;
        $longitude = $step1['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            Log::warning('ShippingApiController: No coordinates in session', [
                'vendor_id' => $vendorId
            ]);
            return null;
        }

        // ====================================================================
        // 3. استخراج بيانات الـ Geocoding (قد تكون null)
        // ====================================================================
        $cityName = $step1['city_name'] ?? null;
        $stateName = $step1['state_name'] ?? null;
        $countryName = $step1['country_name'] ?? null;
        $geocodingSuccess = $step1['geocoding_success'] ?? false;

        Log::debug('ShippingApiController: Starting city resolution', [
            'vendor_id' => $vendorId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city_name' => $cityName,
            'country_name' => $countryName,
            'geocoding_success' => $geocodingSuccess
        ]);

        $locationService = app(TryotoLocationService::class);

        // ====================================================================
        // 4. محاولة Resolve بالأسماء أولاً (إذا نجح الـ geocoding)
        // ====================================================================
        if ($geocodingSuccess && $countryName) {
            $resolution = $locationService->resolveMapCity(
                $cityName ?? '',
                $stateName,
                $countryName,
                (float) $latitude,
                (float) $longitude
            );

            if ($resolution['success']) {
                Log::info('ShippingApiController: City resolved by name', [
                    'vendor_id' => $vendorId,
                    'resolved_city' => $resolution['resolved_name'],
                    'strategy' => $resolution['strategy']
                ]);
                return $this->normalizeCityName($resolution['resolved_name']);
            }
        }

        // ====================================================================
        // 5. FALLBACK: Resolve بالإحداثيات فقط
        // ====================================================================
        // يستخدم عندما:
        // - فشل الـ geocoding في Step 1
        // - أو المدينة/الدولة غير مدعومة
        // ====================================================================
        Log::info('ShippingApiController: Falling back to coordinates-only resolution', [
            'vendor_id' => $vendorId,
            'reason' => $geocodingSuccess ? 'name_resolution_failed' : 'geocoding_failed'
        ]);

        $fallbackResolution = $locationService->resolveByCoordinatesOnly(
            (float) $latitude,
            (float) $longitude
        );

        if ($fallbackResolution['success']) {
            Log::info('ShippingApiController: City resolved by coordinates fallback', [
                'vendor_id' => $vendorId,
                'resolved_city' => $fallbackResolution['resolved_name'],
                'distance_km' => $fallbackResolution['distance_km'] ?? 0
            ]);
            return $this->normalizeCityName($fallbackResolution['resolved_name']);
        }

        // ====================================================================
        // 6. فشل كامل - لا توجد مدن مدعومة
        // ====================================================================
        Log::warning('ShippingApiController: All resolution strategies failed', [
            'vendor_id' => $vendorId,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);

        return null;
    }

    /**
     * Normalize city name for Tryoto API
     */
    protected function normalizeCityName(string $cityName): string
    {
        $charsToReplace = ['ā', 'ī', 'ū', 'ē', 'ō', 'Ā', 'Ī', 'Ū', 'Ē', 'Ō'];
        $replacements = ['a', 'i', 'u', 'e', 'o', 'A', 'I', 'U', 'E', 'O'];

        $normalized = str_replace($charsToReplace, $replacements, $cityName);
        $normalized = str_replace("'", '', $normalized);

        return trim($normalized);
    }

    /**
     * Translate Tryoto errors to Arabic
     */
    protected function translateTryotoError(string $error): string
    {
        if (str_contains($error, 'could not be found on database')) {
            preg_match('/Given city (.+) could not be found/', $error, $matches);
            $cityName = $matches[1] ?? '';
            return "عذراً، مدينة المرسل ({$cityName}) غير مدعومة حالياً في خدمة الشحن الذكي.";
        }

        if (str_contains($error, 'destination')) {
            return 'عذراً، مدينة التوصيل غير مدعومة في خدمة الشحن الذكي.';
        }

        return 'عذراً، خدمة الشحن الذكي غير متاحة حالياً.';
    }
}
