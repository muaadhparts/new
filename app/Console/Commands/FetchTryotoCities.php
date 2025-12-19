<?php

namespace App\Console\Commands;

use App\Services\TryotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FetchTryotoCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tryoto:fetch-cities
                            {--test-count=10 : عدد المدن للاختبار السريع}
                            {--full : اختبار جميع المدن}
                            {--origin=Riyadh : المدينة الأصلية للاختبار}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'جلب جميع المدن المدعومة من Tryoto API وحفظها في ملفات';

    protected TryotoService $tryotoService;
    protected $baseUrl;
    protected $isSandbox;
    protected $results = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('====================================================================');
        $this->info('        🚀 TRYOTO CITIES FETCHER - جلب المدن من Tryoto');
        $this->info('====================================================================');
        $this->info('');

        try {
            // 1. Initialize TryotoService
            $this->tryotoService = app(TryotoService::class);
            $this->authenticate();

            // 2. محاولة جلب المدن من API
            $this->info('🔍 محاولة جلب قائمة المدن من Tryoto API...');
            $apiCities = $this->tryFetchCitiesFromAPI();

            if (!empty($apiCities)) {
                $this->info('✅ تم جلب ' . count($apiCities) . ' مدينة من API مباشرة');
                $citiesToTest = $apiCities;
            } else {
                // 3. استخدام القائمة المحلية
                $this->warn('⚠️  لم يتم العثور على endpoint للمدن، سنستخدم القائمة المحلية');
                $citiesToTest = $this->loadLocalCitiesList();
            }

            // 4. اختبار المدن
            $testCount = $this->option('full') ? count($citiesToTest) : min($this->option('test-count'), count($citiesToTest));
            $this->info("📍 سيتم اختبار {$testCount} مدينة...");

            $this->testCities(array_slice($citiesToTest, 0, $testCount));

            // 5. حفظ النتائج
            $this->saveResults();

            // 6. عرض الملخص
            $this->displaySummary();

            $this->info('');
            $this->info('✅ اكتمل السكربت بنجاح!');
            $this->info('====================================================================');
            $this->info('');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * المصادقة مع Tryoto API باستخدام TryotoService الموحد
     */
    protected function authenticate()
    {
        $this->info('🔐 جاري المصادقة مع Tryoto API عبر TryotoService الموحد...');

        $config = $this->tryotoService->checkConfiguration();
        $this->isSandbox = $config['sandbox'];
        $this->baseUrl = $config['base_url'];

        $mode = $this->isSandbox ? 'TEST' : 'LIVE';
        $this->info("   البيئة: {$mode}");
        $this->info("   الرابط: {$this->baseUrl}");
        $this->info("   مفتاح الـ Cache: {$config['cache_key']}");

        // استخدام TryotoService للحصول على التوكن
        $token = $this->tryotoService->getToken();

        if (!$token) {
            throw new \Exception('فشل في الحصول على Access Token من TryotoService');
        }

        $this->info('   ✅ تم الحصول على Access Token عبر TryotoService');
    }

    /**
     * محاولة جلب المدن من API مباشرة
     */
    protected function tryFetchCitiesFromAPI()
    {
        $possibleEndpoints = [
            '/rest/v2/cities',
            '/rest/v2/getCities',
            '/rest/v2/locations',
            '/rest/v2/getLocations',
            '/rest/v2/supportedCities',
            '/rest/v2/getAllCities',
        ];

        foreach ($possibleEndpoints as $endpoint) {
            $this->line("   - اختبار endpoint: {$endpoint}");

            try {
                $response = Http::withToken($this->token)->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    $data = $response->json();

                    // محاولة استخراج المدن من الاستجابة
                    if (isset($data['cities']) && is_array($data['cities'])) {
                        $this->info("   ✅ نجح! تم العثور على مدن في {$endpoint}");
                        return $data['cities'];
                    } elseif (is_array($data) && !empty($data)) {
                        $this->info("   ✅ نجح! تم العثور على بيانات في {$endpoint}");
                        return $data;
                    }
                }
            } catch (\Exception $e) {
                // تجاهل الأخطاء والاستمرار
            }
        }

        return [];
    }

    /**
     * تحميل قائمة المدن المحلية
     */
    protected function loadLocalCitiesList()
    {
        $jsonPath = base_path('saudi_cities_list.json');

        if (!file_exists($jsonPath)) {
            throw new \Exception('ملف saudi_cities_list.json غير موجود');
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        return $data['saudi_cities'] ?? [];
    }

    /**
     * اختبار المدن
     */
    protected function testCities($cities)
    {
        $originCity = $this->option('origin');
        $this->info("   المدينة الأصلية للاختبار: {$originCity}");
        $this->info('');

        $bar = $this->output->createProgressBar(count($cities));
        $bar->start();

        $supportedCities = [];
        $unsupportedCities = [];
        $errors = [];

        foreach ($cities as $city) {
            $cityName = is_array($city) ? ($city['name_en'] ?? $city['city_name'] ?? 'Unknown') : $city;
            $cityNameAr = is_array($city) ? ($city['name_ar'] ?? '') : '';

            try {
                $result = $this->testCityShipping($originCity, $cityName);

                if ($result['supported']) {
                    $supportedCities[] = [
                        'city_name' => $cityName,
                        'city_name_ar' => $cityNameAr,
                        'region' => $city['region'] ?? '',
                        'delivery_companies_count' => count($result['companies'] ?? []),
                        'companies' => $result['companies'] ?? [],
                        'tested_at' => now()->toDateTimeString(),
                    ];
                } else {
                    $unsupportedCities[] = [
                        'city_name' => $cityName,
                        'city_name_ar' => $cityNameAr,
                        'error' => $result['error'] ?? 'Unknown',
                        'tested_at' => now()->toDateTimeString(),
                    ];
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'city_name' => $cityName,
                    'error' => $e->getMessage(),
                ];
            }

            $bar->advance();

            // تأخير لتجنب Rate Limiting
            usleep(300000); // 0.3 ثانية
        }

        $bar->finish();
        $this->info('');

        $this->results = [
            'supported_cities' => $supportedCities,
            'unsupported_cities' => $unsupportedCities,
            'errors' => $errors,
            'total_tested' => count($cities),
            'total_supported' => count($supportedCities),
            'total_unsupported' => count($unsupportedCities),
            'total_errors' => count($errors),
            'origin_city' => $originCity,
            'tested_at' => now()->toDateTimeString(),
            'environment' => $this->isSandbox ? 'TEST' : 'LIVE',
        ];
    }

    /**
     * اختبار شحن بين مدينتين باستخدام TryotoService
     */
    protected function testCityShipping($originCity, $destinationCity)
    {
        // استخدام TryotoService الموحد بدلاً من الاتصال المباشر
        $result = $this->tryotoService->verifyCitySupport($destinationCity, $originCity);

        if ($result['supported']) {
            return [
                'supported' => true,
                'companies' => $this->formatCompanies($result['companies'] ?? []),
            ];
        }

        return [
            'supported' => false,
            'error' => $result['error'] ?? 'City not supported',
        ];
    }

    /**
     * تنسيق معلومات شركات الشحن
     */
    protected function formatCompanies($companies)
    {
        $formatted = [];

        foreach ($companies as $company) {
            $formatted[] = [
                'delivery_option_id' => $company['deliveryOptionId'] ?? '',
                'company_name' => $company['companyName'] ?? '',
                'service_name' => $company['serviceName'] ?? '',
                'price' => $company['price'] ?? 0,
                'logo' => $company['logo'] ?? '',
            ];
        }

        return $formatted;
    }

    /**
     * حفظ النتائج
     */
    protected function saveResults()
    {
        $this->info('');
        $this->info('💾 جاري حفظ النتائج...');

        $timestamp = now()->format('Y-m-d_H-i-s');

        // Create exports directory if not exists
        $exportsPath = public_path('exports');
        if (!file_exists($exportsPath)) {
            mkdir($exportsPath, 0755, true);
        }

        // 1. JSON كامل
        $jsonFile = "{$exportsPath}/tryoto_cities_full_{$timestamp}.json";
        file_put_contents($jsonFile, json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("   ✅ JSON: public/exports/" . basename($jsonFile));

        // 2. CSV للمدن المدعومة
        $csvFile = "{$exportsPath}/tryoto_supported_cities_{$timestamp}.csv";
        $csvContent = $this->generateSupportedCitiesCSV();
        file_put_contents($csvFile, $csvContent);
        $this->info("   ✅ CSV: public/exports/" . basename($csvFile));

        // 3. CSV مفصل لشركات الشحن
        $detailedCsvFile = "{$exportsPath}/tryoto_cities_detailed_{$timestamp}.csv";
        $detailedContent = $this->generateDetailedCSV();
        file_put_contents($detailedCsvFile, $detailedContent);
        $this->info("   ✅ CSV مفصل: public/exports/" . basename($detailedCsvFile));

        // 4. SQL للإضافة المباشرة
        $sqlFile = "{$exportsPath}/tryoto_cities_insert_{$timestamp}.sql";
        $sqlContent = $this->generateInsertSQL();
        file_put_contents($sqlFile, $sqlContent);
        $this->info("   ✅ SQL: public/exports/" . basename($sqlFile));
    }

    /**
     * توليد CSV للمدن المدعومة
     */
    protected function generateSupportedCitiesCSV()
    {
        $csv = "City Name,City Name AR,Region,Companies Count,Tested At\n";

        foreach ($this->results['supported_cities'] as $city) {
            $csv .= sprintf(
                '"%s","%s","%s",%d,"%s"' . "\n",
                $city['city_name'],
                $city['city_name_ar'] ?? '',
                $city['region'] ?? '',
                $city['delivery_companies_count'],
                $city['tested_at']
            );
        }

        return $csv;
    }

    /**
     * توليد CSV مفصل
     */
    protected function generateDetailedCSV()
    {
        $csv = "City,City AR,Region,Company Name,Service Name,Delivery Option ID,Price,Logo URL\n";

        foreach ($this->results['supported_cities'] as $city) {
            foreach ($city['companies'] as $company) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s",%.2f,"%s"' . "\n",
                    $city['city_name'],
                    $city['city_name_ar'] ?? '',
                    $city['region'] ?? '',
                    $company['company_name'] ?? '',
                    $company['service_name'] ?? '',
                    $company['delivery_option_id'] ?? '',
                    $company['price'] ?? 0,
                    $company['logo'] ?? ''
                );
            }
        }

        return $csv;
    }

    /**
     * توليد SQL لإضافة المدن
     */
    protected function generateInsertSQL()
    {
        $sql = "-- Tryoto Supported Cities\n";
        $sql .= "-- Generated at: " . now()->toDateTimeString() . "\n";
        $sql .= "-- Total cities: " . count($this->results['supported_cities']) . "\n\n";

        $sql .= "-- ⚠️ تحذير: تأكد من مراجعة البيانات قبل التنفيذ\n";
        $sql .= "-- هذا السكربت يضيف المدن إلى جدول cities\n\n";

        foreach ($this->results['supported_cities'] as $city) {
            $cityName = addslashes($city['city_name']);
            $cityNameAr = addslashes($city['city_name_ar'] ?? '');
            $region = addslashes($city['region'] ?? '');

            $sql .= sprintf(
                "-- %s (%s)\n",
                $cityName,
                $cityNameAr
            );

            $sql .= sprintf(
                "INSERT INTO cities (city_name, city_name_ar, state_id, created_at, updated_at) \n" .
                "SELECT '%s', '%s', states.id, NOW(), NOW()\n" .
                "FROM states \n" .
                "WHERE states.state = '%s' OR states.state_ar = '%s'\n" .
                "LIMIT 1;\n\n",
                $cityName,
                $cityNameAr,
                $region,
                $region
            );
        }

        return $sql;
    }

    /**
     * عرض ملخص النتائج
     */
    protected function displaySummary()
    {
        $this->info('');
        $this->info('====================================================================');
        $this->info('                       📊 ملخص النتائج');
        $this->info('====================================================================');
        $this->table(
            ['المؤشر', 'القيمة'],
            [
                ['إجمالي المدن المختبرة', $this->results['total_tested']],
                ['المدن المدعومة', $this->results['total_supported']],
                ['المدن غير المدعومة', $this->results['total_unsupported']],
                ['الأخطاء', $this->results['total_errors']],
                ['البيئة', $this->results['environment']],
                ['المدينة الأصلية', $this->results['origin_city']],
            ]
        );

        $this->info('');
        $this->info('📍 المدن المدعومة:');
        foreach (array_slice($this->results['supported_cities'], 0, 10) as $city) {
            $this->line(sprintf(
                '   ✅ %s (%s) - %d شركة شحن',
                $city['city_name'],
                $city['city_name_ar'] ?? '',
                $city['delivery_companies_count']
            ));
        }

        if (count($this->results['supported_cities']) > 10) {
            $remaining = count($this->results['supported_cities']) - 10;
            $this->line("   ... و {$remaining} مدينة أخرى");
        }

        $this->info('');
    }
}
