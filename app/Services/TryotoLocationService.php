<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * TryotoLocationService - Location Resolution via Tryoto API ONLY
 *
 * السيناريو الصحيح:
 * 1. البيانات تأتي من Google Maps (الدولة، المنطقة، المدينة، الإحداثيات)
 * 2. Tryoto API هو المصدر الوحيد للتحقق من الدعم
 * 3. الجداول = مجرد Cache للنتائج السابقة من Tryoto
 * 4. لا توجد قوائم hardcoded نهائياً
 *
 * التسلسل:
 * City → State → Nearest State in Same Country → رفض العملية
 */
class TryotoLocationService
{
    protected TryotoService $tryotoService;

    public function __construct()
    {
        $this->tryotoService = app(TryotoService::class);
    }

    /**
     * Verify if a location is supported by Tryoto
     *
     * @param string $locationName City/State/Country name to check
     * @param string $testDestination Optional destination for testing
     * @return array
     */
    public function verifyCitySupport(string $locationName, string $testDestination = 'Riyadh'): array
    {
        return $this->tryotoService->verifyCitySupport($locationName, $testDestination);
    }

    /**
     * Main method: Resolve map location via Tryoto API
     *
     * السيناريو:
     * 1. محاولة المدينة من Google Maps
     * 2. إذا فشلت → محاولة State
     * 3. إذا فشلت → البحث عن أقرب State مدعومة داخل نفس الدولة
     * 4. إذا لم توجد → رفض العملية (الدولة غير مدعومة)
     *
     * @param string $cityName City from Google Maps
     * @param string|null $stateName State from Google Maps
     * @param string|null $countryName Country from Google Maps
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function resolveMapCity(
        string $cityName,
        ?string $stateName,
        ?string $countryName,
        float $latitude,
        float $longitude
    ): array {
        Log::info('TryotoLocation: Resolving map location via API', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName,
            'coordinates' => compact('latitude', 'longitude')
        ]);

        // ==========================================
        // المحاولة 1: المدينة من Google Maps
        // ==========================================
        $cityResult = $this->tryotoService->verifyCitySupport($cityName);

        if ($cityResult['supported']) {
            Log::info('TryotoLocation: City supported directly', [
                'city' => $cityName,
                'companies' => $cityResult['company_count']
            ]);

            return [
                'success' => true,
                'strategy' => 'exact_city',
                'resolved_name' => $cityName,
                'resolved_type' => 'city',
                'original' => [
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                ],
                'companies' => $cityResult['company_count'] ?? 0,
                'region' => $cityResult['region'] ?? null,
                'coordinates' => compact('latitude', 'longitude'),
                'message' => "المدينة '{$cityName}' مدعومة للشحن"
            ];
        }

        Log::info('TryotoLocation: City not supported, trying state...', [
            'city' => $cityName,
            'state' => $stateName
        ]);

        // ==========================================
        // المحاولة 2: المنطقة/State من Google Maps
        // ==========================================
        if ($stateName) {
            $stateResult = $this->tryotoService->verifyCitySupport($stateName);

            if ($stateResult['supported']) {
                Log::info('TryotoLocation: State supported', [
                    'state' => $stateName,
                    'companies' => $stateResult['company_count']
                ]);

                return [
                    'success' => true,
                    'strategy' => 'fallback_state',
                    'resolved_name' => $stateName,
                    'resolved_type' => 'state',
                    'original' => [
                        'city' => $cityName,
                        'state' => $stateName,
                        'country' => $countryName,
                    ],
                    'companies' => $stateResult['company_count'] ?? 0,
                    'region' => $stateResult['region'] ?? null,
                    'coordinates' => compact('latitude', 'longitude'),
                    'message' => "المدينة '{$cityName}' غير مدعومة، سيتم الشحن إلى المنطقة '{$stateName}'"
                ];
            }
        }

        Log::info('TryotoLocation: State not supported, searching for nearest supported state in same country...', [
            'state' => $stateName,
            'country' => $countryName
        ]);

        // ==========================================
        // المحاولة 3: البحث عن أقرب محافظة مدعومة داخل نفس الدولة
        // ==========================================
        if ($countryName) {
            $nearestState = $this->findNearestSupportedStateInCountry(
                $countryName,
                $latitude,
                $longitude
            );

            if ($nearestState) {
                Log::info('TryotoLocation: Found nearest supported state in same country', [
                    'original_state' => $stateName,
                    'nearest_state' => $nearestState['name'],
                    'distance_km' => $nearestState['distance_km'],
                    'country' => $countryName
                ]);

                return [
                    'success' => true,
                    'strategy' => 'nearest_state_same_country',
                    'resolved_name' => $nearestState['name'],
                    'resolved_type' => 'state',
                    'original' => [
                        'city' => $cityName,
                        'state' => $stateName,
                        'country' => $countryName,
                    ],
                    'companies' => $nearestState['companies'] ?? 0,
                    'region' => $nearestState['region'] ?? null,
                    'coordinates' => [
                        'latitude' => $nearestState['latitude'],
                        'longitude' => $nearestState['longitude']
                    ],
                    'original_coordinates' => compact('latitude', 'longitude'),
                    'distance_km' => $nearestState['distance_km'],
                    'message' => "المنطقة '{$stateName}' غير مدعومة، سيتم الشحن إلى أقرب منطقة مدعومة: '{$nearestState['name']}' ({$nearestState['distance_km']} كم)"
                ];
            }
        }

        // ==========================================
        // فشل جميع المحاولات - الدولة غير مدعومة بالكامل
        // ==========================================
        Log::warning('TryotoLocation: No supported states found in country', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName
        ]);

        return [
            'success' => false,
            'strategy' => 'none',
            'original' => [
                'city' => $cityName,
                'state' => $stateName,
                'country' => $countryName,
            ],
            'coordinates' => compact('latitude', 'longitude'),
            'message' => "الدولة '{$countryName}' غير مدعومة من شركة الشحن Tryoto"
        ];
    }

    /**
     * Find nearest supported state within the same country
     *
     * الخطوات:
     * 1. جلب المحافظات/المدن الرئيسية للدولة من Google Maps API
     * 2. التحقق من كل محافظة عبر Tryoto API
     * 3. حساب المسافة لكل محافظة مدعومة
     * 4. إرجاع الأقرب
     *
     * @param string $countryName
     * @param float $userLat
     * @param float $userLng
     * @return array|null
     */
    protected function findNearestSupportedStateInCountry(
        string $countryName,
        float $userLat,
        float $userLng
    ): ?array {
        Log::info('TryotoLocation: Searching for supported states in country', [
            'country' => $countryName
        ]);

        // ==========================================
        // الخطوة 1: جلب المحافظات من الـ Cache أولاً
        // ==========================================
        $cachedStates = $this->getCachedSupportedStates($countryName);

        if (!empty($cachedStates)) {
            Log::info('TryotoLocation: Found cached supported states', [
                'country' => $countryName,
                'count' => count($cachedStates)
            ]);

            return $this->findNearestFromList($cachedStates, $userLat, $userLng);
        }

        // ==========================================
        // الخطوة 2: جلب المحافظات من Google Maps API
        // ==========================================
        $states = $this->getCountryStatesFromGoogle($countryName);

        if (empty($states)) {
            Log::warning('TryotoLocation: Could not get states from Google Maps', [
                'country' => $countryName
            ]);
            return null;
        }

        Log::info('TryotoLocation: Got states from Google Maps', [
            'country' => $countryName,
            'count' => count($states)
        ]);

        // ==========================================
        // الخطوة 3: التحقق من كل محافظة عبر Tryoto
        // ==========================================
        $supportedStates = [];

        foreach ($states as $state) {
            $stateName = $state['name'];

            // Skip the original state (already checked)
            // التحقق من Tryoto
            $result = $this->tryotoService->verifyCitySupport($stateName);

            if ($result['supported']) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $state['latitude'],
                    $state['longitude']
                );

                $supportedStates[] = [
                    'name' => $stateName,
                    'name_ar' => $state['name_ar'] ?? $stateName,
                    'latitude' => $state['latitude'],
                    'longitude' => $state['longitude'],
                    'distance_km' => round($distance, 2),
                    'companies' => $result['company_count'] ?? 0,
                    'region' => $result['region'] ?? null
                ];

                Log::info('TryotoLocation: State supported by Tryoto', [
                    'state' => $stateName,
                    'distance_km' => round($distance, 2)
                ]);

                // Cache this supported state for future use
                $this->cacheSupportedState($countryName, $stateName, $state['latitude'], $state['longitude']);
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }

