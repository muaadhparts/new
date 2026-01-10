<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Services\TryotoLocationService;
use App\Services\TryotoService;
use App\Services\CountrySyncService;
use App\Services\ApiCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeocodingController - Location Resolution via Google Maps + Tryoto API
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن (يتم الاستيراد يدوياً عبر CLI)
 * - هذا الـ controller READ-ONLY - لا يُنشئ مدن جديدة
 * - البيانات: city_name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
 * - لا يوجد اسم عربي للمدن (city_name_ar محذوف)
 * - يبحث عن أقرب مدينة في DB ويستخدمها للشحن
 */
class GeocodingController extends Controller
{
    protected TryotoLocationService $tryotoLocationService;
    protected TryotoService $tryotoService;
    protected CountrySyncService $countrySyncService;
    protected ApiCredentialService $credentialService;

    public function __construct(
        TryotoLocationService $tryotoLocationService,
        TryotoService $tryotoService,
        CountrySyncService $countrySyncService,
        ApiCredentialService $credentialService
    ) {
        $this->tryotoLocationService = $tryotoLocationService;
        $this->tryotoService = $tryotoService;
        $this->countrySyncService = $countrySyncService;
        $this->credentialService = $credentialService;
    }

    /**
     * Reverse geocode coordinates to address with Tryoto verification
     * يتحقق من المزامنة ويرسل needs_sync إذا الدولة غير مزامنة
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

        Log::debug('Geocoding: Starting reverse geocode', compact('latitude', 'longitude'));

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

            Log::debug('Geocoding: Data from Google Maps', [
                'en' => compact('cityName', 'stateName', 'countryName'),
                'ar' => ['city' => $cityNameAr, 'state' => $stateNameAr, 'country' => $countryNameAr]
            ]);

            // ==========================================
            // المرحلة 2: التحقق من حالة مزامنة الدولة
            // ==========================================
            if (!$countryName) {
                // Google لم يُرجع معلومات الدولة
                // نحاول البحث عن أقرب مدينة بالإحداثيات في أي دولة مزامنة
                Log::warning('Geocoding: Google returned no country, trying nearest city', [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);

                $nearestResult = $this->findNearestCityGlobally($latitude, $longitude);

                if ($nearestResult) {
                    return response()->json([
                        'success' => true,
                        'needs_sync' => false,
                        'message' => "تم تحديد أقرب منطقة مدعومة: {$nearestResult['city_name']}",
                        'data' => $nearestResult,
                        'warning' => 'تم استخدام أقرب منطقة مدعومة بناءً على الإحداثيات'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم التعرف على الموقع. يرجى اختيار موقع آخر على الخريطة.',
                    'suggestion' => 'حاول اختيار موقع داخل منطقة سكنية أو تجارية'
                ], 400);
            }

            $syncStatus = $this->countrySyncService->needsSyncByName($countryName);

            if ($syncStatus['needs_sync']) {
                // ==========================================
                // الدولة غير متزامنة - نبحث عن أقرب مدينة مدعومة
                // المزامنة تتم يدوياً فقط عبر: php artisan tryoto:sync-cities
                // ==========================================
                Log::debug('Geocoding: Country not synced, trying nearest city', [
                    'country' => $countryName,
                    'reason' => $syncStatus['reason']
                ]);

                // نحاول البحث عن أقرب مدينة بالإحداثيات في أي دولة مزامنة
                $nearestResult = $this->findNearestCityGlobally($latitude, $longitude);

                if ($nearestResult) {
                    return response()->json([
                        'success' => true,
                        'needs_sync' => false,
                        'message' => "تم تحديد أقرب منطقة مدعومة: {$nearestResult['city_name']}",
                        'data' => $nearestResult,
                        'warning' => 'هذه الدولة غير مدعومة حالياً، تم اختيار أقرب منطقة مدعومة'
                    ]);
                }

                // لا توجد مدن مدعومة
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، هذه المنطقة غير مدعومة حالياً للتوصيل.',
                    'country' => $countryName,
                    'suggestion' => 'يرجى اختيار موقع في منطقة مدعومة'
                ], 400);
            }

            // ==========================================
            // المرحلة 3: الدولة مزامنة - استخدام DB فقط
            // ==========================================
            $country = $syncStatus['country'];

            $resolution = $this->tryotoLocationService->resolveMapCity(
                $cityName ?? '',
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
                    'needs_sync' => false,
                    'message' => $resolution['message'],
                    'data' => [
                        'original' => [
                            'city' => $cityName,
                            'city_ar' => $cityNameAr,
                            'state' => $stateName,
                            'state_ar' => $stateNameAr,
                            'country' => $countryName,
                            'country_ar' => $countryNameAr
                        ],
                        'coordinates' => compact('latitude', 'longitude'),
                        'address' => [
                            'en' => $formattedAddressEn,
                            'ar' => $formattedAddress
                        ]
                    ]
                ], 400);
            }

            // ==========================================
            // المرحلة 4: نجاح - جلب البيانات من DB
            // ==========================================
            $city = City::find($resolution['city_id']);

            $response = [
                'success' => true,
                'needs_sync' => false,
                'message' => $resolution['message'],
                'data' => [
                    'country' => [
                        'id' => $country->id,
                        'name' => $country->country_name,
                        'code' => $country->country_code
                    ],
                    'city' => [
                        'id' => $city->id,
                        'name' => $city->city_name,
                        'tryoto_name' => $resolution['tryoto_name']
                    ],
                    'coordinates' => $resolution['coordinates'],
                    'address' => [
                        'en' => $formattedAddressEn,
                        'ar' => $formattedAddress
                    ],
                    'resolution_info' => [
                        'strategy' => $resolution['strategy'],
                        'original' => $resolution['original'] ?? compact('cityName', 'stateName', 'countryName'),
                    ]
                ]
            ];

            // إضافة معلومات المسافة إذا تم استخدام nearest_city
            if (isset($resolution['distance_km'])) {
                $response['data']['resolution_info']['distance_km'] = $resolution['distance_km'];
                $response['warning'] = $resolution['message'];
            }

            Log::debug('Geocoding: Completed successfully', [
                'strategy' => $resolution['strategy'],
                'resolved' => $resolution['resolved_name'],
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
     * بدء مزامنة دولة
     *
     * ملاحظة: المزامنة الكاملة تتم عبر CLI:
     * php artisan tryoto:sync-cities --country=SA
     */
    public function startCountrySync(Request $request)
    {
        $request->validate([
            'country_name' => 'required|string',
            'country_code' => 'required|string|max:2',
        ]);

        $countryName = $request->country_name;
        $countryCode = strtoupper($request->country_code);
        $sessionId = uniqid('sync_');

        Log::debug('GeocodingController: Starting country sync', [
            'country' => $countryName,
            'code' => $countryCode,
            'session' => $sessionId
        ]);

        // بدء المزامنة
        $result = $this->countrySyncService->syncCountry($countryName, $countryCode, $sessionId);

        return response()->json([
            'success' => $result['success'],
            'session_id' => $sessionId,
            'message' => $result['message'],
            'country_id' => $result['country_id'] ?? null,
            'cities_count' => $result['cities_count'] ?? 0
        ]);
    }

