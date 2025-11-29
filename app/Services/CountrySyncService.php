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
     * @param string $countryName اسم الدولة (English أو Arabic)
     * @param string $countryCode كود الدولة (مثل SA, IQ)
     * @param string|null $sessionId معرف الجلسة للـ progress tracking
     * @return array
     */
    public function syncCountry(string $countryName, string $countryCode, ?string $sessionId = null): array
    {
        $this->sessionId = $sessionId ?? uniqid('sync_');

        Log::info('CountrySyncService: Starting country sync', [
            'country' => $countryName,
            'code' => $countryCode,
            'session' => $this->sessionId
        ]);

        try {
            // ==========================================
            // المرحلة 1: إنشاء/تحديث الدولة
            // ==========================================
            $this->updateProgress(0, 'جاري إنشاء الدولة...');

            $country = $this->createOrUpdateCountry($countryName, $countryCode);
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
            // المرحلة 3: مزامنة المدن (بدون transaction لحفظ تدريجي)
            // ==========================================
            $syncedCities = 0;
            $statesCreated = [];

            foreach ($cities as $index => $cityData) {
                $cityName = $cityData['name'] ?? '';
                if (empty($cityName)) continue;

                $percent = 15 + (int)(($index / $this->totalSteps) * 80);
                $this->updateProgress($percent, "جاري معالجة: {$cityName}");

                try {
                    $result = $this->syncCityWithGeocoding($country, $cityName, $statesCreated);
                    if ($result) {
                        $syncedCities++;
                        if (isset($result['state_name']) && !in_array($result['state_name'], $statesCreated)) {
                            $statesCreated[] = $result['state_name'];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('CountrySyncService: City sync error', [
                        'city' => $cityName,
                        'error' => $e->getMessage()
                    ]);
                    // استمر في المدن الأخرى
                }

                // تأخير صغير لتجنب rate limiting من Google
                usleep(100000); // 100ms
            }

            // ==========================================
            // المرحلة 4: تحديث إحداثيات المحافظات
            // ==========================================
            $this->updateProgress(95, 'جاري تحديث إحداثيات المحافظات...');
            $this->updateStatesCoordinates($country->id);

            // ==========================================
            // المرحلة 5: تحديث is_synced
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
                'cities_synced' => $syncedCities,
                'states_created' => count($statesCreated)
            ]);

            return [
                'success' => true,
                'message' => "تم استيراد {$syncedCities} مدينة و " . count($statesCreated) . " محافظة بنجاح",
                'country_id' => $country->id,
                'cities_count' => $syncedCities,
                'states_count' => count($statesCreated)
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
     * إنشاء أو تحديث الدولة
     */
    protected function createOrUpdateCountry(string $countryName, string $countryCode): ?Country
    {
        try {
            // جلب الاسم العربي من Google إذا لم يكن موجوداً
            $arabicName = $this->getArabicCountryName($countryName, $countryCode);

            return Country::updateOrCreate(
                ['country_code' => strtoupper($countryCode)],
                [
                    'country_name' => $countryName,
                    'country_name_ar' => $arabicName,
                    'status' => 1,
                    'tax' => 0,
                ]
            );
        } catch (\Exception $e) {
            Log::error('CountrySyncService: Failed to create country', [
                'name' => $countryName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * جلب الاسم العربي للدولة من Google
     */
    protected function getArabicCountryName(string $countryName, string $countryCode): string
    {
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
     * جلب المدن من Tryoto API
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

            $allCities = array_merge($allCities, $cities);
            $page++;

        } while (count($allCities) < $totalCount && !empty($cities));

        Log::info('CountrySyncService: Fetched cities from Tryoto', [
            'country' => $countryCode,
            'count' => count($allCities)
        ]);

        return $allCities;
    }

    /**
     * مزامنة مدينة واحدة مع الإحداثيات والمحافظة
     */
    protected function syncCityWithGeocoding(Country $country, string $cityName, array &$statesCreated): ?array
    {
        try {
            // التحقق من وجود المدينة
            $existingCity = City::where('country_id', $country->id)
                ->where('city_name', $cityName)
                ->first();

            // إذا موجودة ولديها إحداثيات، نتخطاها
            if ($existingCity && $existingCity->latitude && $existingCity->longitude && $existingCity->state_id) {
                return null;
            }

            // جلب البيانات من Google
            $geoData = $this->geocodeCity($cityName, $country->country_name);

            if (!$geoData) {
                // إنشاء المدينة بدون إحداثيات
                City::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'city_name' => $cityName,
                    ],
                    [
                        'city_name_ar' => $cityName,
                        'state_id' => 0,
                        'tryoto_supported' => 1,
                        'status' => 1,
                    ]
                );
                return ['city_name' => $cityName];
            }

            // إنشاء أو جلب المحافظة
            $state = null;
            if (!empty($geoData['state'])) {
                $state = $this->findOrCreateState(
                    $country->id,
                    $geoData['state'],
                    $geoData['state_ar'] ?? $geoData['state']
                );
            }

            // إنشاء أو تحديث المدينة
            City::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'city_name' => $cityName,
                ],
                [
                    'city_name_ar' => $geoData['city_ar'] ?? $cityName,
                    'state_id' => $state?->id ?? 0,
                    'latitude' => $geoData['lat'] ?? null,
                    'longitude' => $geoData['lng'] ?? null,
                    'tryoto_supported' => 1,
                    'status' => 1,
                ]
            );

            return [
                'city_name' => $cityName,
                'state_name' => $geoData['state'] ?? null,
                'coordinates' => isset($geoData['lat']) ? true : false
            ];

        } catch (\Exception $e) {
            Log::warning('CountrySyncService: Failed to sync city', [
                'city' => $cityName,
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
     * إنشاء أو جلب محافظة
     */
    protected function findOrCreateState(int $countryId, string $stateName, string $stateNameAr): State
    {
        return State::firstOrCreate(
            [
                'country_id' => $countryId,
                'state' => $stateName,
            ],
            [
                'state_ar' => $stateNameAr,
                'status' => 1,
                'tax' => 0,
                'owner_id' => 0,
            ]
        );
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
