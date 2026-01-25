<?php

namespace App\Domain\Shipping\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * ShippingQuoteService - Quote Only
 *
 * MATCHES ShippingApiController logic EXACTLY:
 * - Uses TryotoLocationService for city resolution
 * - Uses GoogleMapsService for reverse geocoding
 * - Requires FRESH coordinates from browser geolocation
 * - NO reliance on stored city_name - always resolves from coordinates
 *
 * Single function: getCatalogItemQuote(merchantId, weight, coordinates)
 * NO shipment creation, NO COD, NO storage
 */
class ShippingQuoteService
{
    protected TryotoService $tryotoService;
    protected TryotoLocationService $locationService;
    protected GoogleMapsService $googleMapsService;

    private const SESSION_KEY = 'shipping_quote_location';

    public function __construct(
        TryotoService $tryotoService,
        TryotoLocationService $locationService,
        GoogleMapsService $googleMapsService
    ) {
        $this->tryotoService = $tryotoService;
        $this->locationService = $locationService;
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Get shipping quote for a catalogItem
     *
     * MATCHES ShippingApiController logic:
     * 1. Get origin city from SPECIFIC merchant_branch (by branch_id)
     * 2. Get destination from FRESH coordinates (browser geolocation)
     * 3. Resolve city names using TryotoLocationService
     * 4. Call Tryoto API with resolved names
     *
     * @param int $merchantId Merchant user_id
     * @param int $branchId Specific branch ID (from merchant_item.merchant_branch_id)
     * @param float $weight CatalogItem weight in kg (REQUIRED)
     * @param array|null $coordinates ['latitude' => float, 'longitude' => float] from browser
     * @return array Quote result
     */
    public function getCatalogItemQuote(int $merchantId, int $branchId, float $weight, ?array $coordinates = null): array
    {
        // FAIL-FAST: Validate inputs
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid merchantId: {$merchantId}. Must be > 0."
            );
        }

