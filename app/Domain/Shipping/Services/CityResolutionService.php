<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CityResolutionService
 * 
 * Handles all city resolution and geolocation logic
 * 
 * Responsibilities:
 * - Resolve merchant city from merchant_branches
 * - Resolve customer city from purchase data
 * - Find nearest supported city using Haversine formula
 * - Convert city ID to city name
 */
class CityResolutionService
{
    /**
     * Resolve merchant city from merchant_branches
     * 
     * merchant_branches is the ONLY source for merchant address
     * 
     * @param int $merchantId
     * @return string|null City name
     */
    public function resolveMerchantCity(int $merchantId): ?string
    {
        $merchantBranch = DB::table('merchant_branches')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->first();

        if ($merchantBranch && $merchantBranch->city_id) {
            $city = City::find($merchantBranch->city_id);
            if ($city && $city->name) {
                return $city->name;
            }
        }

        Log::warning('Merchant has no branch configured in merchant_branches', [
            'merchant_id' => $merchantId,
            'tip' => 'Add merchant branch in merchant_branches table'
        ]);

        return null;
    }

    /**
     * Resolve customer city from purchase data
     * 
     * Priority:
     * 1. shipping_city from customer_shipping_choice (supported city selected during checkout)
     * 2. customer_city (original city from map)
     * 
     * This ensures we use the same city that was shown to customer during checkout
     * 
     * @param Purchase $purchase
     * @param int $merchantId
     * @return string|null City name
     */
    public function resolveCustomerCity(Purchase $purchase, int $merchantId): ?string
    {
        // First: Check shipping_city in customer_shipping_choice
        // This city was selected during checkout and is actually supported
        $customerShippingChoice = $purchase->customer_shipping_choice;
        if (is_array($customerShippingChoice) && isset($customerShippingChoice[$merchantId])) {
            $merchantChoice = $customerShippingChoice[$merchantId];
            if (!empty($merchantChoice['shipping_city'])) {
                Log::debug('resolveCustomerCity: Using shipping_city from customer_shipping_choice', [
                    'purchase_id' => $purchase->id,
                    'shipping_city' => $merchantChoice['shipping_city'],
                    'original_customer_city' => $purchase->customer_city
                ]);
                return $merchantChoice['shipping_city'];
            }
        }

        // Fallback: Use customer_city from purchase
        $cityValue = $purchase->customer_city;

        if (!$cityValue) {
            Log::warning('Purchase has no customer_city', [
                'purchase_id' => $purchase->id
            ]);
            return null;
        }

        // If it's a number (ID), convert to city name
        if (is_numeric($cityValue)) {
            return $this->resolveCityName($cityValue);
        }

        // Return city as stored
        return $cityValue;
    }

    /**
     * Find nearest supported city using Haversine formula
     * 
     * Problem: Sometimes a city exists in DB as tryoto_supported but Tryoto API doesn't actually serve it
     * Solution: Find nearest DIFFERENT city using Haversine formula
     * 
     * @param Purchase $purchase
     * @return string|null Nearest city name
     */
    public function findNearestSupportedCity(Purchase $purchase): ?string
    {
        $lat = $purchase->customer_latitude;
        $lng = $purchase->customer_longitude;
        $originalCity = $purchase->customer_city;

        if (!$lat || !$lng) {
            Log::debug('findNearestSupportedCity: No coordinates in purchase', [
                'purchase_id' => $purchase->id
            ]);
            return null;
        }

        try {
            // Find country
            $country = Country::where('country_name', 'like', '%' . ($purchase->customer_country ?? 'Saudi') . '%')
                ->orWhere('country_name_ar', 'like', '%' . ($purchase->customer_country ?? 'سعودي') . '%')
                ->first();

            if (!$country) {
                Log::debug('findNearestSupportedCity: Country not found');
                return null;
            }

            // Haversine formula to calculate distance in kilometers
            $haversine = "(6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            ))";

            // Find nearest DIFFERENT city (within 100 km)
            $nearestCity = City::where('country_id', $country->id)
                ->where('tryoto_supported', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('name', '!=', $originalCity) // Exclude original city
                ->selectRaw("name, {$haversine} as distance_km", [(float)$lat, (float)$lng, (float)$lat])
                ->havingRaw('distance_km <= ?', [100]) // Max 100 km
                ->orderBy('distance_km', 'asc')
                ->first();

            if ($nearestCity) {
                Log::debug('findNearestSupportedCity: Found different city', [
                    'purchase_id' => $purchase->id,
                    'original_city' => $originalCity,
                    'nearest_city' => $nearestCity->name,
                    'distance_km' => round($nearestCity->distance_km, 2)
                ]);
                return $nearestCity->name;
            }

            Log::debug('findNearestSupportedCity: No different city found within 100km');

        } catch (\Exception $e) {
            Log::warning('findNearestSupportedCity: Failed', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Convert city ID to city name
     * 
     * @param mixed $cityIdOrName City ID or name
     * @param mixed $fallbackName Fallback name if ID not found
     * @return string|null City name
     */
    public function resolveCityName($cityIdOrName, $fallbackName = null): ?string
    {
        // If we have an ID, look up the name
        if ($cityIdOrName && is_numeric($cityIdOrName)) {
            $city = City::find($cityIdOrName);
            if ($city && $city->name) {
                return $city->name;
            }
        }

        // If it's a text name (not a number), use it directly
        if ($cityIdOrName && !is_numeric($cityIdOrName)) {
            return $cityIdOrName;
        }

        // Last attempt: If fallback is a number too
        if ($fallbackName && is_numeric($fallbackName)) {
            $city = City::find($fallbackName);
            if ($city && $city->name) {
                return $city->name;
            }
        }

        // Use fallback as-is if it's a string
        if ($fallbackName && !is_numeric($fallbackName)) {
            return $fallbackName;
        }

        return null;
    }

    /**
     * Check if a city is supported by Tryoto
     * 
     * @param string $cityName
     * @return bool
     */
    public function isCitySupported(string $cityName): bool
    {
        return City::where('name', $cityName)
            ->where('tryoto_supported', 1)
            ->exists();
    }

    /**
     * Get all supported cities for a country
     * 
     * @param int $countryId
     * @return \Illuminate\Support\Collection
     */
    public function getSupportedCities(int $countryId)
    {
        return City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->orderBy('name')
            ->get();
    }
}
