<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\TryotoLocationService;
use App\Services\TryotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeocodingController - Location Resolution via Google Maps + Tryoto API
 *
 * السيناريو الصحيح:
 * 1. Google Maps = مصدر البيانات (الدولة، المنطقة، المدينة، الإحداثيات)
 * 2. Tryoto API = مصدر التحقق الوحيد (هل مدعومة أم لا)
 * 3. الجداول = مجرد Cache للنتائج السابقة
 *
 * التسلسل:
 * City من Maps → Tryoto API
 * إذا غير مدعومة → State من Maps → Tryoto API
 * إذا غير مدعومة → Country من Maps → Tryoto API
 * إذا غير مدعومة → رفض العملية
 *
 * لا توجد قوائم hardcoded نهائياً!
 */
class GeocodingController extends Controller
{
    protected TryotoLocationService $tryotoLocationService;
    protected TryotoService $tryotoService;

    public function __construct(TryotoLocationService $tryotoLocationService, TryotoService $tryotoService)
    {
        $this->tryotoLocationService = $tryotoLocationService;
        $this->tryotoService = $tryotoService;
    }

    /**
     * Reverse geocode coordinates to address with Tryoto verification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        Log::info('Geocoding: Starting reverse geocode', compact('latitude', 'longitude'));

        try {
            // ==========================================
            // المرحلة 1: جلب البيانات من Google Maps
            // ==========================================
            $geocodeResult = $this->getGoogleGeocode($latitude, $longitude);

            if (!$geocodeResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في الحصول على معلومات الموقع من الخريطة'
                ], 400);
            }

            $addressComponents = $geocodeResult['data'];

            // Extract location details (English)
            $cityName = $addressComponents['city'] ?? null;
            $stateName = $addressComponents['state'] ?? null;
            $countryName = $addressComponents['country'] ?? null;
            $countryCode = $addressComponents['country_code'] ?? null;

            // Arabic names
            $cityNameAr = $addressComponents['city_ar'] ?? $cityName;
            $stateNameAr = $addressComponents['state_ar'] ?? $stateName;
            $countryNameAr = $addressComponents['country_ar'] ?? $countryName;

            // Addresses
            $formattedAddress = $geocodeResult['formatted_address'] ?? '';
            $formattedAddressEn = $geocodeResult['formatted_address_en'] ?? '';

            if (!$cityName) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على اسم المدينة في الموقع المحدد'
                ], 400);
            }

            Log::info('Geocoding: Data from Google Maps', [
                'en' => compact('cityName', 'stateName', 'countryName'),
                'ar' => ['city' => $cityNameAr, 'state' => $stateNameAr, 'country' => $countryNameAr]
            ]);

            // ==========================================
            // المرحلة 2: التحقق من Tryoto API
            // City → State → Country
            // ==========================================
            $resolution = $this->tryotoLocationService->resolveMapCity(
                $cityName,
                $stateName,
                $countryName,
                $latitude,
                $longitude
            );

            if (!$resolution['success']) {
                Log::warning('Geocoding: Location not supported by Tryoto', [
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                    'message' => $resolution['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $resolution['message'],
                    'data' => [
                        'original' => [
                            'city' => $cityName,
                            'state' => $stateName,
                            'country' => $countryName
                        ],
                        'coordinates' => compact('latitude', 'longitude')
                    ]
                ], 400);
            }

            // ==========================================
            // المرحلة 3: حفظ البيانات في الجداول كـ Cache
            // ==========================================
            $resolvedName = $resolution['resolved_name'];
            $resolvedType = $resolution['resolved_type'];

            // Determine Arabic name based on resolution type
            $resolvedNameAr = match ($resolvedType) {
                'city' => $cityNameAr,
                'state' => $stateNameAr,
                'country' => $countryNameAr,
                default => $resolvedName
            };

            // 1) Create/Get Country
            $country = $this->getOrCreateCountry(
                $countryCode,
                $countryName,
                $countryNameAr
            );

            // 2) Create/Get State
            $state = $this->getOrCreateState(
                $country->id,
                $stateName,
                $stateNameAr
            );

            // 3) Create/Get City (using the resolved name from Tryoto)
            $city = $this->getOrCreateCity(
                $country->id,
                $state?->id,
                $resolvedName,
                $resolvedNameAr,
                $latitude,
                $longitude
            );

            // ==========================================
            // إعداد الـ Response
            // ==========================================
            $response = [
                'success' => true,
                'message' => $resolution['message'],
                'data' => [
                    'country' => [
                        'id' => $country->id,
                        'name' => $country->country_name,
                        'name_ar' => $country->country_name_ar,
                        'code' => $country->country_code
                    ],
                    'state' => $state ? [
                        'id' => $state->id,
                        'name' => $state->state,
                        'name_ar' => $state->state_ar
                    ] : null,
                    'city' => [
                        'id' => $city->id,
                        'name' => $city->city_name,
                        'name_ar' => $city->city_name_ar
                    ],
                    'coordinates' => compact('latitude', 'longitude'),
                    'address' => [
                        'en' => $formattedAddressEn,
                        'ar' => $formattedAddress
                    ],
                    'resolution_info' => [
                        'strategy' => $resolution['strategy'],
                        'resolved_type' => $resolvedType,
                        'resolved_name' => $resolvedName,
                        'original' => $resolution['original'],
                        'shipping_companies' => $resolution['companies'] ?? 0,
                        'tryoto_region' => $resolution['region'] ?? null
                    ]
                ]
            ];

            // Add warning if using fallback (state or country)
            if ($resolution['strategy'] !== 'exact_city') {
                $response['warning'] = $resolution['message'];
            }

            Log::info('Geocoding: Completed successfully', [
                'strategy' => $resolution['strategy'],
                'resolved' => $resolvedName,
                'original_city' => $cityName
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Geocoding: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الموقع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create country in database (Cache)
     */
    protected function getOrCreateCountry(string $countryCode, string $countryName, ?string $countryNameAr): Country
    {
        $country = Country::where('country_code', $countryCode)
            ->orWhere('country_name', $countryName)
            ->first();

        if (!$country) {
            $country = Country::create([
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'country_name_ar' => $countryNameAr ?? $countryName,
                'tax' => 0,
                'status' => 1
            ]);

            Log::info('Geocoding: New country cached', [
                'name' => $countryName,
                'code' => $countryCode
            ]);
        }

        return $country;
    }

