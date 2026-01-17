<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\City;
use App\Services\GoogleMapsService;
use App\Services\TryotoLocationService;
use App\Services\CountrySyncService;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * GeocodingController - Location Resolution (READ-ONLY)
 * Tryoto cities are imported via CLI command.
 */
class GeocodingController extends Controller
{
    protected $googleMapsService;
    protected $tryotoService;
    protected $countrySyncService;
    protected $cartManager;

    public function __construct(
        GoogleMapsService $googleMapsService,
        TryotoLocationService $tryotoService,
        CountrySyncService $countrySyncService,
        MerchantCartManager $cartManager
    ) {
        $this->googleMapsService = $googleMapsService;
        $this->cartManager = $cartManager;
        $this->tryotoService = $tryotoService;
        $this->countrySyncService = $countrySyncService;
    }

    /**
     * Reverse geocode coordinates to location data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Get bilingual location data
        $locationData = $this->googleMapsService->getBilingualNames($latitude, $longitude);

        if (!$locationData['success']) {
            return response()->json([
                'success' => false,
                'error' => $locationData['error']
            ], 400);
        }

        $arabicData = $locationData['data']['ar'];
        $englishData = $locationData['data']['en'];

        // Process and store location data in database
        try {
            DB::beginTransaction();

            $result = $this->processLocationData($arabicData, $englishData, $latitude, $longitude);

            // NEW: Integrate Tryoto verification
            $tryotoResult = $this->verifyWithTryoto($result, $latitude, $longitude);

            // Merge Tryoto data with result
            $result = array_merge($result, $tryotoResult);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing location data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process location data'
            ], 500);
        }
    }

    /**
     * Verify location with Tryoto and find alternative if needed
     *
     * @param array $locationData
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    protected function verifyWithTryoto($locationData, $latitude, $longitude)
    {
        $cityName = $locationData['city']['name'] ?? null;
        $countryId = $locationData['country']['id'] ?? null;

        if (!$cityName) {
            return [
                'tryoto_verified' => false,
                'tryoto_message' => 'City name not available'
            ];
        }

        // Use smart city resolution
        $resolution = $this->tryotoService->resolveMapCity($cityName, $latitude, $longitude, $countryId);

        if (!$resolution['verified']) {
            return [
                'tryoto_verified' => false,
                'tryoto_strategy' => $resolution['strategy'],
                'tryoto_message' => $resolution['message']
            ];
        }

        // If alternative city was found
        if ($resolution['strategy'] === 'nearest_city') {
            $alternativeCity = $this->findAlternativeCity($resolution, $countryId);

            if (!$alternativeCity) {
                return [
                    'tryoto_verified' => false,
                    'tryoto_strategy' => $resolution['strategy'],
                    'tryoto_message' => 'المدينة البديلة غير موجودة في قاعدة البيانات'
                ];
            }

            return [
                'tryoto_verified' => true,
                'tryoto_strategy' => $resolution['strategy'],
                'tryoto_message' => $resolution['message'],
                'alternative_city' => [
                    'id' => $alternativeCity->id,
                    'name' => $alternativeCity->city_name,
                    'distance_km' => $resolution['distance_km']
                ],
                'original_city' => [
                    'name' => $cityName,
                    'coordinates' => [
                        'lat' => $latitude,
                        'lng' => $longitude
                    ]
                ],
                'suggested_coordinates' => $resolution['coordinates'] ?? null,
                'shipping_companies' => $resolution['companies'] ?? 0
            ];
        }

        // Exact match or name variation
        return [
            'tryoto_verified' => true,
            'tryoto_strategy' => $resolution['strategy'],
            'tryoto_message' => $resolution['message'],
            'shipping_companies' => $resolution['companies'] ?? 0
        ];
    }

    /**
     * Find alternative city in database (read-only lookup).
     */
    protected function findAlternativeCity($resolution, $countryId): ?City
    {
        $cityName = $resolution['city_name'];

        return City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();
    }

