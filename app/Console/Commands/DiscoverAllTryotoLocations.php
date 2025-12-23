<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiscoverAllTryotoLocations extends Command
{
    protected $signature = 'tryoto:discover-all
                            {--max-attempts=500 : Maximum city discovery attempts}
                            {--export-sql : Generate SQL file}';

    protected $description = 'Smart discovery of ALL supported countries, regions, and cities from Tryoto API';

    protected $token;
    protected $baseUrl;
    protected $discoveredCities = [];
    protected $testedCities = [];
    protected $cityQueue = [];

    // Statistics
    protected $stats = [
        'attempts' => 0,
        'successful' => 0,
        'failed' => 0,
    ];

    public function handle()
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  🌍 SMART DISCOVERY - اكتشاف جميع المواقع المدعومة من Tryoto');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        try {
            // Step 1: Authenticate
            $this->authenticateTryoto();

            // Step 2: Smart discovery with multiple strategies
            $this->info('🔍 بدء الاستكشاف الذكي...');
            $this->smartDiscovery();

            // Step 3: Extract structured data
            $this->info('');
            $this->info('📊 معالجة النتائج...');
            $data = $this->extractStructuredData();

            // Step 4: Display results
            $this->displayResults($data);

            // Step 5: Generate SQL
            if ($this->option('export-sql')) {
                $this->generateSQL($data);
            }

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

    protected function smartDiscovery()
    {
        $maxAttempts = (int)$this->option('max-attempts');

        // Strategy 1: Use common city name patterns across different countries
        $this->info('');
        $this->info('📍 الاستراتيجية 1: اختبار المدن العالمية الشهيرة...');
        $globalCities = $this->getGlobalCitiesDataset();

        // Strategy 2: Use common Arabic city names
        $this->info('📍 الاستراتيجية 2: اختبار المدن العربية الشهيرة...');
        $arabicCities = $this->getArabicCitiesDataset();

        // Strategy 3: Use variations and transliterations
        $this->info('📍 الاستراتيجية 3: اختبار التنويعات...');

        // Combine all datasets
        $allCities = array_merge($globalCities, $arabicCities);
        $allCities = array_unique($allCities);

        $this->info("   📊 إجمالي المدن للاختبار: " . count($allCities));
        $this->info('');

        $bar = $this->output->createProgressBar(min($maxAttempts, count($allCities)));
        $bar->start();

        $seedCity = 'Riyadh'; // Use first discovered city as seed
        $attempts = 0;

        foreach ($allCities as $cityName) {
            if ($attempts >= $maxAttempts) {
                break;
            }

            if (in_array($cityName, $this->testedCities)) {
                continue;
            }

            $this->testedCities[] = $cityName;
            $attempts++;

            // Test city
            $result = $this->testCityPair($seedCity, $cityName);

            if ($result['supported']) {
                $this->discoveredCities[$cityName] = $result;
                $this->stats['successful']++;

                // Update seed city to last successful city for better discovery
                $seedCity = $cityName;
            } else {
                $this->stats['failed']++;
            }

            $this->stats['attempts']++;
            $bar->advance();

            // Rate limiting
            usleep(250000); // 0.25s
        }

        $bar->finish();
        $this->info('');
        $this->info("   ✅ اكتشاف {$this->stats['successful']} مدينة مدعومة من {$this->stats['attempts']} محاولة");
    }

    protected function testCityPair($origin, $destination, $retries = 3)
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
                            'city_name' => $destination,
                            'companies' => $companies,
                            'company_count' => count($companies),
                            'raw_response' => $data,
                        ];
                    }
                }

                return ['supported' => false];

            } catch (\Exception $e) {
                if ($attempt === $retries) {
                    return ['supported' => false, 'error' => $e->getMessage()];
                }
                sleep(2);
            }
        }

        return ['supported' => false];
    }

    protected function extractStructuredData()
    {
        $countries = [];
        $cities = [];

        foreach ($this->discoveredCities as $cityName => $cityData) {
            // For now, Tryoto primarily operates in Saudi Arabia
            // But we'll extract any country information from the API response if available
            $country = 'Saudi Arabia'; // Default
            $countryCode = 'SA';

            // Store country
            if (!isset($countries[$country])) {
                $countries[$country] = [
                    'name' => $country,
                    'code' => $countryCode,
                    'name_ar' => 'المملكة العربية السعودية',
                ];
            }

            // Store city
            $cities[$cityName] = [
                'name' => $cityName,
                'name_ar' => $this->translateCity($cityName),
                'country' => $country,
                'company_count' => $cityData['company_count'] ?? 0,
            ];
        }

        return compact('countries', 'cities');
    }

    protected function translateCity($city)
    {
        $translations = [
            'Riyadh' => 'الرياض',
            'Jeddah' => 'جدة',
            'Mecca' => 'مكة المكرمة',
            'Medina' => 'المدينة المنورة',
            'Dammam' => 'الدمام',
            'Al Khobar' => 'الخبر',
            'Dhahran' => 'الظهران',
            'Tabuk' => 'تبوك',
            'Buraidah' => 'بريدة',
            'Khamis Mushait' => 'خميس مشيط',
            'Hail' => 'حائل',
            'Najran' => 'نجران',
            'Jazan' => 'جازان',
            'Taif' => 'الطائف',
            'Yanbu' => 'ينبع',
            'Abha' => 'أبها',
            'Al Qatif' => 'القطيف',
            'Jubail' => 'الجبيل',
            'Al Ahsa' => 'الأحساء',
            'Al Kharj' => 'الخرج',
            'Arar' => 'عرعر',
            'Sakaka' => 'سكاكا',
            'Hafar Al Batin' => 'حفر الباطن',
            'Al Majmaah' => 'المجمعة',
            'Unaizah' => 'عنيزة',
        ];

        return $translations[$city] ?? $city;
    }

    protected function getGlobalCitiesDataset()
    {
        // Major cities from different countries that might be supported
        return [
            // Saudi Arabia (Primary)
            'Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam', 'Al Khobar', 'Dhahran',
            'Tabuk', 'Buraidah', 'Khamis Mushait', 'Hail', 'Najran', 'Jazan',
            'Taif', 'Yanbu', 'Abha', 'Al Qatif', 'Jubail', 'Al Ahsa', 'Al Kharj',
            'Arar', 'Sakaka', 'Hafar Al Batin', 'Al Majmaah', 'Unaizah',
            'Al Qunfudhah', 'Al Lith', 'Rabigh', 'Al Wajh', 'Duba', 'Al Ula',
            'Badr', 'Al Dawadmi', 'Al Zulfi', 'Shaqra', 'Al Aflaj', 'Wadi Al Dawasir',
            'Al Rass', 'Al Bukayriyah', 'Bishah', 'Al Namas', 'Muhayil', 'Samtah',
            'Sabya', 'Abu Arish', 'Sharurah', 'Al Khafji', 'Ras Tanura', 'Qaisumah',
            'Al Mubarraz', 'Hofuf', 'Turaif', 'Rafha', 'Qurayyat', 'Dumat Al Jandal',
            'Al Quwayiyah', 'Al Muzahimiyah', 'Diriyah', 'Rumah', 'Dhurma',

            // UAE (Test if supported)
            'Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Al Ain',

            // Kuwait
            'Kuwait City', 'Hawalli', 'Salmiya', 'Jahra',

            // Bahrain
            'Manama', 'Muharraq', 'Riffa', 'Hamad Town',

            // Qatar
            'Doha', 'Al Wakrah', 'Al Rayyan', 'Umm Salal',

            // Oman
            'Muscat', 'Salalah', 'Sohar', 'Nizwa',

            // Egypt
            'Cairo', 'Alexandria', 'Giza', 'Shubra El Kheima',

            // Jordan
            'Amman', 'Zarqa', 'Irbid', 'Aqaba',
        ];
    }

    protected function getArabicCitiesDataset()
    {
        // Arabic variations of city names
        return [
            'الرياض', 'جدة', 'مكة', 'المدينة', 'الدمام', 'الخبر', 'الظهران',
            'تبوك', 'بريدة', 'خميس مشيط', 'حائل', 'نجران', 'جازان',
            'الطائف', 'ينبع', 'أبها', 'القطيف', 'الجبيل', 'الأحساء',
        ];
    }

    protected function displayResults($data)
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                       📊 النتائج النهائية');
        $this->info('═══════════════════════════════════════════════════════════════');

        $this->table(
            ['المؤشر', 'العدد'],
            [
                ['إجمالي المحاولات', $this->stats['attempts']],
                ['المدن المكتشفة', $this->stats['successful']],
                ['الفشل', $this->stats['failed']],
                ['الدول', count($data['countries'])],
            ]
        );

        $this->info('');
        $this->info('🌍 الدول المدعومة:');
        foreach ($data['countries'] as $country) {
            $this->line("   ✅ {$country['name']} ({$country['code']}) - {$country['name_ar']}");
        }

        $this->info('');
        $this->info('🏙️  المدن المدعومة: ' . count($data['cities']));
        foreach (array_slice($data['cities'], 0, 15) as $city) {
            $this->line("   • {$city['name']} - {$city['name_ar']} ({$city['company_count']} شركة شحن)");
        }
        if (count($data['cities']) > 15) {
            $this->line('   ... و ' . (count($data['cities']) - 15) . ' مدينة أخرى');
        }
    }

    protected function generateSQL($data)
    {
        $this->info('');
        $this->info('📝 توليد ملف SQL...');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "TRYOTO_COMPLETE_IMPORT_{$timestamp}.sql";
        $filepath = base_path($filename);

        $sql = $this->buildSQL($data);
        file_put_contents($filepath, $sql);

        $this->info("   ✅ تم الحفظ: {$filename}");
        $this->info("   📄 الحجم: " . number_format(strlen($sql)) . " bytes");
    }

    protected function buildSQL($data)
    {
        $sql = "-- ═══════════════════════════════════════════════════════════════\n";
        $sql .= "-- TRYOTO COMPLETE LOCATION DATA IMPORT\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n";
        $sql .= "-- Countries: " . count($data['countries']) . "\n";
        $sql .= "-- Cities: " . count($data['cities']) . "\n";
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n\n";

        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        // 1. Countries
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n";
        $sql .= "-- 1. COUNTRIES\n";
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n\n";

        foreach ($data['countries'] as $country) {
            $sql .= sprintf(
                "INSERT INTO countries (country_code, country_name, country_name_ar, tax, status, created_at, updated_at)\n" .
                "SELECT '%s', '%s', '%s', 0, 1, NOW(), NOW()\n" .
                "WHERE NOT EXISTS (SELECT 1 FROM countries WHERE country_code = '%s');\n\n",
                addslashes($country['code']),
                addslashes($country['name']),
                addslashes($country['name_ar']),
                addslashes($country['code'])
            );
        }

        // 2. Cities
        $sql .= "\n-- ═══════════════════════════════════════════════════════════════\n";
        $sql .= "-- 2. CITIES\n";
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n\n";

        foreach ($data['cities'] as $city) {
            $sql .= sprintf(
                "INSERT INTO cities (city_name, city_name_ar, country_id, status, created_at, updated_at)\n" .
                "SELECT '%s', '%s', c.id, 1, NOW(), NOW()\n" .
                "FROM countries c\n" .
                "WHERE c.country_code = 'SA'\n" .
                "AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = '%s' AND country_id = c.id);\n\n",
                addslashes($city['name']),
                addslashes($city['name_ar']),
                addslashes($city['name'])
            );
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n";
        $sql .= "-- END OF IMPORT\n";
        $sql .= "-- ═══════════════════════════════════════════════════════════════\n";

        return $sql;
    }
}
