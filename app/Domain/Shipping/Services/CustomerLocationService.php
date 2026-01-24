<?php

namespace App\Domain\Shipping\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

/**
 * CustomerLocationService - Single Source for Customer Location
 *
 * Stores city_id only in Session.
 * Independent from Checkout/Cart.
 *
 * Set via:
 * - Manual selection (Modal)
 * - Geolocation (with manual fallback)
 */
class CustomerLocationService
{
    private const SESSION_KEY = 'customer_shipping_city';

    /**
     * Check if city is set
     */
    public function hasCity(): bool
    {
        return $this->getCityId() !== null;
    }

    /**
     * Get city_id
     */
    public function getCityId(): ?int
    {
        $data = Session::get(self::SESSION_KEY);
        return $data['city_id'] ?? null;
    }

    /**
     * Get city name
     */
    public function getCityName(): ?string
    {
        $data = Session::get(self::SESSION_KEY);
        return $data['city_name'] ?? null;
    }

    /**
     * Set city manually (user selected from dropdown)
     */
    public function setManually(int $cityId): array
    {
        $city = DB::table('cities')->where('id', $cityId)->first();

        if (!$city) {
            throw new \Exception(__('المدينة غير موجودة'));
        }

        $data = [
            'city_id' => $cityId,
            'city_name' => $city->city_name,
            'source' => 'manual',
        ];

        Session::put(self::SESSION_KEY, $data);

        return $data;
    }

    /**
     * Set city from geolocation coordinates
     * Resolves to nearest supported city
     */
    public function setFromGeolocation(float $lat, float $lng): array
    {
        $city = $this->resolveCoordinatesToCity($lat, $lng);

        if (!$city) {
            throw new \Exception(__('لم نتمكن من تحديد مدينتك. يرجى اختيار المدينة يدوياً.'));
        }

        $data = [
            'city_id' => $city->id,
            'city_name' => $city->city_name,
            'source' => 'geolocation',
        ];

        Session::put(self::SESSION_KEY, $data);

        return $data;
    }

    /**
     * Clear location
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get all supported cities for dropdown
     */
    public function getAvailableCities(): array
    {
        return cache()->remember('shipping_cities_dropdown', 3600, function () {
            return DB::table('cities')
                ->select('id', 'city_name')
                ->where('status', 1)
                ->orderBy('city_name')
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->city_name])
                ->toArray();
        });
    }

    /**
     * Find nearest SUPPORTED city to coordinates (Haversine)
     * Uses tryoto_supported column from cities table
     */
    protected function resolveCoordinatesToCity(float $lat, float $lng): ?object
    {
        // First: Try to find nearest city with Tryoto support
        $supportedCities = DB::table('cities')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 1)
            ->where('tryoto_supported', 1)
            ->get();

        if ($supportedCities->isNotEmpty()) {
            $nearest = null;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($supportedCities as $city) {
                $distance = $this->haversineDistance($lat, $lng, $city->latitude, $city->longitude);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearest = $city;
                }
            }

            // If found a supported city within 300km, use it
            if ($nearest && $minDistance <= 300) {
                return $nearest;
            }
        }

        // Fallback: find any nearest city within 100km
        $allCities = DB::table('cities')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 1)
            ->get();

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($allCities as $city) {
            $distance = $this->haversineDistance($lat, $lng, $city->latitude, $city->longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $city;
            }
        }

        return ($nearest && $minDistance <= 100) ? $nearest : null;
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
}