    /**
     * الحصول على حالة تقدم المزامنة
     */
    public function getSyncProgress(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $progress = CountrySyncService::getProgress($request->session_id);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * البحث عن أقرب مدينة مدعومة في أي دولة
     *
     * تُستخدم عندما Google لا يُرجع معلومات الموقع
     * أو عندما المدينة غير موجودة في أي دولة
     *
     * @param float $latitude خط العرض
     * @param float $longitude خط الطول
     * @param float $maxDistanceKm أقصى مسافة للبحث (افتراضي 100 كم)
     * @return array|null
     */
    protected function findNearestCityGlobally(float $latitude, float $longitude, float $maxDistanceKm = 100): ?array
    {
        // صيغة Haversine لحساب المسافة بالكيلومتر
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        ))";

        // أولاً: البحث في المدن التي لديها إحداثيات
        $city = City::where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("cities.*, {$haversine} as distance_km", [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $maxDistanceKm)
            ->orderBy('distance_km', 'asc')
            ->with(['country:id,country_name,country_name_ar,country_code'])
            ->first();

        if (!$city) {
            // لا توجد مدينة قريبة لها إحداثيات
            // نحاول البحث في أقرب دولة مزامنة وإرجاع مدينة عشوائية
            // (حالة المدن بدون إحداثيات)
            $syncedCountry = Country::where('is_synced', 1)->first();

            if ($syncedCountry) {
                // البحث عن مدينة بنفس المنطقة الجغرافية التقريبية
                // نستخدم أول حرف من الاسم كتقريب (غير دقيق لكنه أفضل من لا شيء)
                $anyCity = City::where('country_id', $syncedCountry->id)
                    ->where('tryoto_supported', 1)
                    ->with(['country:id,country_name,country_name_ar,country_code'])
                    ->inRandomOrder()
                    ->first();

                if ($anyCity) {
                    Log::debug('Geocoding: Using random city from synced country (no coordinates available)', [
                        'city' => $anyCity->city_name,
                        'country' => $syncedCountry->country_name,
                        'user_coordinates' => compact('latitude', 'longitude')
                    ]);

                    return [
                        'country' => [
                            'id' => $syncedCountry->id,
                            'name' => $syncedCountry->country_name,
                            'code' => $syncedCountry->country_code
                        ],
                        'city' => [
                            'id' => $anyCity->id,
                            'name' => $anyCity->city_name,
                        ],
                        'city_name' => $anyCity->city_name,
                        'coordinates' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude
                        ],
                        'resolution_info' => [
                            'strategy' => 'fallback_random_city',
                            'note' => 'Google did not return location data, no cities have coordinates',
                            'warning' => 'يرجى التأكد من اختيار المدينة الصحيحة يدوياً'
                        ]
                    ];
                }
            }

            return null;
        }