        if (empty($supportedStates)) {
            Log::warning('TryotoLocation: No supported states found in country', [
                'country' => $countryName,
                'checked_states' => count($states)
            ]);
            return null;
        }

        // ==========================================
        // الخطوة 4: اختيار الأقرب
        // ==========================================
        usort($supportedStates, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return $supportedStates[0];
    }

    /**
     * Get states of a country from Google Maps Geocoding API
     *
     * @param string $countryName
     * @return array
     */
    protected function getCountryStatesFromGoogle(string $countryName): array
    {
        $apiKey = config('services.google_maps.api_key');

        if (!$apiKey) {
            Log::warning('TryotoLocation: Google Maps API key not configured');
            return [];
        }

        try {
            // First, get country bounds
            $countryResponse = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $countryName,
                'key' => $apiKey,
                'language' => 'en'
            ]);

            if (!$countryResponse->successful()) {
                return [];
            }

            $countryData = $countryResponse->json();

            if ($countryData['status'] !== 'OK' || empty($countryData['results'])) {
                return [];
            }

            // Get country center for searching
            $location = $countryData['results'][0]['geometry']['location'];
            $countryLat = $location['lat'];
            $countryLng = $location['lng'];

            // Search for major cities/states in this country
            // Use Places API or known major cities approach
            $states = $this->searchMajorCitiesInCountry($countryName, $countryLat, $countryLng, $apiKey);

