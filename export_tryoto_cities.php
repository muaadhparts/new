<?php

/**
 * ====================================================================
 * TRYOTO CITIES EXPORT SCRIPT
 * ====================================================================
 * تشغيل مباشر: php export_tryoto_cities.php
 * ====================================================================
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

echo "\n";
echo "====================================================================\n";
echo "        📥 EXPORT TRYOTO CITIES - تصدير المدن من Tryoto          \n";
echo "====================================================================\n";
echo "\n";

// Run the artisan command
echo "⏳ جاري تشغيل الأمر...\n\n";

Artisan::call('tryoto:fetch-cities', ['--full' => true]);

$output = Artisan::output();
echo $output;

// Copy files to public directory
echo "\n📁 جاري نسخ الملفات إلى public/exports...\n";

$publicPath = public_path('exports');
if (!file_exists($publicPath)) {
    mkdir($publicPath, 0755, true);
    echo "   ✅ تم إنشاء مجلد exports\n";
}

// Find the latest files
$files = Storage::files();
$tryotoFiles = array_filter($files, function($file) {
    return str_starts_with(basename($file), 'tryoto_cities');
});

// Sort by time (most recent first)
usort($tryotoFiles, function($a, $b) {
    return Storage::lastModified($b) <=> Storage::lastModified($a);
});

$copied = 0;
foreach ($tryotoFiles as $file) {
    if ($copied >= 4) break; // Copy only the 4 most recent files

    $filename = basename($file);
    $destination = $publicPath . '/' . $filename;

    if (copy(storage_path('app/' . $file), $destination)) {
        echo "   ✅ {$filename}\n";
        $copied++;
    }
}

if ($copied > 0) {
    echo "\n✅ تم نسخ {$copied} ملف إلى public/exports\n";
    echo "يمكنك الوصول إليها عبر:\n";
    echo "   http://localhost/exports/tryoto_cities_full_*.json\n";
    echo "   http://localhost/exports/tryoto_supported_cities_*.csv\n";
    echo "   http://localhost/exports/tryoto_cities_detailed_*.csv\n";
    echo "   http://localhost/exports/tryoto_cities_insert_*.sql\n";
} else {
    echo "⚠️  لم يتم العثور على ملفات للنسخ\n";
}

echo "\n====================================================================\n";
echo "✅ انتهى التصدير!\n";
echo "====================================================================\n\n";
