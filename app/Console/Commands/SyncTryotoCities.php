<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\TryotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * مزامنة المدن المدعومة من Tryoto إلى قاعدة البيانات
 *
 * هذا الـ Command يجلب جميع المدن المدعومة من Tryoto API
 * ويخزنها في جداول countries, states, cities مع الإحداثيات
 *
 * يُشغّل مرة واحدة عند التثبيت، ثم أسبوعياً للتحديث
 */
class SyncTryotoCities extends Command
{
    protected $signature = 'tryoto:sync-cities
                            {--country= : Sync specific country code (SA, AE, IQ, etc.)}
                            {--fresh : Delete existing data and sync fresh}
                            {--no-geocode : Skip geocoding (faster, but no coordinates)}';

    protected $description = 'Sync all Tryoto supported cities to database with coordinates';

    protected TryotoService $tryoto;
    protected string $googleApiKey;

    /**
     * الدول المدعومة من Tryoto مع أكوادها
     */
    protected array $supportedCountries = [
        'SA' => ['name' => 'Saudi Arabia', 'name_ar' => 'السعودية'],
        'AE' => ['name' => 'United Arab Emirates', 'name_ar' => 'الإمارات'],
        'IQ' => ['name' => 'Iraq', 'name_ar' => 'العراق'],
        'JO' => ['name' => 'Jordan', 'name_ar' => 'الأردن'],
        'KW' => ['name' => 'Kuwait', 'name_ar' => 'الكويت'],
        'BH' => ['name' => 'Bahrain', 'name_ar' => 'البحرين'],
        'OM' => ['name' => 'Oman', 'name_ar' => 'عُمان'],
        'QA' => ['name' => 'Qatar', 'name_ar' => 'قطر'],
        'EG' => ['name' => 'Egypt', 'name_ar' => 'مصر'],
    ];

    public function handle(): int
    {
        $this->tryoto = app(TryotoService::class);
        $this->googleApiKey = config('services.google_maps.api_key', '');

        $this->info('🚀 Starting Tryoto Cities Sync...');
        $this->newLine();

        // تحديد الدول للمزامنة
        $countryCode = $this->option('country');
        $countries = $countryCode
            ? [$countryCode => $this->supportedCountries[$countryCode] ?? ['name' => $countryCode, 'name_ar' => $countryCode]]
            : $this->supportedCountries;

        // مسح البيانات القديمة إذا طُلب
        if ($this->option('fresh')) {
            $this->warn('⚠️  Fresh sync requested - clearing existing Tryoto data...');
            $this->clearExistingData($countries);
        }

        $totalCities = 0;
        $totalGeocoded = 0;

        foreach ($countries as $code => $countryData) {
            $this->info("📍 Processing {$countryData['name']} ({$code})...");

            // 1. إنشاء/تحديث الدولة
            $country = $this->syncCountry($code, $countryData);
            if (!$country) {
                $this->error("   Failed to create country: {$countryData['name']}");
                continue;
            }

            // 2. جلب المدن من Tryoto API
            $cities = $this->fetchTryotoCities($code);
            if (empty($cities)) {
                $this->warn("   No cities found for {$code}");
                continue;
            }

            $this->info("   Found " . count($cities) . " cities from Tryoto API");

            // 3. مزامنة المدن مع DB
            $bar = $this->output->createProgressBar(count($cities));
            $bar->start();

            $geocodedCount = 0;
            foreach ($cities as $cityData) {
                $cityName = $cityData['name'] ?? '';
                if (empty($cityName)) continue;

                // تخزين المدينة مع الإحداثيات
                $geocoded = $this->syncCity($country, $cityName);
                if ($geocoded) $geocodedCount++;

                $bar->advance();

                // تأخير صغير لتجنب rate limiting من Google
                if (!$this->option('no-geocode')) {
                    usleep(50000); // 50ms
                }
            }

            $bar->finish();
            $this->newLine();

            $totalCities += count($cities);
            $totalGeocoded += $geocodedCount;

            $this->info("   ✓ Synced " . count($cities) . " cities, geocoded {$geocodedCount}");
            $this->newLine();
        }

        $this->newLine();
        $this->info("🎉 Sync completed!");
        $this->info("   Total cities: {$totalCities}");
        $this->info("   Geocoded: {$totalGeocoded}");

        // مسح الـ cache
        \Illuminate\Support\Facades\Cache::flush();
        $this->info("   Cache cleared");

        return Command::SUCCESS;
    }

