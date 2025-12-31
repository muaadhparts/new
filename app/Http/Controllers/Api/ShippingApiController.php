<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\TryotoService;
use App\Services\TryotoLocationService;
use App\Services\MerchantCartService;
use App\Services\ShippingCalculatorService;
use App\Services\CheckoutPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ShippingApiController extends Controller
{
    protected TryotoService $tryotoService;
    protected CheckoutPriceService $priceService;

    public function __construct(TryotoService $tryotoService, CheckoutPriceService $priceService)
    {
        $this->tryotoService = $tryotoService;
        $this->priceService = $priceService;
    }

    /**
     * Get Tryoto delivery options for a vendor
     */
    public function getTryotoOptions(Request $request)
    {
        try {
            $merchantId = $request->input('merchant_id');

            if (!$merchantId) {
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
            $shippingData = MerchantCartService::calculateVendorShipping($merchantId, $cart->items);

            Log::debug('ShippingApiController: Shipping data calculated', [
                'merchant_id' => $merchantId,
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
            $vendorCityData = ShippingCalculatorService::getVendorCity($merchantId);

            if (!$vendorCityData || empty($vendorCityData['city_name'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vendor city not configured',
                ]);
            }

            $originCity = $this->normalizeCityName($vendorCityData['city_name']);

            // 4. Get destination city from session
            $destinationCity = $this->getDestinationCity($merchantId);

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
                'merchant_id' => $merchantId,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions
            ]);

            // 6. Call Tryoto API with vendor-specific credentials
            $result = $this->tryotoService
                ->forVendor($merchantId)
                ->getDeliveryOptions(
                    $originCity,
                    $destinationCity,
                    $weight,
                    0,
                    $dimensions
                );

            if (!$result['success']) {
                Log::error('ShippingApiController: Failed to get delivery options', [
                    'error' => $result['error'],
                    'merchant_id' => $merchantId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $this->translateTryotoError($result['error'] ?? 'Unknown error'),
                ]);
            }

            $deliveryCompany = $result['raw']['deliveryCompany'] ?? [];

            Log::debug('ShippingApiController: Got delivery options', [
                'merchant_id' => $merchantId,
                'options_count' => count($deliveryCompany),
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight
            ]);

            if (empty($deliveryCompany)) {
                Log::warning('ShippingApiController: No delivery options returned from Tryoto', [
                    'merchant_id' => $merchantId,
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

            // Convert prices to selected currency before returning
            $convertedOptions = $this->convertDeliveryOptionsPrices($deliveryCompany);

            return response()->json([
                'success' => true,
                'merchant_id' => $merchantId,
                'delivery_options' => $convertedOptions,
                'count' => count($convertedOptions),
                'weight' => $weight,
            ]);

        } catch (\Exception $e) {
            Log::error('ShippingApiController: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'merchant_id' => $merchantId ?? null,
            ]);

            // Check if this is a credentials missing error
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'credentials not configured')) {
                return response()->json([
                    'success' => false,
                    'error' => 'عذراً، لم يتم إعداد خدمة الشحن لهذا التاجر بعد. يرجى التواصل مع التاجر.',
                    'error_code' => 'VENDOR_SHIPPING_NOT_CONFIGURED',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $this->translateTryotoError($errorMessage),
            ], 500);
        }
    }

    /**
     * Render Tryoto shipping options HTML
     */
    public function getTryotoHtml(Request $request)
    {
        $merchantId = $request->input('merchant_id');

        // Get currency from CheckoutPriceService (single source of truth)
        $curr = $this->priceService->getCurrency();

        // Get free shipping threshold from vendor's Tryoto config
        $vendorTryotoShipping = \App\Models\Shipping::where('user_id', $merchantId)
            ->where('provider', 'tryoto')
            ->first();
        $freeAbove = $vendorTryotoShipping ? (float)$vendorTryotoShipping->free_above : 0;
        $freeAboveConverted = $this->priceService->convert($freeAbove);

        // Calculate vendor's products total from cart (converted)
        $cart = Session::get('cart');
        $vendorProductsTotal = 0;
        if ($cart && !empty($cart->items)) {
            foreach ($cart->items as $item) {
                $itemVendorId = data_get($item, 'item.user_id') ?? data_get($item, 'item.vendor_user_id') ?? 0;
                if ($itemVendorId == $merchantId) {
                    $vendorProductsTotal += (float)($item['price'] ?? 0);
                }
            }
        }
        $vendorProductsTotalConverted = $this->priceService->convert($vendorProductsTotal);

        // Get the API response (already has converted prices)
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
            'vendorId' => $merchantId,
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
     * Convert delivery options prices to selected currency
     */
    protected function convertDeliveryOptionsPrices(array $options): array
    {
        return array_map(function ($option) {
            // Convert price field (Tryoto returns 'price' in SAR)
            if (isset($option['price'])) {
                $option['price'] = $this->priceService->convert((float)$option['price']);
            }
            // Also convert any other price-related fields if they exist
            if (isset($option['originalPrice'])) {
                $option['originalPrice'] = $this->priceService->convert((float)$option['originalPrice']);
            }
            return $option;
        }, $options);
    }

    /**
     * Resolve destination city from session coordinates.
     * This is the SINGLE SOURCE OF TRUTH for city resolution.
     * Does geocoding here (not in Step 1), with fallback to nearest supported city.
     */
    protected function getDestinationCity(int $merchantId): ?string
    {
        $step1 = Session::get('vendor_step1_' . $merchantId) ?? Session::get('step1');

        if (!$step1) {
            Log::warning('ShippingApiController: No step1 session found', [
                'merchant_id' => $merchantId
            ]);
            return null;
        }

        $latitude = $step1['latitude'] ?? null;
        $longitude = $step1['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            Log::warning('ShippingApiController: No coordinates in session', [
                'merchant_id' => $merchantId
            ]);
            return null;
        }

        $locationService = app(TryotoLocationService::class);

        // Geocode coordinates to get city/country names
        $cityName = null;
        $stateName = null;
        $countryName = null;
        $geocodingSuccess = false;

        try {
            $googleMapsService = app(\App\Services\GoogleMapsService::class);
            $geocodeResult = $googleMapsService->reverseGeocode((float)$latitude, (float)$longitude, 'en');

            if ($geocodeResult['success'] && !empty($geocodeResult['data'])) {
                $cityName = $geocodeResult['data']['city'] ?? null;
                $stateName = $geocodeResult['data']['state'] ?? null;
                $countryName = $geocodeResult['data']['country'] ?? null;
                $geocodingSuccess = true;

                Log::info('ShippingApiController: Geocoding successful', [
                    'merchant_id' => $merchantId,
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                ]);
            } else {
                Log::warning('ShippingApiController: Geocoding failed', [
                    'merchant_id' => $merchantId,
                    'error' => $geocodeResult['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ShippingApiController: Geocoding exception', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);
        }

        // Try resolve by name first
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
                    'merchant_id' => $merchantId,
                    'resolved_city' => $resolution['resolved_name'],
                    'strategy' => $resolution['strategy']
                ]);
                return $this->normalizeCityName($resolution['resolved_name']);
            }

        }

        // Fallback: resolve by coordinates (finds nearest supported city)
        $fallbackResolution = $locationService->resolveByCoordinatesOnly(
            (float) $latitude,
            (float) $longitude
        );

        if ($fallbackResolution['success']) {
            return $this->normalizeCityName($fallbackResolution['resolved_name']);
        }

        Log::warning('ShippingApiController: City resolution failed', [
            'merchant_id' => $merchantId,
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
