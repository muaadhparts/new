<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * CountrySyncService - مزامنة الدول تلقائياً
 *
 * السياسة:
 * 1. عند اختيار موقع من خريطة لدولة غير موجودة أو is_synced = 0
 * 2. نجلب كل المدن المدعومة من Tryoto لهذه الدولة
 * 3. نجلب الإحداثيات والأسماء العربية من Google
 * 4. نستخرج المحافظات تلقائياً من Google
 * 5. نحدث is_synced = 1
 *
 * بعد المزامنة: كل شيء من DB فقط - لا API calls
 */
class CountrySyncService
{
    protected TryotoService $tryoto;
    protected string $googleApiKey;

    /**
     * تتبع progress للعرض في الواجهة
     */
    protected int $totalSteps = 0;
    protected int $currentStep = 0;
    protected string $currentMessage = '';
    protected ?string $sessionId = null;

    public function __construct()
    {
        $this->tryoto = app(TryotoService::class);
        $this->googleApiKey = config('services.google_maps.api_key', config('services.google_maps.key', ''));
    }

    /**
     * التحقق إذا كانت الدولة تحتاج مزامنة
     */
    public function needsSync(Country $country): bool
    {
        return !$country->is_synced;
    }

    /**
     * التحقق إذا كانت الدولة تحتاج مزامنة بالاسم
     */
    public function needsSyncByName(string $countryName): array
    {
        $country = Country::where('country_name', $countryName)
            ->orWhere('country_name_ar', $countryName)
            ->first();

        if (!$country) {
            return ['needs_sync' => true, 'country' => null, 'reason' => 'country_not_exists'];
        }

        if (!$country->is_synced) {
            return ['needs_sync' => true, 'country' => $country, 'reason' => 'not_synced'];
        }

        return ['needs_sync' => false, 'country' => $country, 'reason' => 'already_synced'];
    }

