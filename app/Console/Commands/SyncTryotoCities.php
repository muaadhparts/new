<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Services\TryotoService;
use App\Services\ApiCredentialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * مزامنة المدن المدعومة من Tryoto إلى قاعدة البيانات
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن
 * - البيانات: city_name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
 * - لا يوجد اسم عربي للمدن
 * - يُشغّل يدوياً فقط - لا علاقة بالـ checkout
 */
class SyncTryotoCities extends Command
{
    protected $signature = 'tryoto:sync-cities
                            {--country= : Sync specific country code (SA, AE, IQ, etc.)}
                            {--fresh : Delete existing data and sync fresh}
                            {--no-geocode : Skip geocoding (faster, coordinates fetched later by cities:geocode)}';

    protected $description = 'Sync all Tryoto supported cities to database';

    protected TryotoService $tryoto;
    protected ApiCredentialService $credentialService;
    protected string $googleApiKey;

    /**
     * الدول المدعومة من Tryoto
     */
    protected array $supportedCountries = [
        'SA' => 'Saudi Arabia',
        'AE' => 'United Arab Emirates',
        'IQ' => 'Iraq',
        'JO' => 'Jordan',
        'KW' => 'Kuwait',
        'BH' => 'Bahrain',
        'OM' => 'Oman',
        'QA' => 'Qatar',
        'EG' => 'Egypt',
    ];

    public function handle(): int
    {
        $this->tryoto = app(TryotoService::class);
        $this->credentialService = app(ApiCredentialService::class);
        $this->googleApiKey = $this->credentialService->getGoogleMapsKey() ?? '';

        $this->info('Starting Tryoto Cities Sync...');
        $this->newLine();

        // تحديد الدول للمزامنة
        $countryCode = $this->option('country');
        $countries = $countryCode
            ? [$countryCode => $this->supportedCountries[$countryCode] ?? $countryCode]
            : $this->supportedCountries;

        // مسح البيانات القديمة إذا طُلب
        if ($this->option('fresh')) {
            $this->warn('Fresh sync requested - clearing existing data...');
            $this->clearExistingData($countries);
        }

        $totalCities = 0;
        $totalGeocoded = 0;

        foreach ($countries as $code => $countryName) {
            $this->info("Processing {$countryName} ({$code})...");

            // 1. إنشاء/تحديث الدولة
            $country = $this->syncCountry($code, $countryName);
            if (!$country) {
                $this->error("   Failed to create country: {$countryName}");
                continue;
            }

            // 2. جلب المدن من Tryoto API
            $cities = $this->fetchTryotoCities($code);
            if (empty($cities)) {
                $this->warn("   No cities found for {$code}");
                continue;
            }

            $this->info("   Found " . count($cities) . " cities from Tryoto API");

            // 3. مزامنة المدن
            $bar = $this->output->createProgressBar(count($cities));
            $bar->start();

            $geocodedCount = 0;
            foreach ($cities as $cityData) {
                $cityName = $cityData['name'] ?? '';
                if (empty($cityName)) continue;

                $geocoded = $this->syncCity($country, $cityName);
                if ($geocoded) $geocodedCount++;

                $bar->advance();

                // تأخير صغير لتجنب rate limiting
                if (!$this->option('no-geocode') && $this->googleApiKey) {
                    usleep(50000); // 50ms
                }
            }

            $bar->finish();
            $this->newLine();

            $totalCities += count($cities);
            $totalGeocoded += $geocodedCount;

            $this->info("   Synced " . count($cities) . " cities, geocoded {$geocodedCount}");
            $this->newLine();
        }

        $this->newLine();
        $this->info("Sync completed!");
        $this->info("   Total cities: {$totalCities}");
        $this->info("   Geocoded: {$totalGeocoded}");

        if ($totalGeocoded < $totalCities) {
            $this->warn("   Run 'php artisan cities:geocode' to fetch remaining coordinates");
        }

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
        foreach ($countries as $code => $name) {
            $country = Country::where('country_code', $code)->first();
            if ($country) {
                City::where('country_id', $country->id)->delete();
                $this->line("   Cleared data for {$name}");
            }
        }
    }

    /**
     * مزامنة الدولة
     */
    protected function syncCountry(string $code, string $name): ?Country
    {
        try {
            return Country::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $name,
                    'status' => 1,
                    'tax' => 0,
                    'is_synced' => 1,
                    'synced_at' => now(),
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
     * جلب المدن من Tryoto API
     */
    protected function fetchTryotoCities(string $countryCode): array
    {
        $allCities = [];
        $page = 1;

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

        } while (count($allCities) < $totalCount && !empty($cities));

        return $allCities;
    }

    /**
     * مزامنة مدينة واحدة
     * البيانات: city_name فقط (إنجليزي) + tryoto_supported + coordinates
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

            // جلب الإحداثيات من Google (اختياري)
            $coordinates = null;
            if (!$this->option('no-geocode') && $this->googleApiKey) {
                $coordinates = $this->geocodeCity($cityName, $country->country_name);
            }

            // إنشاء أو تحديث المدينة
            City::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'city_name' => $cityName,
                ],
                [
                    'latitude' => $coordinates['lat'] ?? null,
                    'longitude' => $coordinates['lng'] ?? null,
                    'status' => 1,
                    'tryoto_supported' => 1,
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
     * يرجع الإحداثيات فقط - لا أسماء عربية
     */
    protected function geocodeCity(string $cityName, string $countryName): ?array
    {
        try {
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

            return $data['results'][0]['geometry']['location'];

        } catch (\Exception $e) {
            return null;
        }
    }
}
