<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Services\ApiCredentialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeocodeCities - تحديث إحداثيات المدن من Google Maps
 *
 * يعمل في الخلفية ويشيك على جدول المدن
 * إذا لقى مدينة بدون latitude أو longitude يجلبها من Google Maps
 * يتم تنظيف أسماء المدن (إزالة العلامات والفراغات) للمطابقة الصحيحة
 *
 * ملاحظة: لا يتم جلب أسماء عربية - الاسم الإنجليزي هو المرجع الوحيد
 */
class GeocodeCities extends Command
{
    protected $signature = 'cities:geocode
                            {--country= : Country ID or code to geocode (optional)}
                            {--limit=50 : Maximum number of cities to geocode per run}
                            {--delay=300 : Delay between API calls in milliseconds}
                            {--quiet-log : Only log errors, skip success messages}';

    protected $description = 'Background job: Geocode cities missing latitude/longitude from Google Maps';

    protected string $googleApiKey;

    public function handle(): int
    {
        $credentialService = app(ApiCredentialService::class);
        $this->googleApiKey = $credentialService->getGoogleMapsKey() ?? '';

        if (empty($this->googleApiKey)) {
            $this->error('Google Maps API key not configured! Add it via Operator Panel > API Credentials.');
            Log::error('GeocodeCities: Google Maps API key not configured');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $countryFilter = $this->option('country');
        $quietLog = $this->option('quiet-log');

        // بناء الاستعلام - المدن بدون إحداثيات
        $query = City::where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->where('tryoto_supported', 1);

        // فلترة حسب الدولة إذا تم تحديدها
        if ($countryFilter) {
            $country = Country::where('id', $countryFilter)
                ->orWhere('country_code', strtoupper($countryFilter))
                ->first();

            if (!$country) {
                $this->error("Country not found: {$countryFilter}");
                return 1;
            }

            $query->where('country_id', $country->id);
            if (!$quietLog) {
                $this->info("Filtering by country: {$country->country_name}");
            }
        }

        $cities = $query->limit($limit)->get();

        if ($cities->isEmpty()) {
            if (!$quietLog) {
                $this->info('All cities already have coordinates!');
            }
            return 0;
        }

        if (!$quietLog) {
            $this->info("Found {$cities->count()} cities without coordinates. Starting geocoding...");
            $bar = $this->output->createProgressBar($cities->count());
            $bar->start();
        }

        $success = 0;
        $failed = 0;

        foreach ($cities as $city) {
            $result = $this->geocodeCity($city);

            if ($result) {
                $success++;
            } else {
                $failed++;
            }

            if (!$quietLog && isset($bar)) {
                $bar->advance();
            }

            // تأخير بين الطلبات لتجنب rate limiting
            usleep($delay * 1000);
        }

        if (!$quietLog && isset($bar)) {
            $bar->finish();
            $this->newLine(2);
            $this->info("Geocoding completed!");
            $this->info("  Success: {$success}");
            $this->info("  Failed: {$failed}");
        }

        // تسجيل النتيجة
        Log::info('GeocodeCities: Run completed', [
            'success' => $success,
            'failed' => $failed,
            'country' => $countryFilter ?? 'all'
        ]);

        // عرض عدد المدن المتبقية
        $remaining = City::where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->where('tryoto_supported', 1)
            ->count();

        if ($remaining > 0 && !$quietLog) {
            $this->warn("  Remaining cities without coordinates: {$remaining}");
        }

        return 0;
    }

    /**
     * تنظيف اسم المدينة للبحث
     * يزيل العلامات الخاصة والفراغات الزائدة
     */
    protected function normalizeCityName(string $name): string
    {
        // إزالة العلامات الخاصة مع الاحتفاظ بالحروف والأرقام والمسافات
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);

        // إزالة الفراغات المتعددة
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // إزالة الفراغات من البداية والنهاية
        return trim($normalized);
    }

    /**
     * جلب إحداثيات مدينة واحدة من Google Maps
     * ملاحظة: يتم جلب الإحداثيات فقط - لا أسماء عربية
     */
    protected function geocodeCity(City $city): bool
    {
        try {
            // تحميل الدولة
            $country = $city->country;
            if (!$country) {
                Log::warning("GeocodeCities: City has no country", ['city_id' => $city->id]);
                return false;
            }

            // تنظيف اسم المدينة للبحث
            $cleanCityName = $this->normalizeCityName($city->city_name);

            // بناء عنوان البحث
            $searchAddress = "{$cleanCityName}, {$country->country_name}";

            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $searchAddress,
                'key' => $this->googleApiKey,
                'language' => 'en',
            ]);

            if (!$response->successful()) {
                Log::warning("GeocodeCities: API request failed", [
                    'city' => $city->city_name,
                    'clean_name' => $cleanCityName,
                    'status' => $response->status()
                ]);
                return false;
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                // محاولة ثانية بدون تنظيف الاسم
                if ($cleanCityName !== $city->city_name) {
                    return $this->geocodeCityRaw($city, $country);
                }

                Log::debug("GeocodeCities: No results for city", [
                    'city' => $city->city_name,
                    'clean_name' => $cleanCityName,
                    'status' => $data['status']
                ]);
                return false;
            }

            $location = $data['results'][0]['geometry']['location'];

            // تحديث الإحداثيات فقط - لا أسماء عربية
            $city->update([
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ]);

            Log::debug("GeocodeCities: City geocoded", [
                'city' => $city->city_name,
                'lat' => $location['lat'],
                'lng' => $location['lng']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("GeocodeCities: Exception", [
                'city' => $city->city_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * محاولة ثانية بدون تنظيف الاسم
     */
    protected function geocodeCityRaw(City $city, Country $country): bool
    {
        try {
            $searchAddress = "{$city->city_name}, {$country->country_name}";

            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $searchAddress,
                'key' => $this->googleApiKey,
                'language' => 'en',
            ]);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return false;
            }

            $location = $data['results'][0]['geometry']['location'];

            $city->update([
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
