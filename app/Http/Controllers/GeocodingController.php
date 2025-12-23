<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\City;
use App\Services\GoogleMapsService;
use App\Services\TryotoLocationService;
use App\Services\CountrySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GeocodingController extends Controller
{
    protected $googleMapsService;
    protected $tryotoService;
    protected $countrySyncService;

    public function __construct(
        GoogleMapsService $googleMapsService,
        TryotoLocationService $tryotoService,
        CountrySyncService $countrySyncService
    ) {
        $this->googleMapsService = $googleMapsService;
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
            // Find or create the alternative city in database
            $alternativeCity = $this->findOrCreateAlternativeCity($resolution, $countryId);

            return [
                'tryoto_verified' => true,
                'tryoto_strategy' => $resolution['strategy'],
                'tryoto_message' => $resolution['message'],
                'alternative_city' => [
                    'id' => $alternativeCity->id,
                    'name' => $alternativeCity->city_name,
                    'name_ar' => $alternativeCity->city_name_ar,
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
     * Find or create alternative city in database
     *
     * @param array $resolution
     * @param int $countryId
     * @return City
     */
    protected function findOrCreateAlternativeCity($resolution, $countryId)
    {
        $cityName = $resolution['city_name'];
        $cityNameAr = $resolution['city_name_ar'] ?? $cityName;

        // Try to find existing city
        $city = City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();

        if ($city) {
            return $city;
        }

        // Create new city directly under country
        $country = Country::find($countryId);

        if (!$country) {
            throw new \Exception('Country not found');
        }

        // Create the city
        $city = City::create([
            'country_id' => $countryId,
            'city_name' => $cityName,
            'city_name_ar' => $cityNameAr,
            'status' => 1
        ]);

        Log::debug('Alternative Tryoto city created', [
            'city' => $cityName,
            'city_ar' => $cityNameAr,
            'country_id' => $countryId
        ]);

        return $city;
    }

    /**
     * Process and store location data
     *
     * @param array $arabicData
     * @param array $englishData
     * @param float $latitude
     * @param float $longitude
     * @return array
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
        $countryNameAr = $arabicData['country'] ?? $countryNameEn;

        // City names
        $cityNameEn = $englishData['city'] ?? null;
        $cityNameAr = $arabicData['city'] ?? $cityNameEn;

        // Address
        $addressEn = $englishData['address'] ?? null;
        $addressAr = $arabicData['address'] ?? $addressEn;

        // Find or create Country
        $country = Country::where('country_code', $countryCode)->first();

        if (!$country) {
            $country = Country::create([
                'country_code' => $countryCode,
                'country_name' => $countryNameEn ?? $countryCode,
                'country_name_ar' => $countryNameAr ?? $countryNameEn ?? $countryCode,
                'tax' => 0,
                'status' => 1
            ]);
        }

        // Find or create City
        $city = null;
        if ($cityNameEn) {
            $city = City::where('country_id', $country->id)
                ->where(function ($query) use ($cityNameEn, $cityNameAr) {
                    $query->where('city_name', $cityNameEn)
                        ->orWhere('city_name_ar', $cityNameAr);
                })
                ->first();

            if (!$city) {
                $city = City::create([
                    'country_id' => $country->id,
                    'city_name' => $cityNameEn,
                    'city_name_ar' => $cityNameAr ?? $cityNameEn,
                    'status' => 1
                ]);
            }
        }

        return [
            'country' => [
                'id' => $country->id,
                'code' => $country->country_code,
                'name' => $country->country_name,
                'name_ar' => $country->country_name_ar
            ],
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->city_name,
                'name_ar' => $city->city_name_ar
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
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get cities by country
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            ->get(['id', 'city_name', 'city_name_ar', 'country_id']);

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    /**
     * التحقق إذا كانت الدولة تحتاج مزامنة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

        $countryName = $englishData['country'] ?? null;
        $countryCode = $englishData['country_code'] ?? null;

        if (!$countryName) {
            return response()->json([
                'success' => false,
                'error' => 'لم يتم التعرف على الدولة'
            ], 400);
        }

        // التحقق من حالة مزامنة الدولة
        $syncStatus = $this->countrySyncService->needsSyncByName($countryName);

        if ($syncStatus['needs_sync']) {
            // الدولة تحتاج مزامنة
            return response()->json([
                'success' => true,
                'needs_sync' => true,
                'country_name' => $countryName,
                'country_code' => $countryCode,
                'country_name_ar' => $arabicData['country'] ?? $countryName,
                'message' => 'يتم تحميل الدولة واستيراد المدن المدعومة من شركة الشحن. هذه الخطوة تتم مرة واحدة فقط.',
                'google_data' => [
                    'city' => $englishData['city'] ?? null,
                    'city_ar' => $arabicData['city'] ?? null,
                    'state' => $englishData['state'] ?? null,
                    'state_ar' => $arabicData['state'] ?? null,
                    'address' => $englishData['address'] ?? null,
                    'address_ar' => $arabicData['address'] ?? null,
                ],
                'coordinates' => compact('latitude', 'longitude')
            ]);
        }

        // الدولة مزامنة - استخدم DB فقط
        return $this->resolveLocationFromDB($englishData, $arabicData, $latitude, $longitude, $syncStatus['country']);
    }

    /**
     * تحديد الموقع من قاعدة البيانات (بعد المزامنة)
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
                    'city_ar' => $arabicData['city'] ?? null,
                    'state' => $stateName,
                    'state_ar' => $arabicData['state'] ?? null,
                    'country' => $countryName,
                    'country_ar' => $arabicData['country'] ?? null,
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
                'name_ar' => $country->country_name_ar
            ],
            'city' => [
                'id' => $resolution['city_id'],
                'name' => $resolution['resolved_name'],
                'name_ar' => $resolution['resolved_name_ar'] ?? $resolution['resolved_name'],
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
}