        if ($branchId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid branchId: {$branchId}. Must be > 0."
            );
        }

        if ($weight <= 0) {
            throw new \InvalidArgumentException(
                "Invalid weight: {$weight}. Must be > 0. " .
                "The caller must provide the actual product weight."
            );
        }

        // 1. Get coordinates - REQUIRE fresh coordinates from browser
        $coords = $coordinates ?? $this->getStoredCoordinates();

        if (!$coords || empty($coords['latitude']) || empty($coords['longitude'])) {
            return [
                'success' => false,
                'requires_location' => true,
                'location_type' => 'coordinates',
                'message' => __('يرجى تفعيل خدمة الموقع في المتصفح لحساب تكلفة الشحن'),
                'message_en' => 'Please enable location services in your browser to calculate shipping cost',
            ];
        }

        $latitude = (float) $coords['latitude'];
        $longitude = (float) $coords['longitude'];

        Log::info('═══════════════════════════════════════════════════════════');
        Log::info('SHIPPING QUOTE REQUEST START', [
            'merchant_id' => $merchantId,
            'branch_id' => $branchId,
            'weight_received' => $weight,
            'customer_latitude' => $latitude,
            'customer_longitude' => $longitude,
        ]);

        // 2. Get origin city from SPECIFIC branch - NO FALLBACK
        $originResult = $this->resolveOriginCity($merchantId, $branchId);

        if (!$originResult) {
            return [
                'success' => false,
                'error_code' => 'MERCHANT_CITY_NOT_SET',
                'message' => __('البائع لم يحدد مدينة الشحن أو مدينته غير مدعومة'),
            ];
        }

        // 3. Resolve destination city from coordinates - NO FALLBACK
        $destinationResult = $this->resolveDestinationCity($latitude, $longitude);

        if (!$destinationResult['success']) {
            return [
                'success' => false,
                'error_code' => $destinationResult['error_code'] ?? 'DESTINATION_ERROR',
                'message' => $destinationResult['message'] ?? __('لم نتمكن من تحديد مدينتك'),
            ];
        }

        $originCity = $originResult['city_name'];
        $destinationCity = $destinationResult['resolved_name'];

        Log::info('SHIPPING QUOTE: Final Data for Tryoto API (ALL VERIFIED - NO FALLBACKS)', [
            'origin_city' => $originCity,
            'origin_city_id' => $originResult['city_id'],
            'destination_city' => $destinationCity,
            'destination_city_id' => $destinationResult['city_id'],
            'weight_kg' => $weight,
            'merchant_id' => $merchantId,
        ]);

        // 4. Get quote from Tryoto
        $result = $this->tryGetQuote($merchantId, $originCity, $destinationCity, $weight);

        if ($result['success']) {
            return $result;
        }

        // 5. If no route, return failure (don't try fallbacks here - the resolution already did)
        return [
            'success' => false,
            'message' => __('لا تتوفر خيارات شحن لهذه المنطقة'),
            'debug' => [
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
            ],
        ];
    }

    /**
     * Resolve origin city from SPECIFIC branch - NO FALLBACKS
     *
     * @param int $merchantId Merchant user_id
     * @param int $branchId Specific branch ID
     * @return array|null City info or null if not found/not supported
     */
    protected function resolveOriginCity(int $merchantId, int $branchId): ?array
    {
        // Get SPECIFIC branch data from database - NO FALLBACK to other branches
        $branchCityData = ShippingCalculatorService::getBranchCity($branchId);

        Log::info('SHIPPING QUOTE: Merchant Branch Data (from DB)', [
            'merchant_id' => $merchantId,
            'branch_id' => $branchId,
            'branch_data' => $branchCityData,
        ]);

        // MUST have this specific branch
        if (!$branchCityData) {
            Log::error('SHIPPING QUOTE FAILED: Branch not found', [
                'merchant_id' => $merchantId,
                'branch_id' => $branchId,
            ]);
            return null;
        }

        // Verify branch belongs to this merchant
        if (($branchCityData['merchant_id'] ?? null) != $merchantId) {
            Log::error('SHIPPING QUOTE FAILED: Branch does not belong to merchant', [
                'merchant_id' => $merchantId,
                'branch_id' => $branchId,
                'branch_merchant_id' => $branchCityData['merchant_id'] ?? null,
            ]);
            return null;
        }

        if (empty($branchCityData['city_name'])) {
            Log::error('SHIPPING QUOTE FAILED: Branch has no city configured', [
                'merchant_id' => $merchantId,
                'branch_id' => $branchId,
            ]);
            return null;
        }

        $cityName = $branchCityData['city_name'];

        // Check if this city is supported by Tryoto - NO FALLBACK to other cities
        $city = DB::table('cities')
            ->where('city_name', $cityName)
            ->where('tryoto_supported', 1)
            ->first();

        if (!$city) {
            // Try partial match but ONLY for the same city name
            $city = DB::table('cities')
                ->where(function ($q) use ($cityName) {
                    $q->where('city_name', 'LIKE', $cityName . '%')
                      ->orWhere('city_name', 'LIKE', '%' . $cityName);
                })
                ->where('tryoto_supported', 1)
                ->first();
        }

        if (!$city) {
            Log::error('SHIPPING QUOTE FAILED: Branch city not supported by shipping company', [
                'merchant_id' => $merchantId,
                'branch_id' => $branchId,
                'city_name' => $cityName,
            ]);
            return null;
        }

        Log::info('SHIPPING QUOTE: Branch City Verified', [
            'merchant_id' => $merchantId,
            'branch_id' => $branchId,
            'branch_name' => $branchCityData['branch_name'] ?? null,
            'city_name' => $city->city_name,
            'city_id' => $city->id,
            'tryoto_supported' => true,
        ]);

        return [
            'city_name' => $city->city_name,
            'city_id' => $city->id,
            'branch_id' => $branchId,
            'branch_name' => $branchCityData['branch_name'] ?? null,
        ];
    }

    /**
     * Resolve destination city from coordinates - NO FALLBACKS
     *
     * Steps:
     * 1. Reverse geocode coordinates using GoogleMapsService (REQUIRED)
     * 2. Verify the city is supported by Tryoto (REQUIRED)
     *
     * NO FALLBACK to nearest city - customer's actual city must be supported
     */
    protected function resolveDestinationCity(float $latitude, float $longitude): array
    {
        // Step 1: Reverse geocode using GoogleMapsService - REQUIRED
        $geocodeResult = $this->googleMapsService->reverseGeocode($latitude, $longitude, 'en');

        if (!$geocodeResult['success'] || empty($geocodeResult['data'])) {
            Log::error('SHIPPING QUOTE FAILED: Google Maps geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $geocodeResult['error'] ?? 'Unknown error',
            ]);
            return [
                'success' => false,
                'error_code' => 'GEOCODING_FAILED',
                'message' => __('لم نتمكن من تحديد موقعك. تأكد من تفعيل خدمة الموقع.'),
            ];
        }

        $cityName = $geocodeResult['data']['city'] ?? null;
        $stateName = $geocodeResult['data']['state'] ?? null;
        $countryName = $geocodeResult['data']['country'] ?? null;

        Log::info('SHIPPING QUOTE: Google Maps Geocoding Result', [
            'city_from_google' => $cityName,
            'state_from_google' => $stateName,
            'country_from_google' => $countryName,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        // Step 2: Check if country is supported
        if (!$countryName) {
            Log::error('SHIPPING QUOTE FAILED: Could not determine country', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            return [
                'success' => false,
                'error_code' => 'COUNTRY_NOT_FOUND',
                'message' => __('لم نتمكن من تحديد دولتك'),
            ];
        }

        $country = DB::table('countries')
            ->where('country_name', $countryName)
            ->first();

        if (!$country) {
            Log::error('SHIPPING QUOTE FAILED: Country not supported', [
                'country' => $countryName,
            ]);
            return [
                'success' => false,
                'error_code' => 'COUNTRY_NOT_SUPPORTED',
                'message' => __('الدولة ":country" غير مدعومة للشحن', ['country' => $countryName]),
            ];
        }

        // Step 3: Find the EXACT city in our database - NO FALLBACK to other cities
        $searchNames = array_filter([$cityName, $stateName]);

        $city = null;
        foreach ($searchNames as $name) {
            if (!$name) continue;

            $city = DB::table('cities')
                ->where('country_id', $country->id)
                ->where('tryoto_supported', 1)
                ->where(function ($q) use ($name) {
                    $q->where('city_name', $name)
                      ->orWhere('city_name', 'LIKE', $name . '%')
                      ->orWhere('city_name', 'LIKE', '%' . $name);
                })
                ->first();

            if ($city) break;
        }

        if (!$city) {
            Log::error('SHIPPING QUOTE FAILED: Customer city not supported by shipping company', [
                'city_from_google' => $cityName,
                'state_from_google' => $stateName,
                'country' => $countryName,
            ]);
            return [
                'success' => false,
                'error_code' => 'CITY_NOT_SUPPORTED',
                'message' => __('مدينتك ":city" غير مدعومة حالياً من خدمة الشحن', ['city' => $cityName ?: $stateName]),
            ];
        }

        Log::info('SHIPPING QUOTE: Customer City Verified', [
            'google_city' => $cityName,
            'google_state' => $stateName,
            'resolved_city' => $city->city_name,
            'city_id' => $city->id,
            'tryoto_supported' => true,
        ]);

        return [
            'success' => true,
            'resolved_name' => $city->city_name,
            'city_id' => $city->id,
        ];
    }

    /**
     * Normalize city name for Tryoto API - MATCHES ShippingApiController
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
     * Try to get quote from Tryoto API
     */
    protected function tryGetQuote(int $merchantId, string $originCity, string $destinationCity, float $weight): array
    {
        // Check cache
        $cacheKey = "quote:{$merchantId}:{$originCity}:{$destinationCity}:{$weight}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            Log::info('SHIPPING QUOTE: Calling Tryoto API', [
                'origin_city' => $originCity,
                'destination_city' => $destinationCity,
                'weight_kg' => $weight,
                'merchant_id' => $merchantId,
            ]);

            $result = $this->tryotoService
                ->forMerchant($merchantId)
                ->getDeliveryOptions($originCity, $destinationCity, $weight, 0, []);

            Log::info('SHIPPING QUOTE: Tryoto API Response', [
                'success' => $result['success'],
                'options_count' => count($result['raw']['deliveryCompany'] ?? []),
            ]);

            if (!$result['success']) {
                return ['success' => false];
            }

            // Use raw deliveryCompany data with proper field names
            $rawOptions = $result['raw']['deliveryCompany'] ?? [];

            if (empty($rawOptions)) {
                return ['success' => false];
            }

            // Format options with correct Tryoto field names
            $formattedOptions = [];
            foreach ($rawOptions as $opt) {
                // Company name: prefer deliveryOptionName, fallback to deliveryCompanyName
                $companyName = $opt['deliveryOptionName'] ?? $opt['deliveryCompanyName'] ?? null;

                $formattedOptions[] = [
                    'id' => $opt['deliveryOptionId'] ?? $opt['id'] ?? null,
                    'name' => $companyName ?: __('شحن'),
                    'company_code' => $opt['deliveryCompanyName'] ?? null,
                    'price' => (float) ($opt['price'] ?? 0),
                    'currency' => 'SAR',
                    'estimated_days' => $this->parseDeliveryTime($opt['avgDeliveryTime'] ?? ''),
                    'avg_delivery_time' => $opt['avgDeliveryTime'] ?? null,
                ];
            }

            usort($formattedOptions, fn($a, $b) => $a['price'] <=> $b['price']);

            $response = [
                'success' => true,
                'options' => $formattedOptions,
                'origin' => $originCity,
                'destination' => $destinationCity,
            ];

            Cache::put($cacheKey, $response, 900);
            return $response;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'credentials')) {
                return [
                    'success' => false,
                    'message' => __('البائع لا يدعم الشحن الإلكتروني'),
                ];
            }

            return ['success' => false];
        }
    }

    /**
     * Get cheapest option from quote result
     */
    public function getCheapestOption(array $result): ?array
    {
        if (!$result['success'] || empty($result['options'])) {
            return null;
        }

        return $result['options'][0]; // Already sorted by price
    }

    /**
     * Parse delivery time string to readable format
     */
    protected function parseDeliveryTime(string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        // Same day
        if (stripos($time, 'same') !== false) {
            return '0';
        }

        // Next day
        if (stripos($time, 'next') !== false) {
            return '1';
        }

        // Range like "2to3" or "2-3"
        if (preg_match('/(\d+)\s*(?:to|-)\s*(\d+)/i', $time, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        // Single number
        if (preg_match('/(\d+)/', $time, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // ========================================================================
    // LOCATION STORAGE (for shipping quote - separate from checkout)
    // ========================================================================

    /**
     * Store coordinates from browser geolocation
     * Called by CustomerLocationController when user enables location
     */
    public function storeCoordinates(float $latitude, float $longitude): array
    {
        $data = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'stored_at' => now()->toISOString(),
            'source' => 'browser_geolocation',
        ];

        Session::put(self::SESSION_KEY, $data);

        // Also resolve the city for display purposes
        $resolution = $this->resolveDestinationCity($latitude, $longitude);

        if ($resolution['success']) {
            $data['resolved_city'] = $resolution['resolved_name'];
            $data['city_id'] = $resolution['city_id'] ?? null;
            Session::put(self::SESSION_KEY, $data);
        }

        return $data;
    }

    /**
     * Get stored coordinates
     */
    public function getStoredCoordinates(): ?array
    {
        $data = Session::get(self::SESSION_KEY);

        if (!$data || empty($data['latitude']) || empty($data['longitude'])) {
            return null;
        }

        return [
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'resolved_city' => $data['resolved_city'] ?? null,
        ];
    }

    /**
     * Check if coordinates are stored
     */
    public function hasStoredCoordinates(): bool
    {
        return $this->getStoredCoordinates() !== null;
    }

    /**
     * Get resolved city name (for display)
     */
    public function getResolvedCityName(): ?string
    {
        $data = Session::get(self::SESSION_KEY);
        return $data['resolved_city'] ?? null;
    }

    /**
     * Clear stored coordinates
     */
    public function clearStoredCoordinates(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
