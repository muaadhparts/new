<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * ShippingQuoteService - Quote Only
 *
 * Single function: getCatalogItemQuote(merchantId, weight, cityId)
 * Uses same logic as Checkout (origin merchant → destination customer)
 * NO shipment creation, NO COD, NO storage
 */
class ShippingQuoteService
{
    protected TryotoService $tryotoService;
    protected CustomerLocationService $locationService;

    public function __construct(
        TryotoService $tryotoService,
        CustomerLocationService $locationService
    ) {
        $this->tryotoService = $tryotoService;
        $this->locationService = $locationService;
    }

    /**
     * Get shipping quote for a catalogItem
     *
     * FAIL-FAST: weight is REQUIRED, no default values.
     * The caller must provide the actual weight from the product.
     *
     * @param int $merchantId Merchant user_id
     * @param float $weight CatalogItem weight in kg (REQUIRED)
     * @param int|null $cityId Destination city (uses session if null)
     * @return array Quote result
     * @throws \InvalidArgumentException if weight is invalid
     */
    public function getCatalogItemQuote(int $merchantId, float $weight, ?int $cityId = null): array
    {
        // FAIL-FAST: Validate inputs
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid merchantId: {$merchantId}. Must be > 0."
            );
        }

        if ($weight <= 0) {
            throw new \InvalidArgumentException(
                "Invalid weight: {$weight}. Must be > 0. " .
                "The caller must provide the actual product weight."
            );
        }
        // 1. Get destination city
        $destinationCityId = $cityId ?? $this->locationService->getCityId();

        if (!$destinationCityId) {
            return [
                'success' => false,
                'requires_location' => true,
                'message' => __('يرجى تحديد موقعك أولاً'),
            ];
        }

        // 2. Get origin city (merchant's city)
        $originCity = $this->getMerchantCity($merchantId);

        if (!$originCity) {
            return [
                'success' => false,
                'message' => __('البائع لم يحدد مدينة الشحن'),
            ];
        }

        // 3. Get destination city info
        $destCity = DB::table('cities')->where('id', $destinationCityId)->first();

        if (!$destCity) {
            return [
                'success' => false,
                'message' => __('المدينة المحددة غير صالحة'),
            ];
        }

        // 4. Try to get quote, if fails try nearby supported cities
        $result = $this->tryGetQuote($merchantId, $originCity, $destCity->city_name, $weight);

        if ($result['success']) {
            return $result;
        }

        // 5. If no route, try nearby supported cities
        if ($destCity->latitude && $destCity->longitude) {
            $alternativeCity = $this->findNearestWorkingCity(
                $merchantId,
                $originCity,
                $destCity->latitude,
                $destCity->longitude,
                $weight,
                $destinationCityId
            );

            if ($alternativeCity) {
                $result = $alternativeCity;
                $result['adjusted_city'] = true;
                return $result;
            }
        }

        return [
            'success' => false,
            'message' => __('لا تتوفر خيارات شحن لهذه المنطقة'),
        ];
    }

    /**
     * Try to get quote for a specific route
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
            $result = $this->tryotoService
                ->forMerchant($merchantId)
                ->getDeliveryOptions($originCity, $destinationCity, $weight, 0, []);

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
     * Find nearest city with working shipping route
     */
    protected function findNearestWorkingCity(
        int $merchantId,
        string $originCity,
        float $lat,
        float $lng,
        float $weight,
        int $excludeCityId
    ): ?array {
        // Get nearby supported cities
        $nearbyCities = DB::table('cities')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 1)
            ->where('tryoto_supported', 1)
            ->where('id', '!=', $excludeCityId)
            ->get();

        // Calculate distances and sort
        $citiesWithDistance = [];
        foreach ($nearbyCities as $city) {
            $distance = $this->haversineDistance($lat, $lng, $city->latitude, $city->longitude);
            if ($distance <= 100) { // Only within 100km
                $citiesWithDistance[] = [
                    'city' => $city,
                    'distance' => $distance,
                ];
            }
        }

        usort($citiesWithDistance, fn($a, $b) => $a['distance'] <=> $b['distance']);

        // Try top 5 nearest cities
        foreach (array_slice($citiesWithDistance, 0, 5) as $item) {
            $result = $this->tryGetQuote($merchantId, $originCity, $item['city']->city_name, $weight);

            if ($result['success']) {
                $result['destination'] = $item['city']->city_name;
                $result['destination_id'] = $item['city']->id;
                $result['distance_km'] = round($item['distance'], 1);
                return $result;
            }
        }

        return null;
    }

    /**
     * Haversine distance formula
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
     * Get merchant's origin city name from merchant_locations
     */
    protected function getMerchantCity(int $merchantId): ?string
    {
        // Get first active merchant location (warehouse)
        $merchantLocation = DB::table('merchant_locations')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->first();

        if (!$merchantLocation || !$merchantLocation->city_id) {
            return null;
        }

        return $this->getCityName((int) $merchantLocation->city_id);
    }

    /**
     * Get city name by ID
     */
    protected function getCityName(int $cityId): ?string
    {
        $city = DB::table('cities')->where('id', $cityId)->first();
        return $city->city_name ?? null;
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
}