    /**
     * Get or create state in database (Cache)
     */
    protected function getOrCreateState(int $countryId, ?string $stateName, ?string $stateNameAr): ?State
    {
        if (!$stateName) {
            return null;
        }

        $state = State::where('country_id', $countryId)
            ->where(function ($query) use ($stateName, $stateNameAr) {
                $query->where('state', $stateName)
                    ->orWhere('state_ar', $stateNameAr);
            })
            ->first();

        if (!$state) {
            $state = State::create([
                'country_id' => $countryId,
                'state' => $stateName,
                'state_ar' => $stateNameAr ?? $stateName,
                'status' => 1,
                'tax' => 0,
                'owner_id' => 0
            ]);

            Log::info('Geocoding: New state cached', [
                'state' => $stateName
            ]);
        }

        return $state;
    }

    /**
     * Get or create city in database (Cache)
     */
    protected function getOrCreateCity(
        int $countryId,
        ?int $stateId,
        string $cityName,
        ?string $cityNameAr,
        float $latitude,
        float $longitude
    ): City {
        $city = City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();

        if (!$city) {
            $city = City::create([
                'state_id' => $stateId,
                'country_id' => $countryId,
                'city_name' => $cityName,
                'city_name_ar' => $cityNameAr ?? $cityName,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 1
            ]);

            Log::info('Geocoding: New city cached (verified by Tryoto API)', [
                'city' => $cityName
            ]);
        } elseif (!$city->latitude || !$city->longitude) {
            $city->update([
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        }

        return $city;
    }

    /**
     * Get geocoding data from Google Maps API in BOTH languages
     */
    protected function getGoogleGeocode($latitude, $longitude)
    {
        try {
            $apiKey = config('services.google_maps.api_key');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'Google Maps API key not configured'
                ];
            }

            // Request 1: English Names
            $responseEn = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey,
                'language' => 'en'
            ]);

