<?php

/**
 * ====================================================================
 * TRYOTO CITIES FETCHER
 * ====================================================================
 *
 * هذا السكربت يقوم بجلب جميع المدن المدعومة من Tryoto API
 * ويصدرها إلى ملف JSON وملف Excel
 *
 * الاستخدام:
 * php fetch_tryoto_cities.php
 *
 * ====================================================================
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

// تحميل Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class TryotoCitiesFetcher
{
    protected $token;
    protected $baseUrl;
    protected $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('services.tryoto.sandbox');
        $this->baseUrl = $this->isSandbox
            ? config('services.tryoto.test.url')
            : config('services.tryoto.live.url');

        $this->authenticate();
    }

    /**
     * المصادقة مع Tryoto API
     */
    protected function authenticate()
    {
        echo "🔐 جاري المصادقة مع Tryoto API...\n";

        // محاولة جلب Token من Cache
        $cachedToken = Cache::get('tryoto-token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            echo "✅ تم استخدام Token من Cache\n";
            return;
        }

        // الحصول على refresh token
        $refreshToken = $this->isSandbox
            ? (config('services.tryoto.test.token') ?? env('TRYOTO_TEST_REFRESH_TOKEN'))
            : (config('services.tryoto.live.token') ?? env('TRYOTO_REFRESH_TOKEN'));

        if (empty($refreshToken)) {
            throw new Exception('❌ Tryoto refresh token غير موجود في الإعدادات');
        }

        $response = Http::post($this->baseUrl . '/rest/v2/refreshToken', [
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new Exception('❌ فشل في الحصول على Access Token: ' . $response->body());
        }

        $this->token = $response->json()['access_token'];
        $expiresIn = (int)($response->json()['expires_in'] ?? 3600);

        // حفظ في Cache
        Cache::put('tryoto-token', $this->token, now()->addSeconds(max(300, $expiresIn - 60)));

        echo "✅ تم الحصول على Access Token بنجاح\n";
    }

    /**
     * جلب جميع المدن المدعومة
     *
     * ملاحظة: Tryoto لا يوفر endpoint لجلب قائمة المدن مباشرة
     * سنحاول استخراجها من endpoints أخرى أو من التوثيق
     */
    public function fetchCities()
    {
        echo "\n📍 جاري جلب المدن المدعومة من Tryoto...\n";

        // المدن الشائعة في السعودية (قائمة أولية للاختبار)
        $testCities = [
            'Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam',
            'Khobar', 'Dhahran', 'Tabuk', 'Buraidah', 'Khamis Mushait',
            'Hail', 'Najran', 'Jazan', 'Taif', 'Yanbu',
            'Abha', 'Al Qatif', 'Jubail', 'Al Ahsa', 'Al Kharj',
            'Arar', 'Sakaka', 'Hafar Al Batin', 'Al Majmaah', 'Unaizah'
        ];

        $supportedCities = [];
        $unsupportedCities = [];

        foreach ($testCities as $city) {
            echo "   - اختبار مدينة: {$city}... ";

            $result = $this->testCityShipping($city, $city);

            if ($result['supported']) {
                $supportedCities[] = [
                    'city_name' => $city,
                    'city_name_ar' => $result['city_name_ar'] ?? '',
                    'delivery_companies_count' => count($result['companies'] ?? []),
                    'companies' => $result['companies'] ?? [],
                    'tested_at' => date('Y-m-d H:i:s')
                ];
                echo "✅ مدعومة (" . count($result['companies']) . " شركة شحن)\n";
            } else {
                $unsupportedCities[] = [
                    'city_name' => $city,
                    'error' => $result['error'] ?? 'Unknown',
                    'tested_at' => date('Y-m-d H:i:s')
                ];
                echo "❌ غير مدعومة\n";
            }

            // تأخير لتجنب Rate Limiting
            usleep(500000); // 0.5 ثانية
        }

        return [
            'supported_cities' => $supportedCities,
            'unsupported_cities' => $unsupportedCities,
            'total_tested' => count($testCities),
            'total_supported' => count($supportedCities),
            'total_unsupported' => count($unsupportedCities),
        ];
    }

    /**
     * اختبار شحن بين مدينتين
     */
    protected function testCityShipping($originCity, $destinationCity)
    {
        $requestData = [
            "originCity" => $originCity,
            "destinationCity" => $destinationCity,
            "weight" => 1,
            "xlength" => 30,
            "xheight" => 30,
            "xwidth" => 30,
        ];

        try {
            $response = Http::withToken($this->token)
                ->post($this->baseUrl . '/rest/v2/checkOTODeliveryFee', $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['deliveryCompany'] ?? [];

                return [
                    'supported' => !empty($companies),
                    'companies' => $this->formatCompanies($companies),
                    'raw_response' => $data
                ];
            } else {
                return [
                    'supported' => false,
                    'error' => $response->body(),
                    'status' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'supported' => false,
                'error' => $e->getMessage()
            ];
        }
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
     * محاولة جلب المدن من API مباشرة
     * (إذا كان Tryoto يوفر endpoint لذلك)
     */
    public function fetchCitiesFromAPI()
    {
        echo "\n🔍 محاولة جلب قائمة المدن من Tryoto API مباشرة...\n";

        // جرب endpoints محتملة
        $possibleEndpoints = [
            '/rest/v2/cities',
            '/rest/v2/getCities',
            '/rest/v2/locations',
            '/rest/v2/getLocations',
            '/rest/v2/supportedCities',
        ];

        foreach ($possibleEndpoints as $endpoint) {
            echo "   - جاري اختبار: {$endpoint}... ";

            try {
                $response = Http::withToken($this->token)
                    ->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    echo "✅ نجح!\n";
                    return [
                        'success' => true,
                        'endpoint' => $endpoint,
                        'data' => $response->json()
                    ];
                } else {
                    echo "❌ فشل (Status: {$response->status()})\n";
                }
            } catch (\Exception $e) {
                echo "❌ خطأ: {$e->getMessage()}\n";
            }
        }

        echo "\n⚠️  لم يتم العثور على endpoint لجلب المدن مباشرة\n";
        echo "سنستخدم الطريقة اليدوية (اختبار المدن الشائعة)\n";

        return ['success' => false];
    }

    /**
     * حفظ النتائج إلى ملفات
     */
    public function saveResults($results)
    {
        $timestamp = date('Y-m-d_H-i-s');

        // 1. حفظ JSON
        $jsonFile = storage_path("app/tryoto_cities_{$timestamp}.json");
        file_put_contents($jsonFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n✅ تم حفظ البيانات في: {$jsonFile}\n";

        // 2. حفظ CSV للمدن المدعومة
        $csvFile = storage_path("app/tryoto_supported_cities_{$timestamp}.csv");
        $fp = fopen($csvFile, 'w');

        // رأس الجدول
        fputcsv($fp, ['City Name', 'City Name AR', 'Companies Count', 'Tested At']);

        foreach ($results['supported_cities'] as $city) {
            fputcsv($fp, [
                $city['city_name'],
                $city['city_name_ar'] ?? '',
                $city['delivery_companies_count'],
                $city['tested_at']
            ]);
        }

        fclose($fp);
        echo "✅ تم حفظ CSV في: {$csvFile}\n";

        // 3. حفظ CSV مفصل لشركات الشحن
        $detailedCsvFile = storage_path("app/tryoto_cities_detailed_{$timestamp}.csv");
        $fp = fopen($detailedCsvFile, 'w');

        // رأس الجدول
        fputcsv($fp, ['City', 'Company Name', 'Service Name', 'Delivery Option ID', 'Price', 'Logo']);

        foreach ($results['supported_cities'] as $city) {
            foreach ($city['companies'] as $company) {
                fputcsv($fp, [
                    $city['city_name'],
                    $company['company_name'] ?? '',
                    $company['service_name'] ?? '',
                    $company['delivery_option_id'] ?? '',
                    $company['price'] ?? 0,
                    $company['logo'] ?? ''
                ]);
            }
        }

        fclose($fp);
        echo "✅ تم حفظ CSV المفصل في: {$detailedCsvFile}\n";

        // 4. طباعة ملخص
        $this->printSummary($results);
    }

    /**
     * طباعة ملخص النتائج
     */
    protected function printSummary($results)
    {
        echo "\n";
        echo "====================================================================\n";
        echo "                       📊 ملخص النتائج                            \n";
        echo "====================================================================\n";
        echo "إجمالي المدن المختبرة: {$results['total_tested']}\n";
        echo "المدن المدعومة: {$results['total_supported']}\n";
        echo "المدن غير المدعومة: {$results['total_unsupported']}\n";
        echo "====================================================================\n";

        echo "\n📍 المدن المدعومة:\n";
        foreach ($results['supported_cities'] as $city) {
            echo "   ✅ {$city['city_name']} ({$city['delivery_companies_count']} شركة شحن)\n";
        }

        if (!empty($results['unsupported_cities'])) {
            echo "\n❌ المدن غير المدعومة:\n";
            foreach ($results['unsupported_cities'] as $city) {
                echo "   ❌ {$city['city_name']}\n";
            }
        }

        echo "\n";
    }
}

// ====================================================================
// تشغيل السكربت
// ====================================================================

try {
    echo "\n";
    echo "====================================================================\n";
    echo "        🚀 TRYOTO CITIES FETCHER - جلب المدن من Tryoto           \n";
    echo "====================================================================\n";

    $fetcher = new TryotoCitiesFetcher();

    // 1. محاولة جلب المدن من API مباشرة
    $apiResult = $fetcher->fetchCitiesFromAPI();

    if ($apiResult['success']) {
        // إذا نجح، احفظ البيانات
        $results = [
            'method' => 'api',
            'data' => $apiResult['data'],
            'fetched_at' => date('Y-m-d H:i:s')
        ];
    } else {
        // 2. استخدام الطريقة اليدوية (اختبار المدن)
        $results = $fetcher->fetchCities();
        $results['method'] = 'manual_testing';
        $results['fetched_at'] = date('Y-m-d H:i:s');
    }

    // 3. حفظ النتائج
    $fetcher->saveResults($results);

    echo "\n✅ اكتمل السكربت بنجاح!\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    echo "\n❌ خطأ: {$e->getMessage()}\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