    /**
     * مسح البيانات الموجودة
     */
    protected function clearExistingData(array $countries): void
    {
        foreach ($countries as $code => $data) {
            $country = Country::where('country_code', $code)->first();
            if ($country) {
                // مسح المدن والمحافظات المرتبطة
                City::where('country_id', $country->id)->delete();
                State::where('country_id', $country->id)->delete();
                $this->line("   Cleared data for {$data['name']}");
            }
        }
    }

    /**
     * مزامنة الدولة
     */
    protected function syncCountry(string $code, array $data): ?Country
    {
        try {
            return Country::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $data['name'],
                    'country_name_ar' => $data['name_ar'],
                    'status' => 1,
                    'tax' => 0,
                ]
            );
        } catch (\Exception $e) {
            Log::error('SyncTryotoCities: Failed to sync country', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * جلب المدن من Tryoto API (مع pagination)
     */
    protected function fetchTryotoCities(string $countryCode): array
    {
        $allCities = [];
        $page = 1;
        $perPage = 100;

        do {
            $result = $this->tryoto->makeApiRequest('POST', '/rest/v2/getCities', [
                'country' => $countryCode,
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

            // التحقق من وصولنا لنهاية الصفحات
        } while (count($allCities) < $totalCount && !empty($cities));

        return $allCities;
    }

    /**
     * مزامنة مدينة واحدة مع الإحداثيات
     */
    protected function syncCity(Country $country, string $cityName): bool
    {
        try {
            // التحقق من وجود المدينة
            $existingCity = City::where('country_id', $country->id)
                ->where('city_name', $cityName)
                ->first();

            // إذا موجودة ولديها إحداثيات، نتخطاها
            if ($existingCity && $existingCity->latitude && $existingCity->longitude) {
                return false;
            }

            // جلب الإحداثيات من Google
            $coordinates = null;
            $arabicName = $cityName;

            if (!$this->option('no-geocode') && $this->googleApiKey) {
                $geoData = $this->geocodeCity($cityName, $country->country_name);
                if ($geoData) {
                    $coordinates = $geoData['coordinates'];
                    $arabicName = $geoData['arabic_name'] ?? $cityName;
                }
            }

            // إنشاء أو تحديث المدينة
            // نستخدم state_id = 0 لأن Tryoto لا يعطينا المحافظات
            City::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'city_name' => $cityName,
                ],
                [
                    'city_name_ar' => $arabicName,
                    'state_id' => 0, // سيتم تحديثه لاحقاً إذا توفرت بيانات المحافظة
                    'latitude' => $coordinates['lat'] ?? null,
                    'longitude' => $coordinates['lng'] ?? null,
                    'status' => 1,
                    'tryoto_supported' => 1, // علامة أن المدينة مدعومة من Tryoto
                ]
            );

            return $coordinates !== null;
        } catch (\Exception $e) {
            Log::warning('SyncTryotoCities: Failed to sync city', [
                'city' => $cityName,
                'country' => $country->country_code,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * جلب الإحداثيات من Google Geocoding API
     */
    protected function geocodeCity(string $cityName, string $countryName): ?array
    {
        try {
            // طلب بالإنجليزية للإحداثيات
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
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

            $location = $data['results'][0]['geometry']['location'];

            // طلب بالعربية للاسم العربي
            $arabicName = $cityName;
            try {
                $arResponse = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$location['lat']},{$location['lng']}",
                    'key' => $this->googleApiKey,
                    'language' => 'ar',
                ]);

                if ($arResponse->successful()) {
                    $arData = $arResponse->json();
                    if ($arData['status'] === 'OK' && !empty($arData['results'])) {
                        foreach ($arData['results'][0]['address_components'] as $component) {
                            if (in_array('locality', $component['types'])) {
                                $arabicName = $component['long_name'];
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // تجاهل أخطاء الترجمة
            }

            return [
                'coordinates' => $location,
                'arabic_name' => $arabicName,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