        Log::debug('Geocoding: Found nearest city globally', [
            'city' => $city->city_name,
            'country' => $city->country?->country_name,
            'distance_km' => round($city->distance_km, 2)
        ]);

        return [
            'country' => [
                'id' => $city->country->id,
                'name' => $city->country->country_name,
                'code' => $city->country->country_code
            ],
            'city' => [
                'id' => $city->id,
                'name' => $city->city_name,
                'tryoto_name' => $city->city_name
            ],
            'city_name' => $city->city_name,
            'coordinates' => [
                'latitude' => $city->latitude ?? $latitude,
                'longitude' => $city->longitude ?? $longitude
            ],
            'distance_km' => round($city->distance_km, 2),
            'resolution_info' => [
                'strategy' => 'nearest_city_globally',
                'original_coordinates' => compact('latitude', 'longitude')
            ]
        ];
    }

    /**
     * البحث عن دولة في قاعدة البيانات (read-only)
     * ملاحظة: لا يتم إنشاء دول جديدة - المزامنة تتم عبر CLI فقط
     */
    protected function findCountry(string $countryCode, string $countryName): ?Country
    {
        return Country::where('country_code', $countryCode)
            ->orWhere('country_name', $countryName)
            ->first();
    }

    /**
     * البحث عن مدينة في قاعدة البيانات (read-only)
     * ملاحظة: لا يتم إنشاء مدن جديدة - المزامنة تتم عبر CLI فقط
     * البيانات: city_name (إنجليزي فقط) - لا يوجد city_name_ar
     */
    protected function findCity(int $countryId, string $cityName): ?City
    {
        return City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();
    }

    /**
     * Get geocoding data from Google Maps API in BOTH languages
     */
    protected function getGoogleGeocode($latitude, $longitude)
    {
        try {
            $apiKey = $this->credentialService->getGoogleMapsKey();

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'Google Maps API key not configured. Add it via Operator Panel > API Credentials.'
                ];
            }

            // Request 1: English Names (with retry)
            $responseEn = Http::timeout(30)->retry(3, 1000)->get('https://maps.googleapis.com/maps/api/geocode/json', [
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

            // Request 2: Arabic Names (with retry)
            $responseAr = Http::timeout(30)->retry(3, 1000)->get('https://maps.googleapis.com/maps/api/geocode/json', [
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
     * البحث عن مدن (read-only من قاعدة البيانات)
     * ملاحظة: البحث بالاسم الإنجليزي فقط - لا يوجد city_name_ar
     */
    public function searchCities(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->query;

        $cities = City::where('status', 1)
            ->where('city_name', 'like', "%{$query}%")
            ->with(['country:id,country_name,country_code'])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->city_name,
                    'country' => [
                        'id' => $city->country->id,
                        'name' => $city->country->country_name,
                        'code' => $city->country->country_code
                    ],
                    'coordinates' => [
                        'lat' => $city->latitude,
                        'lng' => $city->longitude
                    ]
                ];
            })
        ]);
    }

    /**
     * Get cities by country ID
     * Legacy route support
     */
    public function getCitiesByCountry(Request $request, ?int $country_id = null)
    {
        $countryId = $country_id ?? $request->input('country_id');

        if (!$countryId) {
            return response()->json([]);
        }

        $cities = City::where('status', 1)
            ->where('country_id', $countryId)
            ->orderBy('city_name')
            ->get(['id', 'city_name']);

        return response()->json($cities);
    }
}