    /**
     * Process location data (read-only lookup).
     */
    protected function processLocationData($arabicData, $englishData, $latitude, $longitude)
    {
        // Extract country code (prioritize English data for country code)
        $countryCode = $englishData['country_code'] ?? $arabicData['country_code'] ?? null;

        if (!$countryCode) {
            throw new \Exception('Country code not found in geocoding results');
        }

        // Country names
        $countryNameEn = $englishData['country'] ?? null;

        $cityNameEn = $englishData['city'] ?? null;
        $addressEn = $englishData['address'] ?? null;
        $addressAr = $arabicData['address'] ?? $addressEn;

        $country = Country::where('country_code', $countryCode)->first();

        if (!$country) {
            throw new \Exception("Country not found: {$countryCode}. Run: php artisan tryoto:sync-cities");
        }

        $city = null;
        if ($cityNameEn) {
            $city = City::where('country_id', $country->id)
                ->where('city_name', $cityNameEn)
                ->first();
        }

        return [
            'country' => [
                'id' => $country->id,
                'code' => $country->country_code,
                'name' => $country->country_name,
            ],
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->city_name,
            ] : null,
            'address' => [
                'en' => $addressEn,
                'ar' => $addressAr
            ],
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]
        ];
    }

    /**
     * Get all countries
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCountries()
    {
        $countries = Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name']);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get cities by country.
     */
    public function getCitiesByCountry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cities = City::where('country_id', $request->country_id)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'country_id']);

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    /**
     * Check if country needs sync.
     */
    public function checkCountrySync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_name' => 'required|string',
            'country_code' => 'nullable|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $countryName = $request->country_name;
        $countryCode = $request->country_code;

        // التحقق من حالة المزامنة
        $syncStatus = $this->countrySyncService->needsSyncByName($countryName);

        if (!$syncStatus['needs_sync']) {
            return response()->json([
                'success' => true,
                'needs_sync' => false,
                'country_id' => $syncStatus['country']->id,
                'message' => 'الدولة مزامنة بالفعل'
            ]);
        }

        // تحديد كود الدولة إذا لم يتم تمريره
        if (!$countryCode) {
            $countryCode = $this->countrySyncService->getCountryCode($countryName);
        }

        return response()->json([
            'success' => true,
            'needs_sync' => true,
            'country_name' => $countryName,
            'country_code' => $countryCode,
            'reason' => $syncStatus['reason'],
            'message' => 'الدولة تحتاج مزامنة. سيتم استيراد المدن المدعومة من شركة الشحن.'
        ]);
    }

    /**
     * بدء مزامنة دولة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startCountrySync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_name' => 'required|string',
            'country_code' => 'required|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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
            'cities_count' => $result['cities_count'] ?? 0,
            'states_count' => $result['states_count'] ?? 0
        ]);
    }

    /**
     * الحصول على حالة تقدم المزامنة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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
     * Reverse geocode مع التحقق من المزامنة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocodeWithSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        Log::debug('Geocoding: Starting reverse geocode', compact('latitude', 'longitude'));

        // Get bilingual location data from Google
        $locationData = $this->googleMapsService->getBilingualNames($latitude, $longitude);

        if (!$locationData['success']) {
            return response()->json([
                'success' => false,
                'error' => $locationData['error']
            ], 400);
        }

        $arabicData = $locationData['data']['ar'];
        $englishData = $locationData['data']['en'];

        Log::debug('Geocoding: Data from Google Maps', [
            'en' => [
                'cityName' => $englishData['city'] ?? null,
                'stateName' => $englishData['state'] ?? null,
                'countryName' => $englishData['country'] ?? null
            ],
            'ar' => $arabicData
        ]);

        // Return raw Google data (validation happens in Step 2)
        return response()->json([
            'success' => true,
            'capture_only' => true,
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'address_payload' => [
                'en' => $englishData,
                'ar' => $arabicData,
            ],
            // بنية متوافقة مع Frontend (للعرض فقط)
            'country' => [
                'name' => $englishData['country'] ?? null,
                'name_ar' => $arabicData['country'] ?? null,
            ],
            'state' => [
                'name' => $englishData['state'] ?? null,
                'name_ar' => $arabicData['state'] ?? null,
            ],
            'city' => [
                'name' => $englishData['city'] ?? null,
                'name_ar' => $arabicData['city'] ?? null,
            ],
            // العنوان الكامل للعرض
            'address' => [
                'en' => $englishData['address'] ?? null,
                'ar' => $arabicData['address'] ?? null,
            ],
            'postal_code' => $englishData['postal_code'] ?? $arabicData['postal_code'] ?? null,
            'message' => 'تم تحديد الموقع بنجاح'
        ]);
    }

    /**
     * تحديد الموقع من قاعدة البيانات (بعد المزامنة)
     * ملاحظة: read-only - لا يتم إنشاء أي بيانات
     */
    protected function resolveLocationFromDB($englishData, $arabicData, $latitude, $longitude, $country)
    {
        $cityName = $englishData['city'] ?? null;
        $stateName = $englishData['state'] ?? null;
        $countryName = $englishData['country'] ?? null;

        Log::debug('TryotoLocation: Resolving location from DB', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName,
            'coordinates' => compact('latitude', 'longitude')
        ]);

        // استخدام TryotoLocationService للبحث في DB
        $resolution = $this->tryotoService->resolveMapCity(
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
                'error' => $resolution['message'],
                'strategy' => $resolution['strategy'] ?? 'unknown',
                'google_data' => [
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                ],
                'coordinates' => compact('latitude', 'longitude')
            ]);
        }

        // نجاح - تم إيجاد موقع مدعوم
        $responseData = [
            'success' => true,
            'needs_sync' => false,
            'country' => [
                'id' => $country->id,
                'code' => $country->country_code,
                'name' => $country->country_name,
            ],
            'city' => [
                'id' => $resolution['city_id'],
                'name' => $resolution['resolved_name'],
                'tryoto_name' => $resolution['tryoto_name']
            ],
            'coordinates' => $resolution['coordinates'],
            'strategy' => $resolution['strategy'],
            'message' => $resolution['message'],
            'address' => [
                'en' => $englishData['address'] ?? null,
                'ar' => $arabicData['address'] ?? null,
            ]
        ];

        // إضافة معلومات المسافة إذا تم استخدام nearest_city
        if (isset($resolution['distance_km'])) {
            $responseData['distance_km'] = $resolution['distance_km'];
            $responseData['original_coordinates'] = $resolution['original_coordinates'] ?? compact('latitude', 'longitude');
        }

        return response()->json($responseData);
    }

    /**
     * Get tax info and formatted address from coordinates.
     * Used in Step 1 to calculate tax after map location selection.
     *
     * IMPORTANT: merchant_id is REQUIRED - no global cart operations
     */
    public function getTaxFromCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'merchant_id' => 'required|integer|min:1',
            'locale' => 'nullable|string|in:ar,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $locale = $request->input('locale', app()->getLocale());

        $countryName = null;
        $countryCode = null;
        $cityName = null;
        $stateName = null;
        $formattedAddress = null;
        $formattedAddressAr = null;
        $formattedAddressEn = null;
        $postalCode = null;
        $geocodingSuccess = false;

        // 1. Geocoding بالعربية
        try {
            $geocodeResultAr = $this->googleMapsService->reverseGeocode($latitude, $longitude, 'ar');

            if ($geocodeResultAr['success'] && !empty($geocodeResultAr['data'])) {
                $formattedAddressAr = $geocodeResultAr['data']['address'] ?? null;
                $postalCode = $geocodeResultAr['data']['postal_code'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('getTaxFromCoordinates: Arabic geocoding failed', [
                'lat' => $latitude,
                'lng' => $longitude,
                'error' => $e->getMessage()
            ]);
        }

        // 2. Geocoding بالإنجليزية للحصول على أسماء المدن (للمطابقة مع DB)
        try {
            $geocodeResult = $this->googleMapsService->reverseGeocode($latitude, $longitude, 'en');

            if ($geocodeResult['success'] && !empty($geocodeResult['data'])) {
                $countryName = $geocodeResult['data']['country'] ?? null;
                $countryCode = $geocodeResult['data']['country_code'] ?? null;
                $cityName = $geocodeResult['data']['city'] ?? null;
                $stateName = $geocodeResult['data']['state'] ?? null;
                $formattedAddressEn = $geocodeResult['data']['address'] ?? null;
                // Use English postal code if Arabic didn't have it
                if (!$postalCode) {
                    $postalCode = $geocodeResult['data']['postal_code'] ?? null;
                }
                $geocodingSuccess = true;
            }
        } catch (\Exception $e) {
            Log::warning('getTaxFromCoordinates: English geocoding failed', [
                'lat' => $latitude,
                'lng' => $longitude,
                'error' => $e->getMessage()
            ]);
        }

        // Select address based on requested locale
        $formattedAddress = ($locale === 'ar') ? ($formattedAddressAr ?? $formattedAddressEn) : ($formattedAddressEn ?? $formattedAddressAr);

        // 3. البحث عن الدولة في قاعدة البيانات
        $country = null;
        if ($countryName) {
            $country = Country::where('country_name', 'LIKE', '%' . $countryName . '%')
                ->orWhere('country_code', $countryCode ?? '')
                ->first();
        }

        // 3.5 البحث عن المدينة في قاعدة البيانات (للربط مع المناديب ونقاط الاستلام)
        // الأولوية: 1. البحث بالإحداثيات (الأدق) 2. البحث بالاسم
        $cityId = null;
        $dbCityName = null;
        $city = null;

        // أولاً: البحث بالإحداثيات (أقرب مدينة - الطريقة الأدق)
        if ($country) {
            $nearestCity = $this->findNearestCityInCountry($latitude, $longitude, $country->id, 100);
            if ($nearestCity) {
                $city = \App\Models\City::find($nearestCity['id']);
                Log::info('getTaxFromCoordinates: Found city by coordinates', [
                    'city_id' => $city->id,
                    'city_name' => $city->city_name,
                    'distance_km' => $nearestCity['distance_km']
                ]);
            }
        }

        // ثانياً: إذا لم نجد بالإحداثيات، نبحث بالاسم
        if (!$city && $cityName) {
            $city = \App\Models\City::where('city_name', 'LIKE', '%' . $cityName . '%')
                ->orWhere('city_name', 'LIKE', '%' . $this->normalizeArabicCity($cityName) . '%')
                ->first();

            if ($city) {
                Log::info('getTaxFromCoordinates: Found city by name', [
                    'city_id' => $city->id,
                    'city_name' => $city->city_name,
                    'search_name' => $cityName
                ]);
            }
        }

        if ($city) {
            $cityId = $city->id;
            $dbCityName = $city->city_name;
        }

        $taxRate = 0;
        $taxLocation = '';

        if ($country && $country->tax > 0) {
            $taxRate = $country->tax;
            $taxLocation = $country->country_name;
        }

        // 4. حساب مبلغ الضريبة من سلة التاجر المحدد فقط
        $merchantId = (int) $request->input('merchant_id');
        $cartSummary = $this->cartManager->getMerchantCartSummary($merchantId);
        $cartTotal = $cartSummary['total_price'];
        $taxAmount = ($cartTotal * $taxRate) / 100;
        // Store location data in location_draft session (merchant-scoped)
        $locationDraft = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_id' => $country->id ?? null,
            'country_name' => $country->country_name ?? $countryName,
            'city_id' => $cityId,
            'city_name' => $dbCityName ?? $cityName,
            'state_name' => $stateName,
            'formatted_address' => $formattedAddress,
            'postal_code' => $postalCode,
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'tax_location' => $taxLocation,
        ];

        // Always merchant-scoped (merchant_id is required)
        \Session::put('location_draft_merchant_' . $merchantId, $locationDraft);

        return response()->json([
            'success' => true,
            'merchant_id' => $merchantId,
            'geocoding_success' => $geocodingSuccess,
            'country_id' => $country->id ?? null,
            'country_name' => $country->country_name ?? $countryName,
            'city_id' => $cityId,
            'city_name' => $dbCityName ?? $cityName,
            'state_name' => $stateName,
            'formatted_address' => $formattedAddress,
            'postal_code' => $postalCode,
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'tax_location' => $taxLocation,
            'merchant_cart_total' => $cartTotal,
            'message' => $geocodingSuccess
                ? ($taxRate > 0 ? "الضريبة {$taxRate}%" : 'لا توجد ضريبة')
                : 'تم تحديد الموقع (الضريبة ستُحسب عند الإرسال)'
        ]);
    }

    /**
     * البحث عن أقرب مدينة مدعومة في أي دولة
     * ملاحظة: read-only - لا يتم إنشاء أي بيانات
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

        // البحث في المدن التي لديها إحداثيات
        $city = City::where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("cities.*, {$haversine} as distance_km", [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $maxDistanceKm)
            ->orderBy('distance_km', 'asc')
            ->with(['country:id,country_name,country_code'])
            ->first();

        if (!$city) {
            // لا توجد مدينة قريبة - نحاول إرجاع أي مدينة من دولة مزامنة
            $syncedCountry = Country::where('is_synced', 1)->first();

            if ($syncedCountry) {
                $anyCity = City::where('country_id', $syncedCountry->id)
                    ->where('tryoto_supported', 1)
                    ->with(['country:id,country_name,country_code'])
                    ->inRandomOrder()
                    ->first();

                if ($anyCity) {
                    Log::debug('Geocoding: Using random city from synced country', [
                        'city' => $anyCity->city_name,
                        'country' => $syncedCountry->country_name
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
                        'coordinates' => compact('latitude', 'longitude'),
                        'resolution_info' => [
                            'strategy' => 'fallback_random_city',
                            'warning' => 'يرجى التأكد من اختيار المدينة الصحيحة يدوياً'
                        ]
                    ];
                }
            }

            return null;
        }

        Log::debug('Geocoding: Found nearest city globally', [
            'city' => $city->city_name,
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
     * تطبيع أسماء المدن العربية للبحث
     * يحول الأسماء الإنجليزية الشائعة إلى النطق العربي المكتوب بالإنجليزية
     */
    protected function normalizeArabicCity(string $cityName): string
    {
        // قائمة تحويلات الأسماء الشائعة
        $mappings = [
            'Buraydah' => 'Buraidah',
            'Riyadh' => 'Riyad',
            'Jeddah' => 'Jidda',
            'Mecca' => 'Makka',
            'Medina' => 'Madina',
            'Dammam' => 'Damam',
            'Qatif' => 'Qatef',
        ];

        return $mappings[$cityName] ?? $cityName;
    }

    /**
     * البحث عن أقرب مدينة في دولة محددة بناءً على الإحداثيات
     *
     * @param float $latitude خط العرض
     * @param float $longitude خط الطول
     * @param int $countryId معرّف الدولة
     * @param float $maxDistanceKm أقصى مسافة للبحث
     * @return array|null
     */
    protected function findNearestCityInCountry(float $latitude, float $longitude, int $countryId, float $maxDistanceKm = 50): ?array
    {
        // صيغة Haversine لحساب المسافة بالكيلومتر
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        ))";

        $city = \App\Models\City::select('cities.*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $longitude, $latitude])
            ->where('country_id', $countryId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $maxDistanceKm)
            ->orderBy('distance_km')
            ->first();

        if (!$city) {
            return null;
        }

        return [
            'id' => $city->id,
            'name' => $city->city_name,
            'distance_km' => round($city->distance_km, 2)
        ];
    }
}
