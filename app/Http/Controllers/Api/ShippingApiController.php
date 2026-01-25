<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Models\City;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\TryotoLocationService;
use App\Domain\Shipping\Services\ShippingCalculatorService;
use App\Domain\Commerce\Services\CheckoutPriceService;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * ShippingApiController - Branch-Scoped Shipping API
 *
 * Architecture:
 * - branch_id: Used for cart items, session data, origin location
 * - merchant_id: Used for Tryoto credentials, payment methods (derived from branch)
 */
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
     * Get Tryoto delivery options for a branch
     *
     * @param Request $request - expects branch_id
     */
    public function getTryotoOptions(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');

            Log::info('═══════════════════════════════════════════════════════════');
            Log::info('TRYOTO API REQUEST START', [
                'branch_id' => $branchId,
                'request_url' => $request->fullUrl(),
                'session_id' => Session::getId(),
            ]);

            if (!$branchId) {
                Log::warning('TRYOTO: Missing branch_id');
                return response()->json([
                    'success' => false,
                    'error' => 'معرّف الفرع مطلوب',
                    'error_code' => 'BRANCH_ID_REQUIRED',
                ], 400);
            }

            // Get branch and merchant
            $branch = MerchantBranch::with('user')->find($branchId);
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'error' => 'الفرع غير موجود',
                    'error_code' => 'BRANCH_NOT_FOUND',
                ], 404);
            }

            $merchantId = $branch->user_id;

            Log::info('TRYOTO: Branch and Merchant', [
                'branch_id' => $branchId,
                'branch_name' => $branch->warehouse_name,
                'merchant_id' => $merchantId,
            ]);

            // 1. Get cart items for this branch
            $cartItems = $this->cartManager->getBranchItems($branchId);

            Log::info('TRYOTO STEP 1: Cart Items', [
                'branch_id' => $branchId,
                'cart_items_count' => count($cartItems),
                'cart_keys' => array_keys($cartItems),
            ]);

            if (empty($cartItems)) {
                Log::warning('TRYOTO: Cart is empty', ['branch_id' => $branchId]);
                return response()->json([
                    'success' => false,
                    'error' => 'السلة فارغة لهذا الفرع',
                    'error_code' => 'CART_EMPTY',
                ], 400);
            }

            // 2. Calculate shipping data from cart items
            $shippingData = $this->calculateBranchShippingFromCart($branchId, $cartItems);

            Log::debug('ShippingApiController: Shipping data calculated', [
                'branch_id' => $branchId,
                'shipping_data' => $shippingData
            ]);

            if (!$shippingData['has_complete_data']) {
                $missingFields = $shippingData['missing_data'] ?? [];
                return response()->json([
                    'success' => false,
                    'error' => 'بيانات الشحن غير مكتملة: ' . implode(', ', $missingFields),
                    'missing_data' => $missingFields,
                    'error_code' => 'INCOMPLETE_SHIPPING_DATA',
                ]);
            }

            // 3. Get branch city (origin) - shipping from branch warehouse location
            $branchCityData = ShippingCalculatorService::getBranchCity($branchId);

            Log::info('TRYOTO STEP 2: Branch City (Origin) - Raw Data', [
                'branch_id' => $branchId,
                'branch_city_data' => $branchCityData,
            ]);

            if (!$branchCityData || empty($branchCityData['city_name'])) {
                Log::warning('TRYOTO: Branch city not configured', ['branch_id' => $branchId]);
                return response()->json([
                    'success' => false,
                    'error' => 'لم يتم إعداد موقع الفرع. يرجى التواصل مع التاجر.',
                    'error_code' => 'BRANCH_CITY_NOT_CONFIGURED',
                ]);
            }

            // 3.1 Resolve origin city with fallback (same logic as destination)
            $originCity = $this->resolveOriginCity($branchCityData);

            Log::info('TRYOTO STEP 2.1: Branch City (Origin) - Resolved', [
                'branch_id' => $branchId,
                'original_city' => $branchCityData['city_name'],
                'resolved_city' => $originCity,
            ]);

            if (!$originCity) {
                Log::warning('TRYOTO: Branch city could not be resolved', [
                    'branch_id' => $branchId,
                    'branch_city_data' => $branchCityData,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'عذراً، موقع الفرع غير مدعوم من شركة الشحن. يرجى التواصل مع التاجر.',
                    'error_code' => 'BRANCH_CITY_NOT_SUPPORTED',
                ]);
            }

            // 4. Get destination city from session (branch-scoped session key)
            $addressKey = 'checkout.branch.' . $branchId . '.address';
            $sessionAddress = Session::get($addressKey);

            Log::info('TRYOTO STEP 3: Session Address Data', [
                'branch_id' => $branchId,
                'session_key' => $addressKey,
                'session_exists' => !empty($sessionAddress),
                'session_data' => $sessionAddress ? [
                    'latitude' => $sessionAddress['latitude'] ?? 'N/A',
                    'longitude' => $sessionAddress['longitude'] ?? 'N/A',
                    'customer_city' => $sessionAddress['customer_city'] ?? 'N/A',
                ] : null,
            ]);

            $destinationCity = $this->getDestinationCity($branchId);

            Log::info('TRYOTO STEP 4: Destination City (Customer)', [
                'branch_id' => $branchId,
                'destination_city' => $destinationCity,
            ]);

            if (!$destinationCity) {
                Log::warning('TRYOTO: Destination city missing', [
                    'branch_id' => $branchId,
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
                'branch_id' => $branchId,
                'merchant_id' => $merchantId,
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions,
            ]);

            // 6. Call Tryoto API with merchant credentials (credentials are merchant-level)
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
                    'branch_id' => $branchId,
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
                'branch_id' => $branchId,
                'options_count' => count($deliveryCompany),
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'weight' => $weight,
            ]);

            if (empty($deliveryCompany)) {
                Log::warning('ShippingApiController: No delivery options returned from Tryoto', [
                    'branch_id' => $branchId,
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

            // Get free shipping threshold (merchant-level setting)
            $freeShippingInfo = $this->getFreeShippingInfoFromItems($merchantId, $cartItems);

            // Convert prices and apply free shipping logic
            $convertedOptions = $this->convertDeliveryOptionsPrices($deliveryCompany, $freeShippingInfo);

            // Save resolved city to session
            $this->saveResolvedCity($branchId, $destinationCity);

            return response()->json([
                'success' => true,
                'branch_id' => $branchId,
                'merchant_id' => $merchantId,
                'delivery_options' => $convertedOptions,
                'count' => count($convertedOptions),
                'weight' => $weight,
                'free_shipping' => $freeShippingInfo,
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'resolved_city' => $destinationCity,
                'is_platform_provided' => $freeShippingInfo['is_platform_provided'],
                'owner_user_id' => $freeShippingInfo['owner_user_id'],
                'shipping_id' => $freeShippingInfo['shipping_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('═══════════════════════════════════════════════════════════');
            Log::error('TRYOTO EXCEPTION', [
                'branch_id' => $branchId ?? null,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::error('═══════════════════════════════════════════════════════════');

            $errorMessage = $e->getMessage();

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
     * Get free shipping info for merchant from cart items
     * Note: Free shipping settings are merchant-level
     */
    protected function getFreeShippingInfoFromItems(int $merchantId, array $cartItems): array
    {
        // Get free_above from shippings table for tryoto provider
        $tryotoShipping = \App\Domain\Shipping\Models\Shipping::where('provider', 'tryoto')
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
        $isPlatformProvided = $tryotoShipping && (int)$tryotoShipping->user_id === 0;

        // Calculate items total from cart
        $itemsTotal = 0;
        foreach ($cartItems as $item) {
            $itemsTotal += (float)($item['total_price'] ?? 0);
        }

        $qualifiesFree = $freeAbove > 0 && $itemsTotal >= $freeAbove;

        return [
            'free_above' => round($freeAbove, 2),
            'items_total' => round($itemsTotal, 2),
            'qualifies' => $qualifiesFree,
            'is_platform_provided' => $isPlatformProvided,
            'owner_user_id' => $isPlatformProvided ? 0 : $merchantId,
            'shipping_id' => $tryotoShipping?->id,
        ];
    }

    /**
     * Convert delivery options prices and apply free shipping
     */
    protected function convertDeliveryOptionsPrices(array $options, array $freeShippingInfo): array
    {
        $qualifiesFree = $freeShippingInfo['qualifies'] ?? false;
        $curr = $this->priceService->getMonetaryUnit();
        $currSign = $curr->sign ?? 'SAR';

        return array_map(function ($option) use ($qualifiesFree, $currSign) {
            $originalPrice = (float)($option['price'] ?? 0);
            $convertedOriginalPrice = $this->priceService->convert($originalPrice);

            $isFree = $qualifiesFree;
            $chargeablePrice = $isFree ? 0 : $convertedOriginalPrice;

            $option['original_price'] = round($convertedOriginalPrice, 2);
            $option['chargeable_price'] = round($chargeablePrice, 2);
            $option['is_free'] = $isFree;
            $option['price'] = round($chargeablePrice, 2);

            $option['original_price_display'] = $currSign . number_format($convertedOriginalPrice, 2);
            $option['chargeable_price_display'] = $isFree ? __('Free') : $currSign . number_format($chargeablePrice, 2);

            $option['company_display'] = $option['company'] ?? $option['deliveryCompanyName'] ?? $option['deliveryOptionName'] ?? '';

            $serviceType = $option['serviceType'] ?? '';
            $option['service_type_display'] = $this->formatCamelCaseToReadable($serviceType);

            $deliveryTime = $option['avgDeliveryTime'] ?? $option['estimatedDeliveryDays'] ?? '';
            $option['delivery_time_display'] = $this->formatDeliveryTime($deliveryTime);

            if (isset($option['codCharge'])) {
                $codCharge = $this->priceService->convert((float)$option['codCharge']);
                $option['codCharge'] = round($codCharge, 2);
                $option['cod_charge_display'] = $codCharge > 0 ? $currSign . number_format($codCharge, 2) : '';
            } else {
                $option['cod_charge_display'] = '';
            }

            return $option;
        }, $options);
    }

    /**
     * Format camelCase to readable text
     */
    protected function formatCamelCaseToReadable(string $text): string
    {
        if (empty($text)) return '';
        $formatted = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        $formatted = str_ireplace(['_', '-'], ' ', $formatted);
        return ucwords(strtolower($formatted));
    }

    /**
     * Format delivery time string
     */
    protected function formatDeliveryTime(string $time): string
    {
        if (empty($time)) return '';

        $timeLower = strtolower($time);

        if (str_contains($timeLower, 'same') || $time === '0') {
            return __('Same Day');
        }

        if (str_contains($timeLower, 'next') || $time === '1') {
            return __('Next Day');
        }

        if (preg_match('/(\d+)\s*to\s*(\d+)/i', $time, $matches)) {
            $from = $matches[1];
            $to = $matches[2];
            $isWorkingDays = str_contains($timeLower, 'working');
            $daysText = $isWorkingDays ? __('working days') : __('days');
            return "{$from}-{$to} {$daysText}";
        }

        if (preg_match('/^(\d+)/i', $time, $matches)) {
            $days = $matches[1];
            $isWorkingDays = str_contains($timeLower, 'working');
            $daysText = $isWorkingDays ? __('working days') : __('days');
            return "{$days} {$daysText}";
        }

        return $this->formatCamelCaseToReadable($time);
    }

    /**
     * Resolve destination city from session (branch-scoped)
     */
    protected function getDestinationCity(int $branchId): ?string
    {
        // Branch-scoped session key
        $addressData = Session::get('checkout.branch.' . $branchId . '.address');

        if (!$addressData) {
            Log::warning('ShippingApiController: No address session found', [
                'branch_id' => $branchId,
                'session_key' => 'checkout.branch.' . $branchId . '.address'
            ]);
            return null;
        }

        $latitude = $addressData['latitude'] ?? null;
        $longitude = $addressData['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            Log::warning('ShippingApiController: No coordinates in session', [
                'branch_id' => $branchId
            ]);
            return null;
        }

        $locationService = app(TryotoLocationService::class);

        $cityName = null;
        $stateName = null;
        $countryName = null;
        $geocodingSuccess = false;

        try {
            $googleMapsService = app(\App\Domain\Shipping\Services\GoogleMapsService::class);
            $geocodeResult = $googleMapsService->reverseGeocode((float)$latitude, (float)$longitude, 'en');

            if ($geocodeResult['success'] && !empty($geocodeResult['data'])) {
                $cityName = $geocodeResult['data']['city'] ?? null;
                $stateName = $geocodeResult['data']['state'] ?? null;
                $countryName = $geocodeResult['data']['country'] ?? null;
                $geocodingSuccess = true;

                Log::info('ShippingApiController: Geocoding successful', [
                    'branch_id' => $branchId,
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ShippingApiController: Geocoding exception', [
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
        }

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
                    'branch_id' => $branchId,
                    'resolved_city' => $resolution['resolved_name'],
                    'strategy' => $resolution['strategy']
                ]);
                return $this->normalizeCityName($resolution['resolved_name']);
            }
        }

        $fallbackResolution = $locationService->resolveByCoordinatesOnly(
            (float) $latitude,
            (float) $longitude
        );

        if ($fallbackResolution['success']) {
            return $this->normalizeCityName($fallbackResolution['resolved_name']);
        }

        Log::warning('ShippingApiController: City resolution failed', [
            'branch_id' => $branchId,
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
     * Resolve origin city (branch) with fallback logic
     *
     * Same fallback strategy as destination city:
     * 1. Exact city match in Tryoto DB
     * 2. Fallback to state/region
     * 3. Find nearest supported city using coordinates
     *
     * @param array $branchCityData Data from ShippingCalculatorService::getBranchCity()
     * @return string|null Resolved city name or null if not found
     */
    protected function resolveOriginCity(array $branchCityData): ?string
    {
        $cityName = $branchCityData['city_name'] ?? null;
        $latitude = $branchCityData['latitude'] ?? null;
        $longitude = $branchCityData['longitude'] ?? null;
        $countryId = $branchCityData['country_id'] ?? null;

        if (!$cityName) {
            Log::warning('ShippingApiController: No city name in branch data');
            return null;
        }

        $locationService = app(TryotoLocationService::class);

        // Step 1: Try to resolve by city name first
        // Get country name from country_id
        $countryName = null;
        if ($countryId) {
            $country = \App\Domain\Shipping\Models\Country::find($countryId);
            $countryName = $country?->country_name;
        }

        // Try to resolve using the existing resolveMapCity method
        if ($latitude && $longitude && $countryName) {
            $resolution = $locationService->resolveMapCity(
                $cityName,
                null, // stateName - not available for branches
                $countryName,
                (float) $latitude,
                (float) $longitude
            );

            Log::info('ShippingApiController: Origin city resolution attempt', [
                'city_name' => $cityName,
                'country_name' => $countryName,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'resolution' => $resolution,
            ]);

            if ($resolution['success']) {
                Log::info('ShippingApiController: Origin city resolved via resolveMapCity', [
                    'original_city' => $cityName,
                    'resolved_city' => $resolution['resolved_name'],
                    'strategy' => $resolution['strategy'],
                ]);
                return $this->normalizeCityName($resolution['resolved_name']);
            }
        }

        // Step 2: If no coordinates, try direct city lookup with normalization
        $normalizedCity = $this->normalizeCityName($cityName);

        // Check if the normalized city exists in Tryoto's supported cities
        $city = \App\Domain\Shipping\Models\City::where('city_name', 'LIKE', '%' . $normalizedCity . '%')
            ->where('tryoto_supported', 1)
            ->first();

        if ($city) {
            Log::info('ShippingApiController: Origin city found via direct lookup', [
                'original_city' => $cityName,
                'found_city' => $city->city_name,
            ]);
            return $this->normalizeCityName($city->city_name);
        }

        // Step 3: Try coordinates-only fallback if we have coordinates
        if ($latitude && $longitude) {
            $fallbackResolution = $locationService->resolveByCoordinatesOnly(
                (float) $latitude,
                (float) $longitude
            );

            if ($fallbackResolution['success']) {
                Log::info('ShippingApiController: Origin city resolved via coordinates fallback', [
                    'original_city' => $cityName,
                    'resolved_city' => $fallbackResolution['resolved_name'],
                    'strategy' => 'coordinates_fallback',
                ]);
                return $this->normalizeCityName($fallbackResolution['resolved_name']);
            }
        }

        // Failed to resolve
        Log::warning('ShippingApiController: Origin city resolution failed', [
            'city_name' => $cityName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_id' => $countryId,
        ]);

        return null;
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
     * Save resolved city to session (branch-scoped)
     */
    protected function saveResolvedCity(int $branchId, string $resolvedCity): void
    {
        $addressKey = 'checkout.branch.' . $branchId . '.address';
        $addressData = Session::get($addressKey, []);

        if (!empty($addressData)) {
            $addressData['shipping_city'] = $resolvedCity;
            Session::put($addressKey, $addressData);
            Session::save();

            Log::debug('ShippingApiController: Saved resolved city to session', [
                'branch_id' => $branchId,
                'original_city' => $addressData['customer_city'] ?? 'N/A',
                'shipping_city' => $resolvedCity,
            ]);
        }
    }

    /**
     * Calculate shipping data from cart items (branch-scoped)
     */
    protected function calculateBranchShippingFromCart(int $branchId, array $cartItems): array
    {
        $totalQty = 0;
        $totalWeight = 0;
        $totalPrice = 0;
        $maxLength = 0;
        $maxHeight = 0;
        $maxWidth = 0;

        foreach ($cartItems as $key => $item) {
            if (!isset($item['qty'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: qty");
            }
            if (!isset($item['total_price'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: total_price");
            }

            $qty = (int)$item['qty'];
            $weight = (float)($item['weight'] ?? 0);
            $price = (float)$item['total_price'];

            $length = (float)($item['length'] ?? 0);
            $height = (float)($item['height'] ?? 0);
            $width = (float)($item['width'] ?? 0);

            $totalQty += $qty;
            $totalWeight += ($weight * $qty);
            $totalPrice += $price;

            $maxLength = max($maxLength, $length);
            $maxHeight = max($maxHeight, $height);
            $maxWidth = max($maxWidth, $width);
        }

        if ($totalQty <= 0) {
            throw new \RuntimeException("Cart has no items for branch {$branchId}");
        }

        if ($totalWeight <= 0) {
            throw new \RuntimeException(
                "Cart items have no weight. Please re-add items to cart. Branch: {$branchId}"
            );
        }

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
            'branch_id' => $branchId,
        ];
    }
}
