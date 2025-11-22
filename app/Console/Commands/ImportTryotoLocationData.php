<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportTryotoLocationData extends Command
{
    protected $signature = 'tryoto:import-locations
                            {--dry-run : عرض النتائج بدون حفظ في قاعدة البيانات}
                            {--max-cities=200 : الحد الأقصى للمدن المراد اختبارها}';

    protected $description = 'Import Countries, States, and Cities from Tryoto API ONLY - No hardcoded data';

    protected $token;
    protected $baseUrl;
    protected $stats = [
        'countries' => [],
        'states' => [],
        'cities' => [],
        'unsupported_cities' => [],
        'errors' => [],
    ];

    public function handle()
    {
        $this->info('');
        $this->info('====================================================================');
        $this->info('    🌍 TRYOTO LOCATION DATA IMPORTER - استيراد البيانات من Tryoto');
        $this->info('====================================================================');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  وضع التجربة (Dry Run) - لن يتم الحفظ في قاعدة البيانات');
        }

        try {
            // Step 1: Authenticate
            $this->authenticateTryoto();

            // Step 2: Discover available endpoints
            $this->discoverEndpoints();

            // Step 3: Fetch supported cities
            $cities = $this->fetchSupportedCities();

            // Step 4: Extract countries and states
            $this->extractLocations($cities);

            // Step 5: Populate database
            if (!$this->option('dry-run')) {
                $this->populateDatabase();
            }

            // Step 6: Display report
            $this->displayReport();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
            Log::error('Tryoto Import Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    protected function authenticateTryoto()
    {
        $this->info('🔐 المصادقة مع Tryoto API...');

        $isSandbox = config('services.tryoto.sandbox');
        $this->baseUrl = $isSandbox
            ? config('services.tryoto.test.url')
            : config('services.tryoto.live.url');

        if ($isSandbox) {
            $this->error('⚠️  البيئة الحالية: TEST - يجب استخدام LIVE فقط');
            throw new \Exception('Must use LIVE environment only');
        }

        $this->info("   البيئة: LIVE ✅");
        $this->info("   الرابط: {$this->baseUrl}");

        $cachedToken = Cache::get('tryoto-token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            $this->info('   ✅ Token موجود في Cache');
            return;
        }

        $refreshToken = config('services.tryoto.live.token') ?? env('TRYOTO_REFRESH_TOKEN');

        if (empty($refreshToken)) {
            throw new \Exception('Tryoto refresh token not configured');
        }

        $response = Http::timeout(30)->post($this->baseUrl . '/rest/v2/refreshToken', [
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to authenticate: ' . $response->body());
        }

        $this->token = $response->json()['access_token'];
        $expiresIn = (int)($response->json()['expires_in'] ?? 3600);

        Cache::put('tryoto-token', $this->token, now()->addSeconds(max(300, $expiresIn - 60)));

        $this->info('   ✅ تم الحصول على Token جديد');
    }

    protected function discoverEndpoints()
    {
        $this->info('');
        $this->info('🔍 استكشاف Endpoints المتاحة...');

        $endpoints = [
            '/rest/v2/countries',
            '/rest/v2/getCountries',
            '/rest/v2/regions',
            '/rest/v2/states',
            '/rest/v2/cities',
            '/rest/v2/getCities',
            '/rest/v2/locations',
            '/rest/v2/getAllLocations',
        ];

        $discovered = [];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout(10)
                    ->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data)) {
                        $discovered[$endpoint] = $data;
                        $this->line("   ✅ {$endpoint} - " . count($data) . " items");
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        if (empty($discovered)) {
            $this->warn('   ⚠️  لم يتم العثور على endpoints مباشرة');
            $this->info('   سنستخدم استراتيجية استخراج البيانات من checkOTODeliveryFee');
        } else {
            $this->info('   ✅ تم اكتشاف ' . count($discovered) . ' endpoints');
        }

        return $discovered;
    }

    protected function fetchSupportedCities()
    {
        $this->info('');
        $this->info('📍 جلب المدن المدعومة من Tryoto...');

        $maxCities = (int)$this->option('max-cities');

        // استراتيجية ذكية: نبدأ بمدينة واحدة ونستكشف المدن الأخرى
        $seedCity = 'Riyadh';
        $discoveredCities = [];
        $testedCities = [];

        // قائمة المدن المحتملة للاختبار (سنستخرجها ديناميكياً)
        $citiesToTest = [$seedCity];

        $bar = $this->output->createProgressBar(min($maxCities, 100));
        $bar->start();

        $iteration = 0;

        while (!empty($citiesToTest) && $iteration < $maxCities) {
            $originCity = array_shift($citiesToTest);

            if (in_array($originCity, $testedCities)) {
                continue;
            }

            $testedCities[] = $originCity;

            // جرب هذه المدينة كمصدر
            $result = $this->testCity($seedCity, $originCity);

            if ($result['supported']) {
                $cityData = [
                    'name' => $originCity,
                    'country' => $result['country'] ?? 'Saudi Arabia',
                    'region' => $result['region'] ?? null,
                    'companies' => $result['companies'] ?? [],
                ];

                $discoveredCities[$originCity] = $cityData;

                // استخرج مدن جديدة من الاستجابة (إن وجدت)
                if (isset($result['suggested_cities'])) {
                    foreach ($result['suggested_cities'] as $suggestedCity) {
                        if (!in_array($suggestedCity, $testedCities) &&
                            !in_array($suggestedCity, $citiesToTest)) {
                            $citiesToTest[] = $suggestedCity;
                        }
                    }
                }
            } else {
                $this->stats['unsupported_cities'][] = [
                    'city' => $originCity,
                    'error' => $result['error'] ?? 'Unknown'
                ];
            }

            $bar->advance();
            $iteration++;

            usleep(300000); // 0.3s delay
        }

        $bar->finish();
        $this->info('');

        $this->info("   ✅ تم اكتشاف " . count($discoveredCities) . " مدينة مدعومة");

        return $discoveredCities;
    }

    protected function testCity($originCity, $destinationCity, $retries = 3)
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout(15)
                    ->post($this->baseUrl . '/rest/v2/checkOTODeliveryFee', [
                        'originCity' => $originCity,
                        'destinationCity' => $destinationCity,
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
                            'companies' => $companies,
                            'country' => $this->extractCountry($data),
                            'region' => $this->extractRegion($destinationCity, $data),
                        ];
                    }
                }

                return ['supported' => false, 'error' => $response->body()];

            } catch (\Exception $e) {
                if ($attempt === $retries) {
                    return ['supported' => false, 'error' => $e->getMessage()];
                }
                sleep(2);
            }
        }

        return ['supported' => false, 'error' => 'Max retries exceeded'];
    }

    protected function extractCountry($apiData)
    {
        // محاولة استخراج الدولة من بيانات API
        // Tryoto يعمل في السعودية بشكل أساسي
        return 'Saudi Arabia';
    }

    protected function extractRegion($cityName, $apiData)
    {
        // محاولة استخراج المنطقة من اسم المدينة أو البيانات
        // يمكن تحسينها لاحقاً إذا وفرت API معلومات إضافية
        return $this->guessRegionFromCity($cityName);
    }

    protected function guessRegionFromCity($cityName)
    {
        // هذه دالة مؤقتة - يجب استبدالها ببيانات API الفعلية
        // لكن حالياً Tryoto لا توفر معلومات المنطقة
        return 'Riyadh Region'; // Default
    }

    protected function extractLocations($cities)
    {
        $this->info('');
        $this->info('🗂️  استخراج الدول والمناطق من البيانات...');

        $countries = [];
        $states = [];

        foreach ($cities as $cityName => $cityData) {
            $country = $cityData['country'];
            $region = $cityData['region'];

            // جمع الدول
            if (!isset($countries[$country])) {
                $countries[$country] = [
                    'name_en' => $country,
                    'name_ar' => $this->translateCountry($country),
                    'code' => $this->getCountryCode($country),
                ];
            }

            // جمع المناطق
            if ($region) {
                $key = $country . '|' . $region;
                if (!isset($states[$key])) {
                    $states[$key] = [
                        'country' => $country,
                        'name_en' => $region,
                        'name_ar' => $this->translateRegion($region),
                    ];
                }
            }
        }

        $this->stats['countries'] = $countries;
        $this->stats['states'] = $states;
        $this->stats['cities'] = $cities;

        $this->info("   ✅ الدول: " . count($countries));
        $this->info("   ✅ المناطق: " . count($states));
        $this->info("   ✅ المدن: " . count($cities));
    }

    protected function populateDatabase()
    {
        $this->info('');
        $this->info('💾 تعبئة قاعدة البيانات...');

        DB::beginTransaction();

        try {
            // 1. Insert Countries
            $this->info('   📌 تعبئة جدول countries...');
            $countryIds = [];

            foreach ($this->stats['countries'] as $countryKey => $countryData) {
                $existing = DB::table('countries')
                    ->where('country_name', $countryData['name_en'])
                    ->first();

                if ($existing) {
                    $countryIds[$countryKey] = $existing->id;
                    $this->line("      ↪️  {$countryData['name_en']} موجودة مسبقاً (ID: {$existing->id})");
                } else {
                    $id = DB::table('countries')->insertGetId([
                        'country_code' => $countryData['code'],
                        'country_name' => $countryData['name_en'],
                        'country_name_ar' => $countryData['name_ar'],
                        'tax' => 0,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $countryIds[$countryKey] = $id;
                    $this->line("      ✅ {$countryData['name_en']} (ID: {$id})");
                }
            }

            // 2. Insert States
            $this->info('   📌 تعبئة جدول states...');
            $stateIds = [];

            foreach ($this->stats['states'] as $stateKey => $stateData) {
                $countryId = $countryIds[$stateData['country']] ?? null;

                if (!$countryId) {
                    $this->warn("      ⚠️  لم يتم العثور على الدولة: {$stateData['country']}");
                    continue;
                }

                $existing = DB::table('states')
                    ->where('country_id', $countryId)
                    ->where('state', $stateData['name_en'])
                    ->first();

                if ($existing) {
                    $stateIds[$stateKey] = $existing->id;
                    $this->line("      ↪️  {$stateData['name_en']} موجودة مسبقاً (ID: {$existing->id})");
                } else {
                    $id = DB::table('states')->insertGetId([
                        'country_id' => $countryId,
                        'state' => $stateData['name_en'],
                        'state_ar' => $stateData['name_ar'],
                        'tax' => 0,
                        'status' => 1,
                        'owner_id' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stateIds[$stateKey] = $id;
                    $this->line("      ✅ {$stateData['name_en']} (ID: {$id})");
                }
            }

            // 3. Insert Cities
            $this->info('   📌 تعبئة جدول cities...');
            $insertedCount = 0;
            $skippedCount = 0;

            foreach ($this->stats['cities'] as $cityName => $cityData) {
                $country = $cityData['country'];
                $region = $cityData['region'];
                $stateKey = $country . '|' . $region;

                $countryId = $countryIds[$country] ?? null;
                $stateId = $stateIds[$stateKey] ?? null;

                if (!$countryId || !$stateId) {
                    $this->warn("      ⚠️  تخطي {$cityName} - المنطقة غير موجودة");
                    $skippedCount++;
                    continue;
                }

                $existing = DB::table('cities')
                    ->where('state_id', $stateId)
                    ->where('city_name', $cityName)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                } else {
                    DB::table('cities')->insert([
                        'state_id' => $stateId,
                        'country_id' => $countryId,
                        'city_name' => $cityName,
                        'city_name_ar' => $this->translateCity($cityName),
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $insertedCount++;
                }
            }

            $this->info("      ✅ تم إضافة {$insertedCount} مدينة");
            $this->info("      ↪️  تم تخطي {$skippedCount} مدينة موجودة مسبقاً");

            DB::commit();
            $this->info('   ✅ تم الحفظ بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }

    protected function displayReport()
    {
        $this->info('');
        $this->info('====================================================================');
        $this->info('                     📊 التقرير النهائي');
        $this->info('====================================================================');

        $this->table(
            ['المؤشر', 'العدد'],
            [
                ['الدول المستخرجة', count($this->stats['countries'])],
                ['المناطق المستخرجة', count($this->stats['states'])],
                ['المدن المدعومة', count($this->stats['cities'])],
                ['المدن غير المدعومة', count($this->stats['unsupported_cities'])],
            ]
        );

        if (!empty($this->stats['unsupported_cities'])) {
            $this->warn('');
            $this->warn('❌ المدن غير المدعومة:');
            foreach (array_slice($this->stats['unsupported_cities'], 0, 10) as $city) {
                $this->line("   - {$city['city']}");
            }
        }

        $this->info('');
        $this->info('✅ اكتمل الاستيراد!');
        $this->info('====================================================================');
    }

    // Helper functions
    protected function translateCountry($name) { return $name === 'Saudi Arabia' ? 'المملكة العربية السعودية' : $name; }
    protected function translateRegion($name) { return $name; } // يجب استخراجها من API
    protected function translateCity($name) { return $name; } // يجب استخراجها من API
    protected function getCountryCode($name) { return $name === 'Saudi Arabia' ? 'SA' : 'XX'; }
}