            return $states;

        } catch (\Exception $e) {
            Log::error('TryotoLocation: Error getting states from Google', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Search for major cities/states in a country using Google Places API
     *
     * @param string $countryName
     * @param float $countryLat
     * @param float $countryLng
     * @param string $apiKey
     * @return array
     */
    protected function searchMajorCitiesInCountry(
        string $countryName,
        float $countryLat,
        float $countryLng,
        string $apiKey
    ): array {
        $states = [];

        // Search queries based on country
        $searchQueries = [
            "major cities in {$countryName}",
            "governorates in {$countryName}",
            "provinces in {$countryName}",
            "states in {$countryName}"
        ];

        // Get country-specific major cities using text search
        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => "major cities in {$countryName}",
                'key' => $apiKey,
                'language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    foreach ($data['results'] as $place) {
                        // Only include places in this country
                        $cityName = $place['name'];
                        $location = $place['geometry']['location'];

                        // Get Arabic name
                        $arabicName = $this->getArabicName($cityName, $location['lat'], $location['lng'], $apiKey);

                        $states[] = [
                            'name' => $cityName,
                            'name_ar' => $arabicName,
                            'latitude' => $location['lat'],
                            'longitude' => $location['lng']
                        ];
                    }
                }
            }

            // Also search for governorates/provinces
            $response2 = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => "governorate {$countryName}",
                'key' => $apiKey,
                'language' => 'en'
            ]);

            if ($response2->successful()) {
                $data2 = $response2->json();

                if ($data2['status'] === 'OK' && !empty($data2['results'])) {
                    foreach ($data2['results'] as $place) {
                        $stateName = $place['name'];
                        $location = $place['geometry']['location'];

                        // Avoid duplicates
                        $exists = false;
                        foreach ($states as $s) {
                            if (strtolower($s['name']) === strtolower($stateName)) {
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $arabicName = $this->getArabicName($stateName, $location['lat'], $location['lng'], $apiKey);

                            $states[] = [
                                'name' => $stateName,
                                'name_ar' => $arabicName,
                                'latitude' => $location['lat'],
                                'longitude' => $location['lng']
                            ];
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('TryotoLocation: Error searching cities', [
                'error' => $e->getMessage()
            ]);
        }

        return $states;
    }

    /**
     * Get Arabic name for a location using reverse geocoding
     *
     * @param string $englishName
     * @param float $lat
     * @param float $lng
     * @param string $apiKey
     * @return string
     */
    protected function getArabicName(string $englishName, float $lat, float $lng, string $apiKey): string
    {
        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $apiKey,
                'language' => 'ar'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    foreach ($data['results'][0]['address_components'] as $component) {
                        if (in_array('locality', $component['types']) ||
                            in_array('administrative_area_level_1', $component['types'])) {
                            return $component['long_name'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors, return English name
        }

        return $englishName;
    }

    /**
     * Get cached supported states for a country
     *
     * @param string $countryName
     * @return array
     */
    protected function getCachedSupportedStates(string $countryName): array
    {
        // Look in database for previously discovered supported states
        $country = Country::where('country_name', $countryName)
            ->orWhere('country_name_ar', $countryName)
            ->first();

        if (!$country) {
            return [];
        }

        $states = State::where('country_id', $country->id)
            ->where('status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $cached = [];

        foreach ($states as $state) {
            // Verify this state is still supported by Tryoto
            // We trust the cache but check if coordinates exist
            if ($state->latitude && $state->longitude) {
                $cached[] = [
                    'name' => $state->state,
                    'name_ar' => $state->state_ar ?? $state->state,
                    'latitude' => $state->latitude,
                    'longitude' => $state->longitude
                ];
            }
        }

        return $cached;
    }

    /**
     * Find nearest state from a list
     *
     * @param array $states
     * @param float $userLat
     * @param float $userLng
     * @return array|null
     */
    protected function findNearestFromList(array $states, float $userLat, float $userLng): ?array
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($states as $state) {
            // Verify with Tryoto
            $result = $this->tryotoService->verifyCitySupport($state['name']);

            if ($result['supported']) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $state['latitude'],
                    $state['longitude']
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearest = [
                        'name' => $state['name'],
                        'name_ar' => $state['name_ar'] ?? $state['name'],
                        'latitude' => $state['latitude'],
                        'longitude' => $state['longitude'],
                        'distance_km' => round($distance, 2),
                        'companies' => $result['company_count'] ?? 0,
                        'region' => $result['region'] ?? null
                    ];
                }
            }
        }

        return $nearest;
    }

    /**
     * Cache a supported state in database
     *
     * @param string $countryName
     * @param string $stateName
     * @param float $latitude
     * @param float $longitude
     */
    protected function cacheSupportedState(
        string $countryName,
        string $stateName,
        float $latitude,
        float $longitude
    ): void {
        try {
            // Get or create country
            $country = Country::firstOrCreate(
                ['country_name' => $countryName],
                [
                    'country_code' => strtoupper(substr($countryName, 0, 2)),
                    'country_name_ar' => $countryName,
                    'status' => 1,
                    'tax' => 0
                ]
            );

            // Get or create state with coordinates
            State::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'state' => $stateName
                ],
                [
                    'state_ar' => $stateName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status' => 1,
                    'tax' => 0,
                    'owner_id' => 0
                ]
            );

            Log::info('TryotoLocation: Cached supported state', [
                'country' => $countryName,
                'state' => $stateName
            ]);

        } catch (\Exception $e) {
            Log::warning('TryotoLocation: Failed to cache state', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get all countries from database (Cache)
     */
    public function getCountries()
    {
        return Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);
    }

    /**
     * Get states for a country from database (Cache)
     */
    public function getStates(int $countryId)
    {
        return State::where('country_id', $countryId)
            ->where('status', 1)
            ->orderBy('state')
            ->get(['id', 'state', 'state_ar', 'country_id']);
    }

    /**
     * Get cities for a state from database (Cache)
     */
    public function getCities(int $stateId)
    {
        return City::where('state_id', $stateId)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'city_name_ar', 'state_id', 'country_id']);
    }
}
