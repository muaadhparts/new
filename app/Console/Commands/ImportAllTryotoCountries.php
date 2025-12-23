<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportAllTryotoCountries extends Command
{
    protected $signature = 'tryoto:import-all
                            {--max-cities=300 : Maximum cities to test}
                            {--save-db : Save directly to database}';

    protected $description = 'Discover and import ALL countries, regions, and cities supported by Tryoto';

    protected $token;
    protected $baseUrl;
    protected $discoveredData = [];
    protected $stats = ['attempts' => 0, 'success' => 0, 'failed' => 0];

    // Country code mapping
    protected $countryMappings = [
        'Saudi Arabia' => 'SA',
        'United Arab Emirates' => 'AE',
        'Kuwait' => 'KW',
        'Bahrain' => 'BH',
        'Qatar' => 'QA',
        'Oman' => 'OM',
        'Egypt' => 'EG',
        'Jordan' => 'JO',
        'Lebanon' => 'LB',
        'Iraq' => 'IQ',
        'Yemen' => 'YE',
        'Syria' => 'SY',
        'Palestine' => 'PS',
        'Morocco' => 'MA',
        'Algeria' => 'DZ',
        'Tunisia' => 'TN',
        'Libya' => 'LY',
        'Sudan' => 'SD',
    ];

    public function handle()
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  🌍 COMPLETE IMPORT - استيراد جميع الدول والمدن من Tryoto');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        try {
            $this->authenticateTryoto();
            $this->discoverAllCountriesAndCities();

            if ($this->option('save-db') || $this->confirm('هل تريد حفظ البيانات في قاعدة البيانات؟', true)) {
                $this->saveToDatabase();
            }

            $this->displayFinalReport();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function authenticateTryoto()
    {
        $this->info('🔐 المصادقة...');

        $this->baseUrl = config('services.tryoto.live.url') ?: 'https://api.tryoto.com';
        $cachedToken = Cache::get('tryoto-token');

        if ($cachedToken) {
            $this->token = $cachedToken;
            $this->info('   ✅ Token من Cache');
            return;
        }

        $refreshToken = config('services.tryoto.live.token') ?? env('TRYOTO_REFRESH_TOKEN');
        $response = Http::timeout(30)->post($this->baseUrl . '/rest/v2/refreshToken', [
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('فشل المصادقة');
        }

        $this->token = $response->json()['access_token'];
        $expiresIn = (int)($response->json()['expires_in'] ?? 3600);
        Cache::put('tryoto-token', $this->token, now()->addSeconds($expiresIn - 60));

        $this->info('   ✅ Token جديد');
    }

    protected function discoverAllCountriesAndCities()
    {
        $maxCities = (int)$this->option('max-cities');

        $this->info('');
        $this->info('🌍 اختبار المدن من جميع الدول...');

        $citiesDataset = $this->buildCompleteCitiesDataset();
        $this->info("   📊 إجمالي المدن للاختبار: " . count($citiesDataset));
        $this->info('');

        $bar = $this->output->createProgressBar(min($maxCities, count($citiesDataset)));
        $bar->start();

        $seedCity = 'Riyadh';
        $tested = 0;

        foreach ($citiesDataset as $cityData) {
            if ($tested >= $maxCities) break;

            $cityName = $cityData['name'];
            $country = $cityData['country'];
            $region = $cityData['region'] ?? null;

            $result = $this->testCity($seedCity, $cityName);

            if ($result['supported']) {
                $this->discoveredData[] = [
                    'city' => $cityName,
                    'country' => $country,
                    'country_code' => $this->countryMappings[$country] ?? 'XX',
                    'region' => $region,
                    'companies' => $result['companies'],
                ];

                $this->stats['success']++;
                $seedCity = $cityName; // Use last successful city
            } else {
                $this->stats['failed']++;
            }

            $this->stats['attempts']++;
            $tested++;
            $bar->advance();

            usleep(250000); // 0.25s delay
        }

        $bar->finish();
        $this->info('');
        $this->info("   ✅ تم اكتشاف {$this->stats['success']} مدينة مدعومة");
    }

    protected function testCity($origin, $destination, $retries = 3)
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout(15)
                    ->post($this->baseUrl . '/rest/v2/checkOTODeliveryFee', [
                        'originCity' => $origin,
                        'destinationCity' => $destination,
                        'weight' => 1,
                        'xlength' => 30,
                        'xheight' => 30,
                        'xwidth' => 30,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $companies = $data['deliveryCompany'] ?? [];

                    if (!empty($companies)) {
                        return [
                            'supported' => true,
                            'companies' => count($companies),
                        ];
                    }
                }

                return ['supported' => false];

            } catch (\Exception $e) {
                if ($attempt === $retries) {
                    return ['supported' => false];
                }
                sleep(2);
            }
        }

        return ['supported' => false];
    }

    protected function saveToDatabase()
    {
        $this->info('');
        $this->info('💾 حفظ البيانات في قاعدة البيانات...');

        DB::beginTransaction();

        try {
            // Extract unique countries and cities
            $countries = [];
            $cities = [];

            foreach ($this->discoveredData as $item) {
                $countryKey = $item['country'];
                if (!isset($countries[$countryKey])) {
                    $countries[$countryKey] = [
                        'code' => $item['country_code'],
                        'name' => $item['country'],
                        'name_ar' => $this->translateCountry($item['country']),
                    ];
                }

                $cities[] = [
                    'country' => $countryKey,
                    'name' => $item['city'],
                    'name_ar' => $this->translateCity($item['city']),
                    'companies' => $item['companies'],
                ];
            }

            // 1. Insert Countries
            $this->info('   📌 حفظ الدول...');
            $countryIds = [];
            foreach ($countries as $key => $country) {
                $existing = DB::table('countries')->where('country_code', $country['code'])->first();

                if ($existing) {
                    $countryIds[$key] = $existing->id;
                } else {
                    $id = DB::table('countries')->insertGetId([
                        'country_code' => $country['code'],
                        'country_name' => $country['name'],
                        'country_name_ar' => $country['name_ar'],
                        'tax' => 0,
                        'status' => 1,
                    ]);
                    $countryIds[$key] = $id;
                    $this->line("      ✅ {$country['name']} (ID: {$id})");
                }
            }

            // 2. Insert Cities
            $this->info('   📌 حفظ المدن...');
            $inserted = 0;
            foreach ($cities as $city) {
                $countryId = $countryIds[$city['country']] ?? null;
                if (!$countryId) continue;

                $existing = DB::table('cities')
                    ->where('country_id', $countryId)
                    ->where('city_name', $city['name'])
                    ->first();

                if (!$existing) {
                    DB::table('cities')->insert([
                        'country_id' => $countryId,
                        'city_name' => $city['name'],
                        'city_name_ar' => $city['name_ar'],
                        'status' => 1,
                    ]);
                    $inserted++;
                }
            }

            $this->info("      ✅ تم إضافة {$inserted} مدينة جديدة");

            DB::commit();
            $this->info('   ✅ تم الحفظ بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('خطأ في قاعدة البيانات: ' . $e->getMessage());
        }
    }

    protected function displayFinalReport()
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                       📊 التقرير النهائي');
        $this->info('═══════════════════════════════════════════════════════════════');

        // Group by country
        $byCountry = [];
        foreach ($this->discoveredData as $item) {
            $country = $item['country'];
            if (!isset($byCountry[$country])) {
                $byCountry[$country] = [];
            }
            $byCountry[$country][] = $item['city'];
        }

        $this->table(
            ['المؤشر', 'العدد'],
            [
                ['إجمالي المحاولات', $this->stats['attempts']],
                ['المدن المدعومة', $this->stats['success']],
                ['الفشل', $this->stats['failed']],
                ['الدول المدعومة', count($byCountry)],
            ]
        );

        $this->info('');
        $this->info('🌍 الدول والمدن المدعومة:');
        foreach ($byCountry as $country => $cities) {
            $code = $this->countryMappings[$country] ?? 'XX';
            $this->line("   ✅ {$country} ({$code}) - " . count($cities) . " مدينة");
            foreach (array_slice($cities, 0, 5) as $city) {
                $this->line("      • {$city}");
            }
            if (count($cities) > 5) {
                $this->line("      ... و " . (count($cities) - 5) . " مدينة أخرى");
            }
        }
    }

    protected function buildCompleteCitiesDataset()
    {
        return array_merge(
            $this->getSaudiCities(),
            $this->getUAECities(),
            $this->getKuwaitCities(),
            $this->getBahrainCities(),
            $this->getQatarCities(),
            $this->getOmanCities(),
            $this->getEgyptCities(),
            $this->getJordanCities(),
            $this->getOtherArabCities()
        );
    }

    protected function getSaudiCities()
    {
        $cities = [
            // Major cities
            'Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam', 'Al Khobar', 'Dhahran',
            'Tabuk', 'Buraidah', 'Khamis Mushait', 'Hail', 'Najran', 'Jazan',
            'Taif', 'Yanbu', 'Abha', 'Al Qatif', 'Jubail', 'Al Ahsa', 'Al Kharj',
            'Arar', 'Sakaka', 'Hafar Al Batin', 'Al Majmaah', 'Unaizah',
            'Al Qunfudhah', 'Al Lith', 'Rabigh', 'Al Wajh', 'Duba', 'Al Ula',
            'Badr', 'Al Dawadmi', 'Al Zulfi', 'Shaqra', 'Al Aflaj', 'Wadi Al Dawasir',
            'Al Rass', 'Al Bukayriyah', 'Bishah', 'Al Namas', 'Muhayil', 'Samtah',
            'Sabya', 'Abu Arish', 'Sharurah', 'Al Khafji', 'Ras Tanura', 'Qaisumah',
            'Al Mubarraz', 'Hofuf', 'Turaif', 'Rafha', 'Qurayyat', 'Dumat Al Jandal',
            'Al Quwayiyah', 'Al Muzahimiyah', 'Diriyah', 'Rumah',
        ];

        return array_map(fn($city) => [
            'name' => $city,
            'country' => 'Saudi Arabia',
        ], $cities);
    }

    protected function getUAECities()
    {
        $cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Al Ain', 'Umm Al Quwain'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'United Arab Emirates'], $cities);
    }

    protected function getKuwaitCities()
    {
        $cities = ['Kuwait City', 'Hawalli', 'Salmiya', 'Jahra', 'Ahmadi', 'Farwaniya'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Kuwait'], $cities);
    }

    protected function getBahrainCities()
    {
        $cities = ['Manama', 'Muharraq', 'Riffa', 'Hamad Town', 'Isa Town', 'Sitra'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Bahrain'], $cities);
    }

    protected function getQatarCities()
    {
        $cities = ['Doha', 'Al Wakrah', 'Al Rayyan', 'Umm Salal', 'Al Khor', 'Dukhan'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Qatar'], $cities);
    }

    protected function getOmanCities()
    {
        $cities = ['Muscat', 'Salalah', 'Sohar', 'Nizwa', 'Sur', 'Ibri', 'Barka'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Oman'], $cities);
    }

    protected function getEgyptCities()
    {
        $cities = ['Cairo', 'Alexandria', 'Giza', 'Shubra El Kheima', 'Port Said', 'Suez', 'Luxor', 'Aswan'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Egypt'], $cities);
    }

    protected function getJordanCities()
    {
        $cities = ['Amman', 'Zarqa', 'Irbid', 'Aqaba', 'Madaba', 'Jerash', 'Karak'];
        return array_map(fn($city) => ['name' => $city, 'country' => 'Jordan'], $cities);
    }

    protected function getOtherArabCities()
    {
        return [
            ['name' => 'Beirut', 'country' => 'Lebanon'],
            ['name' => 'Baghdad', 'country' => 'Iraq'],
            ['name' => 'Damascus', 'country' => 'Syria'],
            ['name' => 'Sanaa', 'country' => 'Yemen'],
        ];
    }

    protected function translateCountry($name) {
        $map = ['Saudi Arabia' => 'المملكة العربية السعودية', 'United Arab Emirates' => 'الإمارات العربية المتحدة',
                'Kuwait' => 'الكويت', 'Bahrain' => 'البحرين', 'Qatar' => 'قطر', 'Oman' => 'عُمان',
                'Egypt' => 'مصر', 'Jordan' => 'الأردن'];
        return $map[$name] ?? $name;
    }

    protected function translateCity($name) { return $name; }
}
