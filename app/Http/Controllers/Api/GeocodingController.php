<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\TryotoLocationService;
use App\Services\TryotoService;
use App\Services\CountrySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeocodingController - Location Resolution via Google Maps + Tryoto API
 *
 * السياسة الجديدة (Enterprise-Level):
 * 1. عند اختيار موقع، نتحقق إذا الدولة موجودة و is_synced = 1
 * 2. إذا لم تكن مزامنة: نجلب كل المدن من Tryoto + الإحداثيات من Google
 * 3. بعد المزامنة: كل شيء من DB فقط - لا API calls
 *
 * هذه الطريقة مستخدمة في: Uber, Talabat, Amazon
 */
class GeocodingController extends Controller
{
    protected TryotoLocationService $tryotoLocationService;
    protected TryotoService $tryotoService;
    protected CountrySyncService $countrySyncService;

    public function __construct(
        TryotoLocationService $tryotoLocationService,
        TryotoService $tryotoService,
        CountrySyncService $countrySyncService
    ) {
        $this->tryotoLocationService = $tryotoLocationService;
        $this->tryotoService = $tryotoService;
        $this->countrySyncService = $countrySyncService;
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

            Log::info('Geocoding: Data from Google Maps', [
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
                // السياسة الجديدة: إنشاء الدولة ومزامنة المدن تلقائياً
                // ==========================================
                Log::info('Geocoding: Country needs sync - starting auto sync', [
                    'country' => $countryName,
                    'reason' => $syncStatus['reason']
                ]);

                // إنشاء الدولة إذا غير موجودة
                if ($syncStatus['reason'] === 'country_not_exists') {
                    $country = $this->createCountryFromGoogle(
                        $countryCode,
                        $countryName,
                        $countryNameAr
                    );

                    Log::info('Geocoding: Created new country from Google Maps', [
                        'country' => $countryName,
                        'country_ar' => $countryNameAr,
                        'code' => $countryCode,
                        'country_id' => $country->id
                    ]);
                } else {
                    $country = $syncStatus['country'];
                }

                // مزامنة المدن تلقائياً (سريعة بدون Google geocoding)
                $syncResult = $this->countrySyncService->syncCountry(
                    $countryName,
                    $countryCode,
                    null,
                    $countryNameAr
                );

                Log::info('Geocoding: Auto sync completed', [
                    'country' => $countryName,
                    'success' => $syncResult['success'],
                    'cities_count' => $syncResult['cities_count'] ?? 0
                ]);

                // إذا فشلت المزامنة، نرجع خطأ
                if (!$syncResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $syncResult['message'] ?? 'فشل في مزامنة المدن',
                        'country_id' => $country->id ?? null,
                    ], 400);
                }

                // تحديث الـ country من DB
                $country = Country::find($country->id);

                // الآن نكمل البحث عن المدينة
                $syncStatus = [
                    'needs_sync' => false,
                    'country' => $country,
                    'reason' => 'just_synced'
                ];
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
            $state = $city && $city->state_id ? State::find($city->state_id) : null;

            // إذا لم نجد state في DB، نستخدم اسم الولاية من Google Maps
            $stateData = null;
            if ($state) {
                $stateData = [
                    'id' => $state->id,
                    'name' => $state->state,
                    'name_ar' => $state->state_ar ?: $stateNameAr
                ];
            } elseif ($stateName) {
                // Fallback: استخدام اسم الولاية من Google Maps مع id=0
                $stateData = [
                    'id' => 0,
                    'name' => $stateName,
                    'name_ar' => $stateNameAr ?: $stateName
                ];
            }

            $response = [
                'success' => true,
                'needs_sync' => false,
                'message' => $resolution['message'],
                'data' => [
                    'country' => [
                        'id' => $country->id,
                        'name' => $country->country_name,
                        'name_ar' => $country->country_name_ar,
                        'code' => $country->country_code
                    ],
                    'state' => $stateData,
                    'city' => [
                        'id' => $city->id,
                        'name' => $city->city_name,
                        'name_ar' => $city->city_name_ar,
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

            Log::info('Geocoding: Completed successfully', [
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
     * السياسة الجديدة:
     * - يستقبل الاسم العربي من Google Maps في الـ request
     * - يمرره إلى syncCountry لحفظه مباشرة
     */
    public function startCountrySync(Request $request)
    {
        $request->validate([
            'country_name' => 'required|string',
            'country_code' => 'required|string|max:2',
            'country_name_ar' => 'nullable|string', // الاسم العربي من Google Maps
        ]);

        $countryName = $request->country_name;
        $countryCode = strtoupper($request->country_code);
        $countryNameAr = $request->country_name_ar; // من Google Maps
        $sessionId = uniqid('sync_');

        Log::info('GeocodingController: Starting country sync', [
            'country' => $countryName,
            'country_ar' => $countryNameAr,
            'code' => $countryCode,
            'session' => $sessionId
        ]);

        // بدء المزامنة مع الاسم العربي
        $result = $this->countrySyncService->syncCountry($countryName, $countryCode, $sessionId, $countryNameAr);

        return response()->json([
            'success' => $result['success'],
            'session_id' => $sessionId,
            'message' => $result['message'],
            'country_id' => $result['country_id'] ?? null,
            'cities_count' => $result['cities_count'] ?? 0,
            'states_count' => $result['states_count'] ?? 0
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
     * إنشاء دولة جديدة من بيانات Google Maps
     *
     * السياسة:
     * - تُنشأ الدولة فوراً عند أول طلب من الخريطة
     * - is_synced = 0 (تحتاج مزامنة المدن من Tryoto)
     * - synced_at = NULL
     * - الأسماء EN و AR من Google Maps
     */
    protected function createCountryFromGoogle(string $countryCode, string $countryName, ?string $countryNameAr): Country
    {
        // التحقق من وجود الدولة أولاً (بالكود أو الاسم)
        $existingCountry = Country::where('country_code', strtoupper($countryCode))
            ->orWhere('country_name', $countryName)
            ->orWhere('country_name_ar', $countryNameAr)
            ->first();

        if ($existingCountry) {
            // تحديث البيانات الناقصة فقط
            $updates = [];

            if (empty($existingCountry->country_name_ar) && $countryNameAr) {
                $updates['country_name_ar'] = $countryNameAr;
            }

            if (empty($existingCountry->country_code) && $countryCode) {
                $updates['country_code'] = strtoupper($countryCode);
            }

            if (!empty($updates)) {
                $existingCountry->update($updates);
            }

            return $existingCountry;
        }

        // إنشاء دولة جديدة
        return Country::create([
            'country_code' => strtoupper($countryCode),
            'country_name' => $countryName,
            'country_name_ar' => $countryNameAr ?: $countryName,
            'tax' => 0,
            'status' => 1,
            'is_synced' => 0,      // تحتاج مزامنة المدن
            'synced_at' => null,   // لم تتم المزامنة بعد
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
            ->with(['country:id,country_name,country_name_ar,country_code', 'state:id,state,state_ar'])
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
                    Log::info('Geocoding: Using random city from synced country (no coordinates available)', [
                        'city' => $anyCity->city_name,
                        'country' => $syncedCountry->country_name,
                        'user_coordinates' => compact('latitude', 'longitude')
                    ]);

                    return [
                        'country' => [
                            'id' => $syncedCountry->id,
                            'name' => $syncedCountry->country_name,
                            'name_ar' => $syncedCountry->country_name_ar,
                            'code' => $syncedCountry->country_code
                        ],
                        'city' => [
                            'id' => $anyCity->id,
                            'name' => $anyCity->city_name,
                            'name_ar' => $anyCity->city_name_ar,
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

        Log::info('Geocoding: Found nearest city globally', [
            'city' => $city->city_name,
            'country' => $city->country?->country_name,
            'distance_km' => round($city->distance_km, 2)
        ]);

        return [
            'country' => [
                'id' => $city->country->id,
                'name' => $city->country->country_name,
                'name_ar' => $city->country->country_name_ar,
                'code' => $city->country->country_code
            ],
            'state' => $city->state ? [
                'id' => $city->state->id,
                'name' => $city->state->state,
                'name_ar' => $city->state->state_ar
            ] : null,
            'city' => [
                'id' => $city->id,
                'name' => $city->city_name,
                'name_ar' => $city->city_name_ar,
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
