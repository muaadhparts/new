<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\TryotoService;
use App\Services\TryotoLocationService;
// MerchantCartService replaced by MerchantCartManager
use App\Services\ShippingCalculatorService;
use App\Services\CheckoutPriceService;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ShippingApiController extends Controller
{
    protected TryotoService $tryotoService;
    protected CheckoutPriceService $priceService;
    protected MerchantCartManager $cartManager;

    public function __construct(TryotoService $tryotoService, CheckoutPriceService $priceService, MerchantCartManager $cartManager)
    {
        $this->tryotoService = $tryotoService;
        $this->priceService = $priceService;
        $this->cartManager = $cartManager;
    }

    /**
     * Get Tryoto delivery options for a merchant
     */
    public function getTryotoOptions(Request $request)
    {
        try {
            $merchantId = $request->input('merchant_id');

            // ========== LOGGING START ==========
            Log::info('═══════════════════════════════════════════════════════════');
            Log::info('TRYOTO API REQUEST START', [
                'merchant_id' => $merchantId,
                'request_url' => $request->fullUrl(),
                'session_id' => Session::getId(),
            ]);
            // ========== LOGGING END ==========

            if (!$merchantId) {
                Log::warning('TRYOTO: Missing merchant_id');
                return response()->json([
                    'success' => false,
                    'error' => 'معرّف التاجر مطلوب',
                    'error_code' => 'MERCHANT_ID_REQUIRED',
                ], 400);
            }

            // 1. Get cart items from MerchantCartManager (NOT from session directly)
            $cartItems = $this->cartManager->getMerchantItems($merchantId);

            Log::info('TRYOTO STEP 1: Cart Items', [
                'merchant_id' => $merchantId,
                'cart_items_count' => count($cartItems),
                'cart_keys' => array_keys($cartItems),
            ]);

            if (empty($cartItems)) {
                Log::warning('TRYOTO: Cart is empty', ['merchant_id' => $merchantId]);
                return response()->json([
                    'success' => false,
                    'error' => 'السلة فارغة لهذا التاجر',
                    'error_code' => 'CART_EMPTY',
                ], 400);
            }

            // 2. Calculate shipping data using cart items
            $shippingData = $this->calculateMerchantShippingFromCart($merchantId, $cartItems);

            Log::debug('ShippingApiController: Shipping data calculated', [
                'merchant_id' => $merchantId,
                'shipping_data' => $shippingData
            ]);

            // 2. Check if data is complete
            if (!$shippingData['has_complete_data']) {
                $missingFields = $shippingData['missing_data'] ?? [];
                return response()->json([
                    'success' => false,
                    'error' => 'بيانات الشحن غير مكتملة: ' . implode(', ', $missingFields),
                    'missing_data' => $missingFields,
                    'error_code' => 'INCOMPLETE_SHIPPING_DATA',
                ]);
            }

            // 3. Get merchant city
            $merchantCityData = ShippingCalculatorService::getMerchantCity($merchantId);

            Log::info('TRYOTO STEP 2: Merchant City (Origin)', [
                'merchant_id' => $merchantId,
                'merchant_city_data' => $merchantCityData,
            ]);

            if (!$merchantCityData || empty($merchantCityData['city_name'])) {
                Log::warning('TRYOTO: Merchant city not configured', ['merchant_id' => $merchantId]);
                return response()->json([
                    'success' => false,
                    'error' => 'لم يتم إعداد موقع التاجر. يرجى التواصل مع التاجر.',
                    'error_code' => 'MERCHANT_CITY_NOT_CONFIGURED',
                ]);
            }

            $originCity = $this->normalizeCityName($merchantCityData['city_name']);

            // 4. Get destination city from session
            // Log session data BEFORE calling getDestinationCity
            $addressKey = 'checkout.merchant.' . $merchantId . '.address';
            $sessionAddress = Session::get($addressKey);
            Log::info('TRYOTO STEP 3: Session Address Data', [
                'merchant_id' => $merchantId,
                'session_key' => $addressKey,
                'session_exists' => !empty($sessionAddress),
                'session_data' => $sessionAddress ? [
                    'latitude' => $sessionAddress['latitude'] ?? 'N/A',
                    'longitude' => $sessionAddress['longitude'] ?? 'N/A',
                    'customer_city' => $sessionAddress['customer_city'] ?? 'N/A',
                ] : null,
            ]);

            $destinationCity = $this->getDestinationCity($merchantId);

            Log::info('TRYOTO STEP 4: Destination City (Customer)', [
                'merchant_id' => $merchantId,
                'destination_city' => $destinationCity,
            ]);

            if (!$destinationCity) {
                Log::warning('TRYOTO: Destination city missing', [
                    'merchant_id' => $merchantId,
                    'session_address' => $sessionAddress,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'عذراً، لم يتم تحديد موقع التوصيل. يرجى العودة لصفحة العنوان وتحديد موقعك على الخريطة.',
                    'error_code' => 'DESTINATION_CITY_MISSING',
                ]);
            }

            // 5. Get chargeable weight
            $weight = $shippingData['total_weight'] ?? $shippingData['chargeable_weight'] ?? $shippingData['actual_weight'] ?? 0;
            $dimensions = $shippingData['dimensions'] ?? [];

            Log::info('TRYOTO STEP 5: Calling Tryoto API', [
                'merchant_id' => $merchantId,
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions,
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
                Log::error('TRYOTO STEP 6: API FAILED', [
                    'merchant_id' => $merchantId,
                    'error' => $result['error'] ?? 'Unknown',
                    'origin_city' => $originCity,
                    'destination_city' => $destinationCity,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $this->translateTryotoError($result['error'] ?? 'Unknown error'),
                ]);
            }

            $deliveryCompany = $result['raw']['deliveryCompany'] ?? [];

            Log::info('TRYOTO STEP 6: API SUCCESS', [
                'merchant_id' => $merchantId,
                'options_count' => count($deliveryCompany),
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'weight' => $weight,
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
            $freeShippingInfo = $this->getFreeShippingInfoFromItems($merchantId, $cartItems);

            // Convert prices and apply free shipping logic
            $convertedOptions = $this->convertDeliveryOptionsPrices($deliveryCompany, $freeShippingInfo);

            // ✅ Save resolved city to session for later use (order creation)
            $this->saveResolvedCity($merchantId, $destinationCity);

            return response()->json([
                'success' => true,
                'merchant_id' => $merchantId,
                'delivery_options' => $convertedOptions,
                'count' => count($convertedOptions),
                'weight' => $weight,
                'free_shipping' => $freeShippingInfo,
                'resolved_city' => $destinationCity,
                // ✅ للحسابات المالية
                'is_platform_provided' => $freeShippingInfo['is_platform_provided'],
                'owner_user_id' => $freeShippingInfo['owner_user_id'],
                'shipping_id' => $freeShippingInfo['shipping_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('═══════════════════════════════════════════════════════════');
            Log::error('TRYOTO EXCEPTION', [
                'merchant_id' => $merchantId ?? null,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::error('═══════════════════════════════════════════════════════════');

            $errorMessage = $e->getMessage();

            // Check specific error types
            if (str_contains($errorMessage, 'credentials not configured')) {
                return response()->json([
                    'success' => false,
                    'error' => 'عذراً، لم يتم إعداد خدمة الشحن لهذا التاجر بعد. يرجى التواصل مع التاجر.',
                    'error_code' => 'MERCHANT_SHIPPING_NOT_CONFIGURED',
                ]);
            }

            if (str_contains($errorMessage, 'no weight')) {
                return response()->json([
                    'success' => false,
                    'error' => 'بيانات السلة قديمة. يرجى حذف المنتجات وإعادة إضافتها للسلة.',
                    'error_code' => 'CART_ITEMS_MISSING_WEIGHT',
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
        $merchantId = (int) $request->input('merchant_id');

        // Get currency from CheckoutPriceService (single source of truth)
        $curr = $this->priceService->getMonetaryUnit();

        // Get free shipping info (includes operator check)
        $cartItems = $this->cartManager->getMerchantItems($merchantId);
        $freeShippingInfo = $this->getFreeShippingInfoFromItems($merchantId, $cartItems);

        $freeAbove = $freeShippingInfo['free_above'];
        $freeAboveConverted = $this->priceService->convert($freeAbove);
        $merchantItemsTotal = $freeShippingInfo['items_total'];
        $merchantItemsTotalConverted = $this->priceService->convert($merchantItemsTotal);

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
            'merchantItemsTotal' => $merchantItemsTotalConverted,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Get free shipping info for merchant from cart items (NEW FORMAT)
     *
     * Note: Tryoto shipping record can be:
     * - user_id = merchantId (merchant's own Tryoto)
     * - user_id = 0, operator = merchantId (platform-provided Tryoto)
     */
    protected function getFreeShippingInfoFromItems(int $merchantId, array $cartItems): array
    {
        // Get free_above from shippings table for tryoto provider
        // Check both merchant's own AND platform-provided
        $tryotoShipping = \App\Models\Shipping::where('provider', 'tryoto')
            ->where('status', 1)
            ->where(function ($q) use ($merchantId) {
                $q->where('user_id', $merchantId)
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->first();

        $freeAbove = $tryotoShipping ? (float)$tryotoShipping->free_above : 0;

        // ✅ تحديد ملكية Tryoto (للحسابات المالية)
        $isPlatformProvided = $tryotoShipping && (int)$tryotoShipping->user_id === 0;

        // Calculate items total from cart
        $itemsTotal = 0;
        foreach ($cartItems as $item) {
            $itemsTotal += (float)($item['total_price'] ?? 0);
        }

        // Free shipping if subtotal MEETS OR EXCEEDS free_above threshold
        $qualifiesFree = $freeAbove > 0 && $itemsTotal >= $freeAbove;

        return [
            'free_above' => round($freeAbove, 2),
            'items_total' => round($itemsTotal, 2),
            'qualifies' => $qualifiesFree,
            // ✅ للحسابات المالية
            'is_platform_provided' => $isPlatformProvided,
            'owner_user_id' => $isPlatformProvided ? 0 : $merchantId,
            'shipping_id' => $tryotoShipping?->id,
        ];
    }

    /**
     * Convert delivery options prices to selected currency and apply free shipping
     * Also format all display values - Frontend should only consume, not process
     */
    protected function convertDeliveryOptionsPrices(array $options, array $freeShippingInfo): array
    {
        $qualifiesFree = $freeShippingInfo['qualifies'] ?? false;
        $curr = $this->priceService->getMonetaryUnit();
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

    /**
     * Save resolved city to session for use when creating purchase
     * This ensures the supported city is used for shipping, not the raw map city
     */
    protected function saveResolvedCity(int $merchantId, string $resolvedCity): void
    {
        // Save to the address session data
        $addressKey = 'checkout.merchant.' . $merchantId . '.address';
        $addressData = Session::get($addressKey, []);

        if (!empty($addressData)) {
            // Add the resolved/supported city for shipping
            $addressData['shipping_city'] = $resolvedCity;
            Session::put($addressKey, $addressData);
            Session::save();

            Log::debug('ShippingApiController: Saved resolved city to session', [
                'merchant_id' => $merchantId,
                'original_city' => $addressData['customer_city'] ?? 'N/A',
                'shipping_city' => $resolvedCity,
            ]);
        }
    }

    /**
     * Calculate shipping data from cart items
     * NEW CART FORMAT ONLY - No fallbacks
     * Replaces MerchantCartService::calculateMerchantShipping
     */
    protected function calculateMerchantShippingFromCart(int $merchantId, array $cartItems): array
    {
        $totalQty = 0;
        $totalWeight = 0;
        $totalPrice = 0;
        $maxLength = 0;
        $maxHeight = 0;
        $maxWidth = 0;

        foreach ($cartItems as $key => $item) {
            // NEW FORMAT ONLY - Required fields
            if (!isset($item['qty'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: qty");
            }
            if (!isset($item['total_price'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: total_price");
            }

            $qty = (int)$item['qty'];
            $weight = (float)($item['weight'] ?? 0);
            $price = (float)$item['total_price'];

            // Track dimensions (use max of all items)
            $length = (float)($item['length'] ?? 0);
            $height = (float)($item['height'] ?? 0);
            $width = (float)($item['width'] ?? 0);

            $totalQty += $qty;
            $totalWeight += ($weight * $qty);
            $totalPrice += $price;

            // Keep max dimensions
            $maxLength = max($maxLength, $length);
            $maxHeight = max($maxHeight, $height);
            $maxWidth = max($maxWidth, $width);
        }

        if ($totalQty <= 0) {
            throw new \RuntimeException("Cart has no items for merchant {$merchantId}");
        }

        // NO FALLBACK - Weight must come from cart explicitly
        if ($totalWeight <= 0) {
            throw new \RuntimeException(
                "Cart items have no weight. Please re-add items to cart. Merchant: {$merchantId}"
            );
        }

        // Build dimensions array only if we have real dimensions
        $dimensions = [];
        if ($maxLength > 0 && $maxHeight > 0 && $maxWidth > 0) {
            $dimensions = [
                'length' => round($maxLength, 2),
                'height' => round($maxHeight, 2),
                'width' => round($maxWidth, 2),
            ];
        }

        return [
            'has_complete_data' => true,
            'missing_data' => [],
            'total_qty' => $totalQty,
            'total_weight' => round($totalWeight, 2),
            'total_price' => round($totalPrice, 2),
            'dimensions' => $dimensions,
            'merchant_id' => $merchantId,
        ];
    }
}
