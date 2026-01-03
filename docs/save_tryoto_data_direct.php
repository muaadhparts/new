<?php

/**
 * ====================================================================
 * SAVE TRYOTO DATA DIRECTLY
 * تشغيل: php save_tryoto_data_direct.php
 * ====================================================================
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "\n";
echo "====================================================================\n";
echo "        📥 SAVE TRYOTO CITIES DATA - حفظ بيانات المدن            \n";
echo "====================================================================\n";
echo "\n";

// Run the command and capture output
ob_start();
Artisan::call('tryoto:fetch-cities', ['--full' => true]);
$output = Artisan::output();
ob_end_clean();

echo $output;

// الحصول على البيانات من ملف JSON الذي تم إنشاؤه
$publicPath = __DIR__ . '/public/exports';

if (!is_dir($publicPath)) {
    echo "\n⚠️  مجلد exports غير موجود\n";
    echo "سأحاول البحث عن الملفات...\n\n";

    // البحث في جميع المجلدات
    $searchPaths = [
        __DIR__ . '/storage/app',
        __DIR__ . '/public',
        __DIR__,
    ];

    foreach ($searchPaths as $path) {
        $files = glob($path . '/tryoto_cities_full_*.json');
        if (!empty($files)) {
            $latestFile = end($files);
            echo "✅ تم العثور على: {$latestFile}\n";

            $data = json_decode(file_get_contents($latestFile), true);

            // احفظ نسخة في المجلد الجذر
            $timestamp = date('Y-m-d_His');

            // JSON
            $jsonOutput = __DIR__ . "/TRYOTO_CITIES_FULL_{$timestamp}.json";
            file_put_contents($jsonOutput, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "📄 JSON: " . basename($jsonOutput) . "\n";

            // CSV  مدن فقط
            $csvOutput = __DIR__ . "/TRYOTO_SUPPORTED_CITIES_{$timestamp}.csv";
            $csv = "City Name,City Name AR,Region,Companies Count\n";
            foreach ($data['supported_cities'] as $city) {
                $csv .= sprintf('"%s","%s","%s",%d' . "\n",
                    $city['city_name'],
                    $city['city_name_ar'] ?? '',
                    $city['region'] ?? '',
                    $city['delivery_companies_count']
                );
            }
            file_put_contents($csvOutput, $csv);
            echo "📄 CSV: " . basename($csvOutput) . "\n";

            // CSV مفصل
            $detailOutput = __DIR__ . "/TRYOTO_CITIES_DETAILED_{$timestamp}.csv";
            $detail = "City,City AR,Region,Company Name,Service,Price\n";
            foreach ($data['supported_cities'] as $city) {
                foreach ($city['companies'] as $company) {
                    $detail .= sprintf('"%s","%s","%s","%s","%s",%.2f' . "\n",
                        $city['city_name'],
                        $city['city_name_ar'] ?? '',
                        $city['region'] ?? '',
                        $company['company_name'] ?? '',
                        $company['service_name'] ?? '',
                        $company['price'] ?? 0
                    );
                }
            }
            file_put_contents($detailOutput, $detail);
            echo "📄 CSV مفصل: " . basename($detailOutput) . "\n";

            echo "\n";
            echo "====================================================================\n";
            echo "✅ تم حفظ الملفات في المجلد الجذر للمشروع!\n";
            echo "====================================================================\n";
            echo "\n";
            echo "📊 الملخص:\n";
            echo "   - إجمالي المدن المختبرة: " . $data['total_tested'] . "\n";
            echo "   - المدن المدعومة: " . $data['total_supported'] . "\n";
            echo "   - المدن غير المدعومة: " . $data['total_unsupported'] . "\n";
            echo "\n";

            exit(0);
        }
    }

    echo "❌ لم يتم العثور على ملفات JSON\n";
    exit(1);
}
