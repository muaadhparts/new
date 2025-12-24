<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * CountrySyncService - مزامنة المدن من Tryoto
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن
 * - يتم الاستيراد يدوياً فقط عبر CLI
 * - البيانات: city_name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
 * - لا يوجد اسم عربي للمدن
 * - الإحداثيات تُجلب من Google Maps في الخلفية
 */
class CountrySyncService
{
    protected TryotoService $tryoto;
    protected string $googleApiKey;

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
            ->orWhere('country_code', strtoupper($countryName))
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
     * مزامنة دولة كاملة من Tryoto
     * يتم استدعاؤها يدوياً فقط - لا علاقة بالـ checkout
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
            // المرحلة 1: إنشاء/تحديث الدولة
            $this->updateProgress(0, 'جاري إنشاء الدولة...');

            $country = $this->createOrUpdateCountry($countryName, $countryCode);
            if (!$country) {
                throw new \Exception('فشل في إنشاء الدولة');
            }

            // المرحلة 2: جلب المدن من Tryoto
            $this->updateProgress(10, 'جاري جلب المدن المدعومة من شركة الشحن...');

            $cities = $this->fetchTryotoCities($countryCode);
            if (empty($cities)) {
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

            // المرحلة 3: استيراد المدن
            $syncedCities = $this->importCities($country, $cities);

            // المرحلة 4: تحديث is_synced
            $this->updateProgress(98, 'جاري إنهاء المزامنة...');

            $country->update([
                'is_synced' => 1,
                'synced_at' => now()
            ]);

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
                'cities_count' => $syncedCities
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
     * استيراد المدن من Tryoto
     * البيانات: city_name فقط (إنجليزي) + tryoto_supported
     * الإحداثيات تُجلب لاحقاً عبر: php artisan cities:geocode
     */
    protected function importCities(Country $country, array $cities): int
    {
        $imported = 0;
        $total = count($cities);

        foreach ($cities as $index => $cityData) {
            $cityName = $cityData['name'] ?? '';
            if (empty($cityName)) continue;

            if ($index % 100 === 0) {
                $percent = 15 + (int)(($index / $total) * 80);
                $this->updateProgress($percent, "جاري استيراد المدن: {$index} / {$total}");
            }

            // إنشاء المدينة بالاسم الإنجليزي فقط
            City::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'city_name' => $cityName
                ],
                [
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
    protected function createOrUpdateCountry(string $countryName, string $countryCode): ?Country
    {
        try {
            $countryCode = strtoupper($countryCode);

            $existingCountry = Country::where('country_code', $countryCode)
                ->orWhere('country_name', $countryName)
                ->first();

            if ($existingCountry) {
                $updates = [];

                if (empty($existingCountry->country_name) && $countryName) {
                    $updates['country_name'] = $countryName;
                }

                if (empty($existingCountry->country_code) && $countryCode) {
                    $updates['country_code'] = $countryCode;
                }

                if (!empty($updates)) {
                    $existingCountry->update($updates);
                }

                return $existingCountry;
            }

            // إنشاء دولة جديدة
            $country = Country::create([
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'status' => 1,
                'tax' => 0,
                'is_synced' => 0,
                'synced_at' => null,
            ]);

            Log::info('CountrySyncService: Created new country', [
                'id' => $country->id,
                'name' => $countryName,
                'code' => $countryCode
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

            foreach ($cities as $city) {
                $cityName = $city['name'] ?? $city['city'] ?? '';
                if (!empty($cityName)) {
                    $allCities[] = ['name' => $cityName];
                }
            }

            $page++;

        } while (count($allCities) < $totalCount && !empty($cities));

        Log::debug('CountrySyncService: Fetched cities from Tryoto', [
            'country' => $countryCode,
            'count' => count($allCities)
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
            'United Arab Emirates' => 'AE',
            'Iraq' => 'IQ',
            'Jordan' => 'JO',
            'Kuwait' => 'KW',
            'Bahrain' => 'BH',
            'Oman' => 'OM',
            'Qatar' => 'QA',
            'Egypt' => 'EG',
            'Syria' => 'SY',
            'Lebanon' => 'LB',
            'Yemen' => 'YE',
            'Libya' => 'LY',
            'Sudan' => 'SD',
            'Tunisia' => 'TN',
            'Algeria' => 'DZ',
            'Morocco' => 'MA',
            'Palestine' => 'PS',
        ];

        return $codes[$countryName] ?? null;
    }
}
