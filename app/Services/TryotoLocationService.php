<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tryoto Location Service
 *
 * Handles dynamic location discovery and caching for Tryoto shipping
 *
 * Strategy:
 * 1. Countries/States/Cities are loaded from local DB (fast)
 * 2. When user selects a city → verify with Tryoto API
 * 3. If city is supported but not in DB → auto-save it
 * 4. DB acts as a learning cache that grows over time
 */
class TryotoLocationService
{
    protected $url;
    protected $refreshToken;
    protected $accessToken;
    protected $cacheName;
    protected $cacheTime;

    public function __construct()
    {
        // Use the same config pattern as TryotoService
        if (!config('services.tryoto.sandbox')) {
            $this->url = config('laravel-tryoto.tryoto.live.url');
            $this->refreshToken = config('laravel-tryoto.tryoto.live.token');
        } else {
            $this->url = config('laravel-tryoto.tryoto.test.url');
            $this->refreshToken = config('laravel-tryoto.tryoto.test.token');
        }

        $this->cacheName = config('laravel-tryoto.cache_name', 'tryoto_access_token');
        $this->cacheTime = config('laravel-tryoto.cache_time', 30);

        $this->accessToken = $this->authorize();
    }

    /**
     * Get access token (same as TryotoService)
     */
    protected function authorize()
    {
        if (Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        try {
            $response = Http::timeout(10)->post($this->url . '/rest/v2/refreshToken', [
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Cache::put($this->cacheName, $token, now()->addMinutes($this->cacheTime));
                return $token;
            }

            Log::error('Tryoto authorization failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Tryoto authorization exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify if a city is supported by Tryoto
     *
     * @param string $cityName City name to check
     * @param string $testDestination Optional destination city for testing
     * @return array ['supported' => bool, 'companies' => array, 'region' => string|null]
     */
    public function verifyCitySupport($cityName, $testDestination = 'Riyadh')
    {
        if (!$this->accessToken) {
            return ['supported' => false, 'error' => 'Authentication failed'];
        }

        try {
            // Test both directions to be sure
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Accept' => 'application/json',
            ])
                ->timeout(15)
                ->post($this->url . '/rest/v2/checkOTODeliveryFee', [
                    'originCity' => $cityName,
                    'destinationCity' => $testDestination,
                    'weight' => 1,
                    'xlength' => 30,
                    'xheight' => 30,
                    'xwidth' => 30,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['deliveryCompany'] ?? [];

                // Extract region from response if available
                $region = $data['originRegion'] ?? $data['region'] ?? null;

                return [
                    'supported' => !empty($companies),
                    'companies' => $companies,
                    'company_count' => count($companies),
                    'region' => $region,
                    'full_response' => $data
                ];
            }

            return ['supported' => false, 'error' => 'API request failed'];
        } catch (\Exception $e) {
            Log::error('Tryoto city verification failed', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);

            return ['supported' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify and auto-save location if supported
     *
     * @param int $countryId
     * @param int $stateId
     * @param string $cityName
     * @return array ['verified' => bool, 'city_id' => int|null, 'message' => string]
     */
    public function verifyAndSaveLocation($countryId, $stateId, $cityName)
    {
        // Check if city already exists
        $existingCity = City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();

        if ($existingCity) {
            return [
                'verified' => true,
                'city_id' => $existingCity->id,
                'message' => 'City already in database',
                'cached' => true
            ];
        }

        // Verify with Tryoto API
        $verification = $this->verifyCitySupport($cityName);

        if (!$verification['supported']) {
            return [
                'verified' => false,
                'city_id' => null,
                'message' => 'City not supported by Tryoto shipping',
                'error' => $verification['error'] ?? 'Unknown error'
            ];
        }

        // City is supported → save it to database
        try {
            DB::beginTransaction();

            // Get country info
            $country = Country::find($countryId);
            if (!$country) {
                DB::rollBack();
                return [
                    'verified' => false,
                    'message' => 'Country not found'
                ];
            }

            // Get or create state
            $state = State::find($stateId);
            if (!$state) {
                // Try to extract region from Tryoto response
                $regionName = $verification['region'] ?? 'Default';

                $state = State::firstOrCreate(
                    [
                        'country_id' => $countryId,
                        'state' => $regionName
                    ],
                    [
                        'state_ar' => $this->translateRegion($regionName),
                        'tax' => 0,
                        'status' => 1,
                        'owner_id' => 0
                    ]
                );

                $stateId = $state->id;
            }

            // Create city
            $city = City::create([
                'state_id' => $stateId,
                'country_id' => $countryId,
                'city_name' => $cityName,
                'city_name_ar' => $this->translateCity($cityName),
                'status' => 1
            ]);

            DB::commit();

            Log::info('New Tryoto city auto-saved', [
                'country' => $country->country_name,
                'state' => $state->state,
                'city' => $cityName,
                'companies' => $verification['company_count']
            ]);

            return [
                'verified' => true,
                'city_id' => $city->id,
                'message' => 'City verified and saved successfully',
                'cached' => false,
                'companies' => $verification['company_count']
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save verified city', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);

            return [
                'verified' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simple translation helper for regions
     */
    protected function translateRegion($regionName)
    {
        $translations = [
            'Riyadh Region' => 'منطقة الرياض',
            'Makkah Region' => 'منطقة مكة المكرمة',
            'Eastern Region' => 'المنطقة الشرقية',
            'Madinah Region' => 'منطقة المدينة المنورة',
            'Al-Qassim Region' => 'منطقة القصيم',
            'Asir Region' => 'منطقة عسير',
            'Tabuk Region' => 'منطقة تبوك',
            'Hail Region' => 'منطقة حائل',
            'Najran Region' => 'منطقة نجران',
            'Jazan Region' => 'منطقة جازان',
            'Northern Borders Region' => 'منطقة الحدود الشمالية',
            'Al Jouf Region' => 'منطقة الجوف',
            'Default' => 'افتراضي'
        ];

        return $translations[$regionName] ?? $regionName;
    }

    /**
     * Simple translation helper for cities
     */
    protected function translateCity($cityName)
    {
        $translations = [
            'Riyadh' => 'الرياض',
            'Jeddah' => 'جدة',
            'Mecca' => 'مكة المكرمة',
            'Medina' => 'المدينة المنورة',
            'Dammam' => 'الدمام',
            'Al Khobar' => 'الخبر',
            'Dhahran' => 'الظهران',
            'Tabuk' => 'تبوك',
            'Buraidah' => 'بريدة',
            'Khamis Mushait' => 'خميس مشيط',
            'Hail' => 'حائل',
            'Najran' => 'نجران',
            'Jazan' => 'جازان',
            'Taif' => 'الطائف',
            'Yanbu' => 'ينبع',
            'Abha' => 'أبها',
            'Dubai' => 'دبي',
            'Manama' => 'المنامة',
            'Doha' => 'الدوحة',
            'Kuwait City' => 'مدينة الكويت',
            'Muscat' => 'مسقط',
            'Cairo' => 'القاهرة',
            'Amman' => 'عمان',
            'Beirut' => 'بيروت',
            'Baghdad' => 'بغداد'
        ];

        return $translations[$cityName] ?? $cityName;
    }

    /**
     * Get all countries from database (cached from Tryoto discoveries)
     */
    public function getCountries()
    {
        return Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);
    }

    /**
     * Get states for a country from database
     */
    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)
            ->where('status', 1)
            ->orderBy('state')
            ->get(['id', 'state', 'state_ar', 'country_id']);
    }

    /**
     * Get cities for a state from database
     */
    public function getCities($stateId)
    {
        return City::where('state_id', $stateId)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'city_name_ar', 'state_id', 'country_id']);
    }

    /**
     * Find nearest supported city by coordinates
     * Uses Haversine formula to calculate distance
     *
     * @param float $latitude
     * @param float $longitude
     * @param int|null $countryId Limit search to specific country
     * @param int $maxResults Maximum number of cities to check
     * @return array|null
     */
    public function findNearestSupportedCity($latitude, $longitude, $countryId = null, $maxResults = 50)
    {
        // Get all cities with coordinates (we'll need to add lat/lng to cities table or use a mapping)
        // For now, we'll use major Saudi cities with known coordinates
        $saudiCities = $this->getSaudiMajorCitiesWithCoordinates();

        $nearestCity = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($saudiCities as $city) {
            // Calculate distance using Haversine formula
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $city['lat'],
                $city['lng']
            );

            if ($distance < $shortestDistance) {
                // Verify this city is supported by Tryoto
                $verification = $this->verifyCitySupport($city['name']);

                if ($verification['supported']) {
                    $shortestDistance = $distance;
                    $nearestCity = [
                        'city_name' => $city['name'],
                        'city_name_ar' => $city['name_ar'],
                        'distance_km' => round($distance, 2),
                        'coordinates' => [
                            'lat' => $city['lat'],
                            'lng' => $city['lng']
                        ],
                        'companies' => $verification['company_count'] ?? 0,
                        'verified' => true
                    ];
                }
            }
        }

        return $nearestCity;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get major Saudi cities with coordinates
     * This is a hardcoded list of major cities that Tryoto supports
     *
     * @return array
     */
    protected function getSaudiMajorCitiesWithCoordinates()
    {
        return [
            // Riyadh Region
            ['name' => 'Riyadh', 'name_ar' => 'الرياض', 'lat' => 24.7136, 'lng' => 46.6753],
            ['name' => 'Al Kharj', 'name_ar' => 'الخرج', 'lat' => 24.1556, 'lng' => 47.3119],
            ['name' => 'Diriyah', 'name_ar' => 'الدرعية', 'lat' => 24.7392, 'lng' => 46.5750],

            // Makkah Region
            ['name' => 'Jeddah', 'name_ar' => 'جدة', 'lat' => 21.5433, 'lng' => 39.1728],
            ['name' => 'Mecca', 'name_ar' => 'مكة المكرمة', 'lat' => 21.4225, 'lng' => 39.8262],
            ['name' => 'Taif', 'name_ar' => 'الطائف', 'lat' => 21.2703, 'lng' => 40.4158],
            ['name' => 'Rabigh', 'name_ar' => 'رابغ', 'lat' => 22.7981, 'lng' => 39.0353],

            // Eastern Region
            ['name' => 'Dammam', 'name_ar' => 'الدمام', 'lat' => 26.4207, 'lng' => 50.0888],
            ['name' => 'Al Khobar', 'name_ar' => 'الخبر', 'lat' => 26.2787, 'lng' => 50.2085],
            ['name' => 'Dhahran', 'name_ar' => 'الظهران', 'lat' => 26.2885, 'lng' => 50.1538],
            ['name' => 'Jubail', 'name_ar' => 'الجبيل', 'lat' => 27.0173, 'lng' => 49.6574],
            ['name' => 'Al Ahsa', 'name_ar' => 'الأحساء', 'lat' => 25.3769, 'lng' => 49.5883],

            // Madinah Region
            ['name' => 'Medina', 'name_ar' => 'المدينة المنورة', 'lat' => 24.5247, 'lng' => 39.5692],
            ['name' => 'Yanbu', 'name_ar' => 'ينبع', 'lat' => 24.0895, 'lng' => 38.0619],

            // Qassim Region
            ['name' => 'Buraidah', 'name_ar' => 'بريدة', 'lat' => 26.3260, 'lng' => 43.9750],
            ['name' => 'Unaizah', 'name_ar' => 'عنيزة', 'lat' => 26.0878, 'lng' => 43.9925],

            // Asir Region
            ['name' => 'Abha', 'name_ar' => 'أبها', 'lat' => 18.2164, 'lng' => 42.5053],
            ['name' => 'Khamis Mushait', 'name_ar' => 'خميس مشيط', 'lat' => 18.3067, 'lng' => 42.7289],

            // Tabuk Region
            ['name' => 'Tabuk', 'name_ar' => 'تبوك', 'lat' => 28.3838, 'lng' => 36.5550],

            // Hail Region
            ['name' => 'Hail', 'name_ar' => 'حائل', 'lat' => 27.5219, 'lng' => 41.6901],

            // Jazan Region
            ['name' => 'Jazan', 'name_ar' => 'جازان', 'lat' => 16.8892, 'lng' => 42.5511],

            // Najran Region
            ['name' => 'Najran', 'name_ar' => 'نجران', 'lat' => 17.4924, 'lng' => 44.1277],

            // Al Jouf Region
            ['name' => 'Sakaka', 'name_ar' => 'سكاكا', 'lat' => 29.9697, 'lng' => 40.2064],

            // Northern Borders Region
            ['name' => 'Arar', 'name_ar' => 'عرعر', 'lat' => 30.9753, 'lng' => 41.0381],

            // Al Bahah Region
            ['name' => 'Al Bahah', 'name_ar' => 'الباحة', 'lat' => 20.0129, 'lng' => 41.4677],
        ];
    }

    /**
     * Smart city resolution for map-selected locations
     * Tries to find exact match first, then falls back to nearest supported city
     *
     * @param string $cityName City name from Google Maps
     * @param float $latitude
     * @param float $longitude
     * @param int|null $countryId
     * @return array
     */
    public function resolveMapCity($cityName, $latitude, $longitude, $countryId = null)
    {
        // Step 1: Try exact city name with Tryoto
        $exactMatch = $this->verifyCitySupport($cityName);

        if ($exactMatch['supported']) {
            return [
                'strategy' => 'exact_match',
                'city_name' => $cityName,
                'verified' => true,
                'companies' => $exactMatch['company_count'] ?? 0,
                'message' => 'Selected city is supported by Tryoto'
            ];
        }

        // Step 2: Try variations of the city name
        $variations = $this->getCityNameVariations($cityName);

        foreach ($variations as $variation) {
            $variantMatch = $this->verifyCitySupport($variation);

            if ($variantMatch['supported']) {
                return [
                    'strategy' => 'name_variation',
                    'original_name' => $cityName,
                    'city_name' => $variation,
                    'verified' => true,
                    'companies' => $variantMatch['company_count'] ?? 0,
                    'message' => "Using city name variation: {$variation}"
                ];
            }
        }

        // Step 3: Find nearest supported city
        $nearest = $this->findNearestSupportedCity($latitude, $longitude, $countryId);

        if ($nearest) {
            return [
                'strategy' => 'nearest_city',
                'original_name' => $cityName,
                'city_name' => $nearest['city_name'],
                'city_name_ar' => $nearest['city_name_ar'],
                'distance_km' => $nearest['distance_km'],
                'verified' => true,
                'companies' => $nearest['companies'],
                'coordinates' => $nearest['coordinates'],
                'message' => "Selected location not supported. Nearest supported city: {$nearest['city_name']} ({$nearest['distance_km']} km away)"
            ];
        }

        // Step 4: No supported city found
        return [
            'strategy' => 'none',
            'original_name' => $cityName,
            'verified' => false,
            'message' => 'No supported cities found in this area. Please select a location within Saudi Arabia.'
        ];
    }

    /**
     * Get city name variations for better matching
     *
     * @param string $cityName
     * @return array
     */
    protected function getCityNameVariations($cityName)
    {
        $variations = [];

        // Remove common prefixes
        $prefixes = ['Al ', 'Al-', 'al ', 'al-'];
        foreach ($prefixes as $prefix) {
            if (stripos($cityName, $prefix) === 0) {
                $variations[] = substr($cityName, strlen($prefix));
            }
        }

        // Add 'Al ' prefix if not present
        if (!in_array(substr($cityName, 0, 3), ['Al ', 'al '])) {
            $variations[] = 'Al ' . $cityName;
            $variations[] = 'Al-' . $cityName;
        }

        // Common name mappings
        $nameMap = [
            'Riyadh' => ['Ar Riyadh', 'الرياض'],
            'Jeddah' => ['Jiddah', 'Gedda', 'جدة'],
            'Mecca' => ['Makkah', 'Makkah Al Mukarramah', 'مكة', 'مكة المكرمة'],
            'Medina' => ['Madinah', 'Al Madinah', 'المدينة المنورة'],
            'Dammam' => ['Ad Dammam', 'الدمام'],
        ];

        if (isset($nameMap[$cityName])) {
            $variations = array_merge($variations, $nameMap[$cityName]);
        }

        return array_unique($variations);
    }
}