    /**
     * مزامنة دولة كاملة
     *
     * السياسة الجديدة:
     * - يستقبل الاسم العربي من Google Maps مباشرة
     * - يحفظه في الدولة عند الإنشاء
     *
     * @param string $countryName اسم الدولة بالإنجليزية (من Google Maps)
     * @param string $countryCode كود الدولة (مثل SA, IQ)
     * @param string|null $sessionId معرف الجلسة للـ progress tracking
     * @param string|null $countryNameAr اسم الدولة بالعربية (من Google Maps)
     * @return array
     */
    public function syncCountry(string $countryName, string $countryCode, ?string $sessionId = null, ?string $countryNameAr = null): array
    {
        $this->sessionId = $sessionId ?? uniqid('sync_');

        Log::info('CountrySyncService: Starting country sync', [
            'country' => $countryName,
            'country_ar' => $countryNameAr,
            'code' => $countryCode,
            'session' => $this->sessionId
        ]);

        try {
            // ==========================================
            // المرحلة 1: إنشاء/تحديث الدولة مع الاسم العربي من Google
            // ==========================================
            $this->updateProgress(0, 'جاري إنشاء الدولة...');

            $country = $this->createOrUpdateCountry($countryName, $countryCode, $countryNameAr);
            if (!$country) {
                throw new \Exception('فشل في إنشاء الدولة');
            }

            // ==========================================
            // المرحلة 2: جلب المدن من Tryoto
            // ==========================================
            $this->updateProgress(10, 'جاري جلب المدن المدعومة من شركة الشحن...');

            $cities = $this->fetchTryotoCities($countryCode);
            if (empty($cities)) {
                // لا مدن = الدولة غير مدعومة من Tryoto
                $country->update(['is_synced' => 1, 'synced_at' => now()]);

                return [
                    'success' => false,
                    'message' => 'هذه الدولة غير مدعومة من شركة الشحن',
                    'country_id' => $country->id,
                    'cities_count' => 0
                ];
            }

            $this->totalSteps = count($cities);
            $this->updateProgress(15, "تم العثور على {$this->totalSteps} مدينة. جاري المعالجة...");

            // ==========================================
            // المرحلة 3: استيراد المدن بسرعة (بدون Google geocoding)
            // ==========================================
            $syncedCities = $this->quickImportCities($country, $cities);

            // ==========================================
            // المرحلة 4: تحديث is_synced
            // ==========================================
            $this->updateProgress(98, 'جاري إنهاء المزامنة...');

            $country->update([
                'is_synced' => 1,
                'synced_at' => now()
            ]);

            // مسح الـ cache
            Cache::flush();

            $this->updateProgress(100, 'تمت المزامنة بنجاح!');

            Log::info('CountrySyncService: Country sync completed', [
                'country' => $countryName,
                'cities_synced' => $syncedCities
            ]);

            return [
                'success' => true,
                'message' => "تم استيراد {$syncedCities} مدينة بنجاح",
                'country_id' => $country->id,
                'cities_count' => $syncedCities,
                'states_count' => 0
            ];

        } catch (\Exception $e) {
            Log::error('CountrySyncService: Sync failed', [
                'country' => $countryName,
                'error' => $e->getMessage()
            ]);

            $this->updateProgress(0, 'فشل في المزامنة: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'فشل في المزامنة: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * استيراد المدن بسرعة بدون Google geocoding
     *
     * هذه الطريقة سريعة جداً لأنها:
     * 1. لا تستدعي Google API لكل مدينة
     * 2. تستخدم firstOrCreate للتجنب التكرار
     * 3. الأسماء العربية والإحداثيات تُضاف لاحقاً عند الحاجة
     */
    protected function quickImportCities(Country $country, array $cities): int
    {
        $imported = 0;
        $total = count($cities);

        foreach ($cities as $index => $cityData) {
            $cityName = $cityData['name'] ?? '';
            if (empty($cityName)) continue;

            // تحديث الـ progress
            if ($index % 100 === 0) {
                $percent = 15 + (int)(($index / $total) * 80);
                $this->updateProgress($percent, "جاري استيراد المدن: {$index} / {$total}");
            }

            // إنشاء المدينة (أو تجاهلها إذا موجودة)
            City::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'city_name' => $cityName
                ],
                [
                    'city_name_ar' => $cityName, // سيتم تحديثها لاحقاً من Google
                    'state_id' => 0,
                    'tryoto_supported' => 1,
                    'status' => 1,
                    'latitude' => null,
                    'longitude' => null,
                ]
            );

            $imported++;
        }

        return $imported;
    }

    /**
     * إنشاء أو تحديث الدولة
     *
     * السياسة الجديدة:
     * - إذا الدولة غير موجودة: إنشاءها مع is_synced = 0 و synced_at = NULL
     * - ملء country_name (EN) و country_name_ar (AR) من Google Maps
     */
    protected function createOrUpdateCountry(string $countryName, string $countryCode, ?string $countryNameAr = null): ?Country
    {
        try {
            $countryCode = strtoupper($countryCode);

            // البحث عن الدولة الموجودة
            $existingCountry = Country::where('country_code', $countryCode)
                ->orWhere('country_name', $countryName)
                ->orWhere('country_name_ar', $countryNameAr)
                ->first();

            if ($existingCountry) {
                // تحديث البيانات الناقصة فقط
                $updates = [];

                if (empty($existingCountry->country_name) && $countryName) {
                    $updates['country_name'] = $countryName;
                }

                if (empty($existingCountry->country_name_ar) && $countryNameAr) {
                    $updates['country_name_ar'] = $countryNameAr;
                }

                if (empty($existingCountry->country_code) && $countryCode) {
                    $updates['country_code'] = $countryCode;
                }

                if (!empty($updates)) {
                    $existingCountry->update($updates);
                    Log::info('CountrySyncService: Updated existing country', [
                        'id' => $existingCountry->id,
                        'updates' => $updates
                    ]);
                }

                return $existingCountry;
            }

            // جلب الاسم العربي من Google Maps إذا لم يتم تمريره
            if (!$countryNameAr) {
                $countryNameAr = $this->getArabicCountryNameFromGoogle($countryName, $countryCode);
            }

            // إنشاء دولة جديدة مع is_synced = 0 و synced_at = NULL
            $country = Country::create([
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'country_name_ar' => $countryNameAr ?: $countryName,
                'status' => 1,
                'tax' => 0,
                'is_synced' => 0,
                'synced_at' => null,
            ]);

            Log::info('CountrySyncService: Created new country', [
                'id' => $country->id,
                'name' => $countryName,
                'name_ar' => $countryNameAr,
                'code' => $countryCode,
                'is_synced' => 0
            ]);

            return $country;
        } catch (\Exception $e) {
            Log::error('CountrySyncService: Failed to create country', [
                'name' => $countryName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * جلب الاسم العربي للدولة من Google Maps API
     *
     * السياسة: نستخدم Google Maps للحصول على الأسماء العربية الرسمية
     * إذا فشل الاتصال، نستخدم القائمة المحلية
     */
    protected function getArabicCountryNameFromGoogle(string $countryName, string $countryCode): string
    {
        // محاولة جلب الاسم من Google Maps أولاً
        if (!empty($this->googleApiKey)) {
            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $countryName,
                    'key' => $this->googleApiKey,
                    'language' => 'ar',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        foreach ($data['results'][0]['address_components'] as $component) {
                            if (in_array('country', $component['types'])) {
                                return $component['long_name'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('CountrySyncService: Failed to get Arabic country name from Google', [
                    'country' => $countryName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // القائمة المحلية كـ fallback
        $arabicNames = [
            'SA' => 'السعودية',
            'AE' => 'الإمارات',
            'IQ' => 'العراق',
            'JO' => 'الأردن',
            'KW' => 'الكويت',
            'BH' => 'البحرين',
            'OM' => 'عُمان',
            'QA' => 'قطر',
            'EG' => 'مصر',
            'SY' => 'سوريا',
            'LB' => 'لبنان',
            'PS' => 'فلسطين',
            'YE' => 'اليمن',
            'LY' => 'ليبيا',
            'SD' => 'السودان',
            'TN' => 'تونس',
            'DZ' => 'الجزائر',
            'MA' => 'المغرب',
        ];

        return $arabicNames[strtoupper($countryCode)] ?? $countryName;
    }

    /**
     * جلب المدن والمناطق من Tryoto API
     *
     * السياسة الجديدة:
     * - نجلب كل المدن مع المناطق (states/regions) من Tryoto
     * - نعتمد على state الذي يعيده Tryoto دائماً
     * - البيانات تشمل: city_name, state/region من Tryoto
     */
    protected function fetchTryotoCities(string $countryCode): array
    {
        $allCities = [];
        $page = 1;

        do {
            $result = $this->tryoto->makeApiRequest('POST', '/rest/v2/getCities', [
                'country' => strtoupper($countryCode),
                'page' => $page,
            ]);

            if (!$result['success'] || !isset($result['data']['getCities'])) {
                break;
            }

            $data = $result['data']['getCities'];
            $cities = $data['Cities'] ?? [];
            $totalCount = $data['totalCount'] ?? 0;

            // معالجة كل مدينة وإضافة معلومات المنطقة من Tryoto
            foreach ($cities as $city) {
                $cityData = [
                    'name' => $city['name'] ?? $city['city'] ?? '',
                    'tryoto_state' => $city['state'] ?? $city['region'] ?? $city['province'] ?? null,
                    'tryoto_city_id' => $city['id'] ?? $city['cityId'] ?? null,
                ];

                // نتخطى المدن بدون اسم
                if (!empty($cityData['name'])) {
                    $allCities[] = $cityData;
                }
            }

            $page++;

        } while (count($allCities) < $totalCount && !empty($cities));

        Log::info('CountrySyncService: Fetched cities from Tryoto', [
            'country' => $countryCode,
            'count' => count($allCities),
            'sample' => array_slice($allCities, 0, 3)
        ]);

        return $allCities;
    }

    /**
     * مزامنة مدينة واحدة مع الإحداثيات والمحافظة
     *
     * السياسة الجديدة:
     * 1. الاسم الإنجليزي: من Tryoto أولاً، ثم Google Maps
     * 2. الاسم العربي: دائماً من Google Maps (لا hardcoded)
     * 3. المنطقة/State: دائماً من Tryoto
     * 4. التحقق من عدم وجود سجل مكرر قبل الإنشاء
     *
     * @param Country $country الدولة
     * @param array $cityData بيانات المدينة من Tryoto ['name', 'tryoto_state', 'tryoto_city_id']
     * @param array $statesCreated قائمة المحافظات المنشأة
     * @return array|null
     */
    protected function syncCityWithGeocoding(Country $country, array $cityData, array &$statesCreated): ?array
    {
        try {
            $cityNameEn = $cityData['name'] ?? '';
            $tryotoState = $cityData['tryoto_state'] ?? null;

            if (empty($cityNameEn)) {
                return null;
            }

            // ==========================================
            // الخطوة 1: التحقق من وجود المدينة (بالاسم EN أو AR)
            // ==========================================
            $existingCity = City::where('country_id', $country->id)
                ->where(function ($q) use ($cityNameEn) {
                    $q->where('city_name', $cityNameEn)
                      ->orWhere('city_name_ar', $cityNameEn);
                })
                ->first();

            // إذا موجودة ولديها كل البيانات، نتخطاها
            if ($existingCity && $existingCity->latitude && $existingCity->longitude && $existingCity->state_id && $existingCity->city_name_ar) {
                // تحديث tryoto_supported فقط إذا لم يكن 1
                if (!$existingCity->tryoto_supported) {
                    $existingCity->update(['tryoto_supported' => 1]);
                }
                return null;
            }

            // ==========================================
            // الخطوة 2: جلب البيانات من Google Maps
            // ==========================================
            $geoData = $this->geocodeCityForSync($cityNameEn, $country->country_name);

            // ==========================================
            // الخطوة 3: معالجة المنطقة/State (دائماً من Tryoto)
            // ==========================================
            $state = null;
            $stateNameEn = $tryotoState; // من Tryoto
            $stateNameAr = $geoData['state_ar'] ?? null; // من Google Maps

            if (!empty($stateNameEn)) {
                $state = $this->findOrCreateStateWithCheck(
                    $country->id,
                    $stateNameEn,       // EN من Tryoto
                    $stateNameAr        // AR من Google Maps
                );

                if ($state && !in_array($stateNameEn, $statesCreated)) {
                    $statesCreated[] = $stateNameEn;
                }
            }

            // ==========================================
            // الخطوة 4: تحديد الأسماء النهائية
            // ==========================================
            // الاسم الإنجليزي: من Tryoto (نستخدمه كما هو)
            $finalCityNameEn = $cityNameEn;

            // الاسم العربي: من Google Maps فقط (لا hardcoded)
            $finalCityNameAr = $geoData['city_ar'] ?? null;

            // إذا لم نجد الاسم العربي، نتركه فارغاً مؤقتاً (سيتم تحديثه لاحقاً)
            // لكن لا نستخدم الاسم الإنجليزي كبديل
            if (empty($finalCityNameAr)) {
                $finalCityNameAr = $cityNameEn; // fallback للاسم الإنجليزي مؤقتاً
                Log::warning('CountrySyncService: No Arabic name from Google for city', [
                    'city' => $cityNameEn,
                    'country' => $country->country_name
                ]);
            }

            // ==========================================
            // الخطوة 5: إنشاء أو تحديث المدينة
            // ==========================================
            if ($existingCity) {
                // تحديث البيانات الناقصة
                $updates = [
                    'tryoto_supported' => 1,
                ];

                if (empty($existingCity->city_name_ar) && $finalCityNameAr !== $cityNameEn) {
                    $updates['city_name_ar'] = $finalCityNameAr;
                }

                if (!$existingCity->state_id && $state) {
                    $updates['state_id'] = $state->id;
                }

                if (!$existingCity->latitude && isset($geoData['lat'])) {
                    $updates['latitude'] = $geoData['lat'];
                    $updates['longitude'] = $geoData['lng'];
                }

                $existingCity->update($updates);
            } else {
                // إنشاء مدينة جديدة
                City::create([
                    'country_id' => $country->id,
                    'state_id' => $state?->id ?? 0,
                    'city_name' => $finalCityNameEn,
                    'city_name_ar' => $finalCityNameAr,
                    'latitude' => $geoData['lat'] ?? null,
                    'longitude' => $geoData['lng'] ?? null,
                    'tryoto_supported' => 1,
                    'status' => 1,
                ]);
            }

            return [
                'city_name' => $finalCityNameEn,
                'city_name_ar' => $finalCityNameAr,
                'state_name' => $stateNameEn,
                'state_name_ar' => $stateNameAr,
                'coordinates' => isset($geoData['lat']) ? true : false
            ];

        } catch (\Exception $e) {
            Log::warning('CountrySyncService: Failed to sync city', [
                'city' => $cityData['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * جلب بيانات المدينة من Google Geocoding
     */
    protected function geocodeCity(string $cityName, string $countryName): ?array
    {
        if (empty($this->googleApiKey)) {
            return null;
        }

        try {
            // طلب بالإنجليزية للإحداثيات
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => "{$cityName}, {$countryName}",
                'key' => $this->googleApiKey,
                'language' => 'en',
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'];

            // استخراج المحافظة
            $state = null;
            foreach ($result['address_components'] as $component) {
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $state = $component['long_name'];
                    break;
                }
            }

            // طلب بالعربية للأسماء العربية
            $arabicData = $this->getArabicNames($location['lat'], $location['lng']);

            return [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'state' => $state,
                'state_ar' => $arabicData['state'] ?? $state,
                'city_ar' => $arabicData['city'] ?? $cityName,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * جلب الأسماء العربية من Google
     */
    protected function getArabicNames(float $lat, float $lng): array
    {
        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $this->googleApiKey,
                'language' => 'ar',
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return [];
            }

            $result = $data['results'][0];
            $names = [];

            foreach ($result['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $names['city'] = $component['long_name'];
                }
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $names['state'] = $component['long_name'];
                }
            }

            return $names;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * جلب بيانات المدينة من Google Maps للمزامنة
     *
     * السياسة الجديدة:
     * - نجلب الإحداثيات من Google
     * - نجلب الأسماء العربية من Google (للمدينة والمحافظة)
     * - لا نستخدم أي ترجمات hardcoded
     */
    protected function geocodeCityForSync(string $cityName, string $countryName): ?array
    {
        if (empty($this->googleApiKey)) {
            Log::warning('CountrySyncService: Google API key not configured');
            return null;
        }

        try {
            // طلب بالإنجليزية للإحداثيات
            $responseEn = Http::timeout(15)->retry(2, 500)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => "{$cityName}, {$countryName}",
                'key' => $this->googleApiKey,
                'language' => 'en',
            ]);

            if (!$responseEn->successful()) {
                return null;
            }

            $dataEn = $responseEn->json();
            if ($dataEn['status'] !== 'OK' || empty($dataEn['results'])) {
                return null;
            }

            $resultEn = $dataEn['results'][0];
            $location = $resultEn['geometry']['location'];

            // طلب بالعربية للأسماء العربية (باستخدام الإحداثيات للدقة)
            $responseAr = Http::timeout(15)->retry(2, 500)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$location['lat']},{$location['lng']}",
                'key' => $this->googleApiKey,
                'language' => 'ar',
            ]);

            $cityAr = null;
            $stateAr = null;

            if ($responseAr->successful()) {
                $dataAr = $responseAr->json();
                if ($dataAr['status'] === 'OK' && !empty($dataAr['results'])) {
                    $resultAr = $dataAr['results'][0];

                    foreach ($resultAr['address_components'] as $component) {
                        // المدينة
                        if (in_array('locality', $component['types'])) {
                            $cityAr = $component['long_name'];
                        }
                        // المنطقة الإدارية (للاسم العربي فقط)
                        if (in_array('administrative_area_level_1', $component['types'])) {
                            $stateAr = $component['long_name'];
                        }
                        // fallback للمدينة
                        if (!$cityAr && in_array('administrative_area_level_2', $component['types'])) {
                            $cityAr = $component['long_name'];
                        }
                    }
                }
            }

            return [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'city_ar' => $cityAr,
                'state_ar' => $stateAr,
            ];

        } catch (\Exception $e) {
            Log::warning('CountrySyncService: Geocoding failed', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * إنشاء أو جلب محافظة مع التحقق من التكرار
     *
     * السياسة الجديدة:
     * - التحقق من وجود المحافظة بالاسم EN أو AR قبل الإنشاء
     * - الاسم الإنجليزي من Tryoto
     * - الاسم العربي من Google Maps
     */
    protected function findOrCreateStateWithCheck(int $countryId, string $stateNameEn, ?string $stateNameAr = null): State
    {
        // البحث عن محافظة موجودة بنفس الاسم (EN أو AR)
        $existingState = State::where('country_id', $countryId)
            ->where(function ($q) use ($stateNameEn, $stateNameAr) {
                $q->where('state', $stateNameEn);

                if ($stateNameAr) {
                    $q->orWhere('state_ar', $stateNameAr);
                }

                // البحث أيضاً بالاسم الإنجليزي في حقل العربي (لحالات الـ fallback)
                $q->orWhere('state_ar', $stateNameEn);
            })
            ->first();

        if ($existingState) {
            // تحديث الاسم العربي إذا كان ناقصاً
            if (empty($existingState->state_ar) && $stateNameAr) {
                $existingState->update(['state_ar' => $stateNameAr]);
            }
            return $existingState;
        }

        // إنشاء محافظة جديدة
        $state = State::create([
            'country_id' => $countryId,
            'state' => $stateNameEn,
            'state_ar' => $stateNameAr ?: $stateNameEn, // fallback للاسم الإنجليزي مؤقتاً
            'status' => 1,
            'tax' => 0,
            'owner_id' => 0,
        ]);

        Log::info('CountrySyncService: Created new state', [
            'id' => $state->id,
            'name_en' => $stateNameEn,
            'name_ar' => $stateNameAr,
            'country_id' => $countryId
        ]);

        return $state;
    }

    /**
     * إنشاء أو جلب محافظة (الدالة القديمة للتوافقية)
     */
    protected function findOrCreateState(int $countryId, string $stateName, string $stateNameAr): State
    {
        return $this->findOrCreateStateWithCheck($countryId, $stateName, $stateNameAr);
    }

    /**
     * تحديث إحداثيات المحافظات (متوسط إحداثيات المدن)
     */
    protected function updateStatesCoordinates(int $countryId): void
    {
        $states = State::where('country_id', $countryId)->get();

        foreach ($states as $state) {
            $avgCoords = City::where('state_id', $state->id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw('AVG(latitude) as avg_lat, AVG(longitude) as avg_lng')
                ->first();

            if ($avgCoords && $avgCoords->avg_lat && $avgCoords->avg_lng) {
                $state->update([
                    'latitude' => $avgCoords->avg_lat,
                    'longitude' => $avgCoords->avg_lng,
                ]);
            }
        }
    }

    /**
     * تحديث حالة الـ progress
     */
    protected function updateProgress(int $percent, string $message): void
    {
        $this->currentStep = $percent;
        $this->currentMessage = $message;

        if ($this->sessionId) {
            Cache::put("sync_progress_{$this->sessionId}", [
                'percent' => $percent,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ], now()->addMinutes(30));
        }

        Log::info('CountrySyncService: Progress', [
            'session' => $this->sessionId,
            'percent' => $percent,
            'message' => $message
        ]);
    }

    /**
     * الحصول على حالة الـ progress
     */
    public static function getProgress(string $sessionId): ?array
    {
        return Cache::get("sync_progress_{$sessionId}");
    }

    /**
     * استخراج كود الدولة من الاسم
     */
    public function getCountryCode(string $countryName): ?string
    {
        $codes = [
            'Saudi Arabia' => 'SA',
            'السعودية' => 'SA',
            'United Arab Emirates' => 'AE',
            'الإمارات' => 'AE',
            'Iraq' => 'IQ',
            'العراق' => 'IQ',
            'Jordan' => 'JO',
            'الأردن' => 'JO',
            'Kuwait' => 'KW',
            'الكويت' => 'KW',
            'Bahrain' => 'BH',
            'البحرين' => 'BH',
            'Oman' => 'OM',
            'عُمان' => 'OM',
            'عمان' => 'OM',
            'Qatar' => 'QA',
            'قطر' => 'QA',
            'Egypt' => 'EG',
            'مصر' => 'EG',
            'Syria' => 'SY',
            'سوريا' => 'SY',
            'Lebanon' => 'LB',
            'لبنان' => 'LB',
            'Yemen' => 'YE',
            'اليمن' => 'YE',
            'Libya' => 'LY',
            'ليبيا' => 'LY',
            'Sudan' => 'SD',
            'السودان' => 'SD',
            'Tunisia' => 'TN',
            'تونس' => 'TN',
            'Algeria' => 'DZ',
            'الجزائر' => 'DZ',
            'Morocco' => 'MA',
            'المغرب' => 'MA',
            'Palestine' => 'PS',
            'فلسطين' => 'PS',
        ];

        return $codes[$countryName] ?? null;
    }
}