            if (!$responseEn->successful()) {
                return ['success' => false, 'message' => 'Google Maps API request failed (EN)'];
            }

            $dataEn = $responseEn->json();

            if ($dataEn['status'] !== 'OK' || empty($dataEn['results'])) {
                return ['success' => false, 'message' => 'No results found (EN)'];
            }

            // Request 2: Arabic Names
            $responseAr = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey,
                'language' => 'ar'
            ]);

            if (!$responseAr->successful()) {
                return ['success' => false, 'message' => 'Google Maps API request failed (AR)'];
            }

            $dataAr = $responseAr->json();

            if ($dataAr['status'] !== 'OK' || empty($dataAr['results'])) {
                return ['success' => false, 'message' => 'No results found (AR)'];
            }

            // Extract Components from BOTH languages
            $componentsEn = $this->extractAddressComponents($dataEn['results'][0]);
            $componentsAr = $this->extractAddressComponents($dataAr['results'][0]);

            // Merge: EN and AR
            $components = [
                'city' => $componentsEn['city'] ?? null,
                'state' => $componentsEn['administrative_area_level_1'] ?? null,
                'country' => $componentsEn['country'] ?? null,
                'country_code' => $componentsEn['country_code'] ?? null,
                'city_ar' => $componentsAr['city'] ?? null,
                'state_ar' => $componentsAr['administrative_area_level_1'] ?? null,
                'country_ar' => $componentsAr['country'] ?? null,
                'postal_code' => $componentsEn['postal_code'] ?? null,
            ];

            return [
                'success' => true,
                'data' => $components,
                'formatted_address' => $dataAr['results'][0]['formatted_address'],
                'formatted_address_en' => $dataEn['results'][0]['formatted_address']
            ];

        } catch (\Exception $e) {
            Log::error('Google Geocode API error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract address components from Google Maps result
     */
    protected function extractAddressComponents($result)
    {
        $components = [];

        foreach ($result['address_components'] as $component) {
            $types = $component['types'];

            if (in_array('locality', $types)) {
                $components['city'] = $component['long_name'];
            }

            if (in_array('administrative_area_level_2', $types) && !isset($components['city'])) {
                $components['city'] = $component['long_name'];
            }

            if (in_array('administrative_area_level_1', $types)) {
                $components['administrative_area_level_1'] = $component['long_name'];
            }

            if (in_array('country', $types)) {
                $components['country'] = $component['long_name'];
                $components['country_code'] = $component['short_name'];
            }

            if (in_array('postal_code', $types)) {
                $components['postal_code'] = $component['long_name'];
            }
        }

        return $components;
    }

    /**
     * Search for cities (from database cache)
     */
    public function searchCities(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->query;

        $cities = City::where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('city_name', 'like', "%{$query}%")
                    ->orWhere('city_name_ar', 'like', "%{$query}%");
            })
            ->with(['country:id,country_name,country_name_ar', 'state:id,state,state_ar'])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->city_name,
                    'name_ar' => $city->city_name_ar,
                    'state' => $city->state ? [
                        'id' => $city->state->id,
                        'name' => $city->state->state,
                        'name_ar' => $city->state->state_ar
                    ] : null,
                    'country' => [
                        'id' => $city->country->id,
                        'name' => $city->country->country_name,
                        'name_ar' => $city->country->country_name_ar
                    ],
                    'coordinates' => [
                        'lat' => $city->latitude,
                        'lng' => $city->longitude
                    ]
                ];
            })
        ]);
    }
}
