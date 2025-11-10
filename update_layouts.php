<?php

/**
 * Script لتوحيد قوالب Blade في المشروع
 *
 * يقوم هذا السكريبت بتحديث جميع ملفات Blade لاستخدام القالب الموحد layouts.unified
 */

echo "🚀 بدء عملية توحيد القوالب...\n\n";

$viewsPath = __DIR__ . '/resources/views';
$logFile = __DIR__ . '/layout_update_log.txt';
$backupDir = __DIR__ . '/resources/views_backup_' . date('Y-m-d_H-i-s');

// إنشاء نسخة احتياطية
echo "📦 إنشاء نسخة احتياطية في: $backupDir\n";
recursiveCopy($viewsPath, $backupDir);
echo "✅ تم إنشاء النسخة الاحتياطية\n\n";

// فتح ملف السجل
$log = fopen($logFile, 'w');
fwrite($log, "سجل تحديث القوالب - " . date('Y-m-d H:i:s') . "\n");
fwrite($log, str_repeat("=", 80) . "\n\n");

$stats = [
    'total' => 0,
    'admin' => 0,
    'vendor' => 0,
    'front' => 0,
    'errors' => 0
];

// البحث عن جميع ملفات Blade
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

echo "🔍 فحص ملفات Blade...\n";

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getFilename(), '.blade.php') !== false) {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $updated = false;
        $layoutType = null;

        // تحديد نوع القالب الحالي
        if (preg_match('/@extends\([\'"]layouts\.admin[\'"]\)/', $content)) {
            $layoutType = 'admin';
            $stats['admin']++;
        } elseif (preg_match('/@extends\([\'"]layouts\.vendor[\'"]\)/', $content)) {
            $layoutType = 'vendor';
            $stats['vendor']++;
        } elseif (preg_match('/@extends\([\'"]layouts\.front[\'"]\)/', $content)) {
            $layoutType = 'front';
            $stats['front']++;
        }

        if ($layoutType) {
            $stats['total']++;

            // استبدال القالب
            $newContent = $content;

            // استبدال @extends
            $newContent = preg_replace(
                '/@extends\([\'"]layouts\.(admin|vendor|front)[\'"]\)/',
                "@extends('layouts.unified')",
                $newContent
            );

            // إضافة المتغيرات المطلوبة في بداية الملف بعد @extends
            $variablesSection = '';

            if ($layoutType === 'admin') {
                $variablesSection = "\n@php\n    \$isDashboard = true;\n    \$isAdmin = true;\n    \$hideFooter = true;\n@endphp";
            } elseif ($layoutType === 'vendor') {
                $variablesSection = "\n@php\n    \$isDashboard = true;\n    \$isVendor = true;\n@endphp";
            }

            // إضافة المتغيرات بعد @extends
            if ($variablesSection) {
                $newContent = preg_replace(
                    '/(@extends\([\'"]layouts\.unified[\'"]\))/',
                    "$1$variablesSection",
                    $newContent
                );
            }

            if ($newContent !== $originalContent) {
                file_put_contents($filePath, $newContent);
                $updated = true;

                $relativePath = str_replace($viewsPath . DIRECTORY_SEPARATOR, '', $filePath);
                $message = "✅ تم تحديث: $relativePath (النوع: $layoutType)\n";
                echo $message;
                fwrite($log, $message);
            }
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 إحصائيات التحديث:\n";
echo str_repeat("=", 80) . "\n";
echo "إجمالي الصفحات المحدثة: {$stats['total']}\n";
echo "  - صفحات admin: {$stats['admin']}\n";
echo "  - صفحات vendor: {$stats['vendor']}\n";
echo "  - صفحات front: {$stats['front']}\n";
echo "الأخطاء: {$stats['errors']}\n";
echo "\n✅ تم حفظ السجل في: $logFile\n";
echo "📦 النسخة الاحتياطية في: $backupDir\n";

// كتابة الإحصائيات في ملف السجل
fwrite($log, "\n" . str_repeat("=", 80) . "\n");
fwrite($log, "الإحصائيات:\n");
fwrite($log, "إجمالي: {$stats['total']}\n");
fwrite($log, "Admin: {$stats['admin']}\n");
fwrite($log, "Vendor: {$stats['vendor']}\n");
fwrite($log, "Front: {$stats['front']}\n");
fwrite($log, "أخطاء: {$stats['errors']}\n");

fclose($log);

echo "\n🎉 تمت عملية التحديث بنجاح!\n";

/**
 * نسخ مجلد بشكل تكراري
 */
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }

    closedir($dir);
}
