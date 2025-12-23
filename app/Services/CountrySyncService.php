<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
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
 * 4. نحدث is_synced = 1
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
     * @param string $countryName اسم الدولة بالإنجليزية (من Google Maps)
     * @param string $countryCode كود الدولة (مثل SA, IQ)
     * @param string|null $sessionId معرف الجلسة للـ progress tracking
     * @param string|null $countryNameAr اسم الدولة بالعربية (من Google Maps)
     * @return array
     */
    public function syncCountry(string $countryName, string $countryCode, ?string $sessionId = null, ?string $countryNameAr = null): array
    {
        $this->sessionId = $sessionId ?? uniqid('sync_');

        Log::debug('CountrySyncService: Starting country sync', [
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

            Log::debug('CountrySyncService: Country sync completed', [
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
                    Log::debug('CountrySyncService: Updated existing country', [
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

            Log::debug('CountrySyncService: Created new country', [
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

            // معالجة كل مدينة
            foreach ($cities as $city) {
                $cityData = [
                    'name' => $city['name'] ?? $city['city'] ?? '',
                    'tryoto_city_id' => $city['id'] ?? $city['cityId'] ?? null,
                ];

                // نتخطى المدن بدون اسم
                if (!empty($cityData['name'])) {
                    $allCities[] = $cityData;
                }
            }

            $page++;

        } while (count($allCities) < $totalCount && !empty($cities));

        Log::debug('CountrySyncService: Fetched cities from Tryoto', [
            'country' => $countryCode,
            'count' => count($allCities),
            'sample' => array_slice($allCities, 0, 3)
        ]);

        return $allCities;
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

        Log::debug('CountrySyncService: Progress', [
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
