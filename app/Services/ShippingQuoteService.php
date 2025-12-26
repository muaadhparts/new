<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ShippingQuoteService - Quote Only
 *
 * Single function: getProductQuote(vendorId, weight, cityId)
 * Uses same logic as Checkout (origin vendor → destination customer)
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
     * Get shipping quote for a product
     *
     * @param int $vendorId Vendor user_id
     * @param float $weight Product weight in kg
     * @param int|null $cityId Destination city (uses session if null)
     * @return array Quote result
     */
    public function getProductQuote(int $vendorId, float $weight = 0.5, ?int $cityId = null): array
    {
        // 1. Get destination city
        $destinationCityId = $cityId ?? $this->locationService->getCityId();

        if (!$destinationCityId) {
            return [
                'success' => false,
                'requires_location' => true,
                'message' => __('يرجى تحديد موقعك أولاً'),
            ];
        }

        // 2. Get origin city (vendor's city)
        $originCity = $this->getVendorCity($vendorId);

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
        $result = $this->tryGetQuote($vendorId, $originCity, $destCity->city_name, $weight);

        if ($result['success']) {
            return $result;
        }

        // 5. If no route, try nearby supported cities
        if ($destCity->latitude && $destCity->longitude) {
            $alternativeCity = $this->findNearestWorkingCity(
                $vendorId,
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
    protected function tryGetQuote(int $vendorId, string $originCity, string $destinationCity, float $weight): array
    {
        // Check cache
        $cacheKey = "quote:{$vendorId}:{$originCity}:{$destinationCity}:{$weight}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $result = $this->tryotoService
                ->forVendor($vendorId)
                ->getDeliveryOptions($originCity, $destinationCity, $weight, 0, []);

            if (!$result['success']) {
                return ['success' => false];
            }

            $options = $result['raw']['deliveryCompany'] ?? [];

            if (empty($options)) {
                return ['success' => false];
            }

            // Format options
            $formattedOptions = [];
            foreach ($options as $opt) {
                $formattedOptions[] = [
                    'id' => $opt['id'] ?? $opt['companyId'] ?? null,
                    'name' => $opt['name'] ?? $opt['companyName'] ?? __('شحن'),
                    'price' => (float) ($opt['price'] ?? $opt['totalPrice'] ?? 0),
                    'currency' => 'SAR',
                    'estimated_days' => $opt['estimatedDays'] ?? $opt['deliveryDays'] ?? null,
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
            Log::warning('ShippingQuoteService: Error', [
                'vendor_id' => $vendorId,
                'route' => "$originCity → $destinationCity",
                'error' => $e->getMessage(),
            ]);

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
        int $vendorId,
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
            $result = $this->tryGetQuote($vendorId, $originCity, $item['city']->city_name, $weight);

            if ($result['success']) {
                $result['destination'] = $item['city']->city_name;
                $result['destination_id'] = $item['city']->id;
                $result['distance_km'] = round($item['distance'], 1);

                Log::info('ShippingQuoteService: Found alternative city', [
                    'original_city_id' => $excludeCityId,
                    'alternative' => $item['city']->city_name,
                    'distance_km' => $result['distance_km'],
                ]);

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
     * Get vendor's origin city name
     */
    protected function getVendorCity(int $vendorId): ?string
    {
        $vendor = DB::table('users')
            ->select('city')
            ->where('id', $vendorId)
            ->first();

        if (!$vendor || empty($vendor->city)) {
            return null;
        }

        // If city is an ID, get the name
        if (is_numeric($vendor->city)) {
            return $this->getCityName((int) $vendor->city);
        }

        return $vendor->city;
    }

    /**
     * Get city name by ID
     */
    protected function getCityName(int $cityId): ?string
    {
        $city = DB::table('cities')->where('id', $cityId)->first();
        return $city->city_name ?? null;
    }
}
