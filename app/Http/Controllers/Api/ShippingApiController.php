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
     * Get Tryoto delivery options for a merchant
     */
    public function getTryotoOptions(Request $request)
    {
        try {
            $merchantId = $request->input('merchant_id');

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Merchant ID is required',
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
            $shippingData = MerchantCartService::calculateMerchantShipping($merchantId, $cart->items);

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

            // 3. Get merchant city
            $merchantCityData = ShippingCalculatorService::getMerchantCity($merchantId);

            if (!$merchantCityData || empty($merchantCityData['city_name'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Merchant city not configured',
                ]);
            }

            $originCity = $this->normalizeCityName($merchantCityData['city_name']);

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

            // 6. Call Tryoto API with merchant-specific credentials
            $result = $this->tryotoService
                ->forMerchant($merchantId)
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

            // Get free shipping threshold from merchant's Tryoto config
            $freeShippingInfo = $this->getFreeShippingInfo($merchantId, $cart);

            // Convert prices and apply free shipping logic
            $convertedOptions = $this->convertDeliveryOptionsPrices($deliveryCompany, $freeShippingInfo);

            return response()->json([
                'success' => true,
                'merchant_id' => $merchantId,
                'delivery_options' => $convertedOptions,
                'count' => count($convertedOptions),
                'weight' => $weight,
                'free_shipping' => $freeShippingInfo,
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
                    'error_code' => 'MERCHANT_SHIPPING_NOT_CONFIGURED',
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

        // Get free shipping threshold from merchant's Tryoto config
        $merchantTryotoShipping = \App\Models\Shipping::where('user_id', $merchantId)
            ->where('provider', 'tryoto')
            ->first();
        $freeAbove = $merchantTryotoShipping ? (float)$merchantTryotoShipping->free_above : 0;
        $freeAboveConverted = $this->priceService->convert($freeAbove);

        // Calculate merchant's catalogItems total from cart (converted)
        $cart = Session::get('cart');
        $merchantCatalogitemsTotal = 0;
        if ($cart && !empty($cart->items)) {
            foreach ($cart->items as $item) {
                $itemMerchantId = data_get($item, 'item.user_id') ?? data_get($item, 'item.merchant_user_id') ?? 0;
                if ($itemMerchantId == $merchantId) {
                    $merchantCatalogitemsTotal += (float)($item['price'] ?? 0);
                }
            }
        }
        $merchantCatalogitemsTotalConverted = $this->priceService->convert($merchantCatalogitemsTotal);

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
            'merchantId' => $merchantId,
            'weight' => $data['weight'],
            'curr' => $curr,
            'freeAbove' => $freeAboveConverted,
            'merchantItemsTotal' => $merchantCatalogitemsTotalConverted,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Get free shipping info for merchant
     */
    protected function getFreeShippingInfo(int $merchantId, $cart): array
    {
        // Get free_above from shippings table for tryoto provider
        $merchantTryotoShipping = \App\Models\Shipping::where('user_id', $merchantId)
            ->where('provider', 'tryoto')
            ->first();

        $freeAbove = $merchantTryotoShipping ? (float)$merchantTryotoShipping->free_above : 0;

        // Calculate merchant's items total from cart (price * qty)
        $itemsTotal = 0;
        if ($cart && !empty($cart->items)) {
            foreach ($cart->items as $item) {
                $itemMerchantId = data_get($item, 'item.user_id') ?? data_get($item, 'item.merchant_user_id') ?? data_get($item, 'user_id') ?? 0;
                if ($itemMerchantId == $merchantId) {
                    $qty = (int)($item['qty'] ?? 1);
                    $price = (float)($item['price'] ?? 0);
                    $itemsTotal += ($price * $qty);
                }
            }
        }

        // Free shipping if subtotal is BELOW free_above threshold
        $qualifiesFree = $freeAbove > 0 && $itemsTotal < $freeAbove;

        return [
            'free_above' => round($freeAbove, 2),
            'items_total' => round($itemsTotal, 2),
            'qualifies' => $qualifiesFree,
        ];
    }

    /**
     * Convert delivery options prices to selected currency and apply free shipping
     * Also format all display values - Frontend should only consume, not process
     */
    protected function convertDeliveryOptionsPrices(array $options, array $freeShippingInfo): array
    {
        $qualifiesFree = $freeShippingInfo['qualifies'] ?? false;
        $curr = $this->priceService->getCurrency();
        $currSign = $curr->sign ?? 'SAR';

        return array_map(function ($option) use ($qualifiesFree, $currSign) {
            // Original price from Tryoto (in SAR)
            $originalPrice = (float)($option['price'] ?? 0);

            // Convert to selected currency
            $convertedOriginalPrice = $this->priceService->convert($originalPrice);

            // Apply free shipping logic
            $isFree = $qualifiesFree;
            $chargeablePrice = $isFree ? 0 : $convertedOriginalPrice;

            // === PRICES (raw values for calculations) ===
            $option['original_price'] = round($convertedOriginalPrice, 2);
            $option['chargeable_price'] = round($chargeablePrice, 2);
            $option['is_free'] = $isFree;
            $option['price'] = round($chargeablePrice, 2);

            // === DISPLAY VALUES (ready for frontend - no processing needed) ===
            $option['original_price_display'] = $currSign . number_format($convertedOriginalPrice, 2);
            $option['chargeable_price_display'] = $isFree ? __('Free') : $currSign . number_format($chargeablePrice, 2);

            // Company name - use what Tryoto sends
            $option['company_display'] = $option['company'] ?? $option['deliveryCompanyName'] ?? $option['deliveryOptionName'] ?? '';

            // Service type - dynamic formatting (camelCase to readable)
            $serviceType = $option['serviceType'] ?? '';
            $option['service_type_display'] = $this->formatCamelCaseToReadable($serviceType);

            // Delivery time - dynamic formatting
            $deliveryTime = $option['avgDeliveryTime'] ?? $option['estimatedDeliveryDays'] ?? '';
            $option['delivery_time_display'] = $this->formatDeliveryTime($deliveryTime);

            // COD charge
            if (isset($option['codCharge'])) {
                $codCharge = $this->priceService->convert((float)$option['codCharge']);
                $option['codCharge'] = round($codCharge, 2);
                $option['cod_charge_display'] = $codCharge > 0
                    ? $currSign . number_format($codCharge, 2)
                    : '';
            } else {
                $option['cod_charge_display'] = '';
            }

            return $option;
        }, $options);
    }

    /**
     * Format camelCase or special strings to readable text
     * Fully dynamic - no hardcoded mappings
     */
    protected function formatCamelCaseToReadable(string $text): string
    {
        if (empty($text)) return '';

        // Add space before capital letters (camelCase to words)
        $formatted = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        // Handle common patterns
        $formatted = str_ireplace(['_', '-'], ' ', $formatted);

        // Capitalize first letter of each word
        return ucwords(strtolower($formatted));
    }

    /**
     * Format delivery time string to readable format
     * Fully dynamic - parses any format from shipping company
     */
    protected function formatDeliveryTime(string $time): string
    {
        if (empty($time)) return '';

        $timeLower = strtolower($time);

        // Same day
        if (str_contains($timeLower, 'same') || $time === '0') {
            return __('Same Day');
        }

        // Next day
        if (str_contains($timeLower, 'next') || $time === '1') {
            return __('Next Day');
        }

        // Pattern: "1to3WorkingDays" or "2to5Days" etc
        if (preg_match('/(\d+)\s*to\s*(\d+)/i', $time, $matches)) {
            $from = $matches[1];
            $to = $matches[2];

            // Check if it mentions working days
            $isWorkingDays = str_contains($timeLower, 'working');
            $daysText = $isWorkingDays ? __('working days') : __('days');

            return "{$from}-{$to} {$daysText}";
        }

        // Single number pattern: "3Days" or "5WorkingDays"
        if (preg_match('/^(\d+)/i', $time, $matches)) {
            $days = $matches[1];
            $isWorkingDays = str_contains($timeLower, 'working');
            $daysText = $isWorkingDays ? __('working days') : __('days');

            return "{$days} {$daysText}";
        }

        // If no pattern matched, return formatted camelCase
        return $this->formatCamelCaseToReadable($time);
    }

    /**
     * Resolve destination city from session coordinates.
     * This is the SINGLE SOURCE OF TRUTH for city resolution.
     * Does geocoding here (not in Step 1), with fallback to nearest supported city.
     */
    protected function getDestinationCity(int $merchantId): ?string
    {
        // Try new merchant checkout session structure first
        $addressData = Session::get('checkout.merchant.' . $merchantId . '.address');

        // Fallback to old session structure
        if (!$addressData) {
            $addressData = Session::get('merchant_step1_' . $merchantId) ?? Session::get('step1');
        }

        if (!$addressData) {
            Log::warning('ShippingApiController: No address session found', [
                'merchant_id' => $merchantId
            ]);
            return null;
        }

        $latitude = $addressData['latitude'] ?? null;
        $longitude = $addressData['longitude'] ?? null;

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
