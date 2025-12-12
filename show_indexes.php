<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// الجداول الثابتة المهمة
$staticTables = [
    'products',
    'merchant_products',
    'categories',
    'newcategories',
    'sections',
    'category_periods',
    'parts_index',
    'sku_alternatives',
    'specification_items',
];

// الجداول الديناميكية (مثال: y61gl)
$catalogCode = 'y61gl';
$dynamicTables = [
    "parts_{$catalogCode}",
    "section_parts_{$catalogCode}",
    "part_spec_groups_{$catalogCode}",
    "part_spec_group_items_{$catalogCode}",
    "part_periods_{$catalogCode}",
];

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║           فهارس الجداول الموجودة في قاعدة البيانات              ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "                    الجداول الثابتة (Static Tables)                \n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

foreach ($staticTables as $table) {
    showTableIndexes($table);
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "              الجداول الديناميكية (Dynamic Tables - {$catalogCode})        \n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

foreach ($dynamicTables as $table) {
    showTableIndexes($table);
}

function showTableIndexes($table) {
    if (!Schema::hasTable($table)) {
        echo "❌ Table: {$table} - NOT EXISTS\n\n";
        return;
    }

    $count = DB::table($table)->count();
    echo "📊 Table: {$table} (" . number_format($count) . " rows)\n";
    echo str_repeat("-", 60) . "\n";

    $indexes = DB::select("SHOW INDEX FROM `{$table}`");

    // Group by index name
    $grouped = [];
    foreach ($indexes as $idx) {
        $name = $idx->Key_name;
        if (!isset($grouped[$name])) {
            $grouped[$name] = [
                'columns' => [],
                'unique' => !$idx->Non_unique,
                'type' => $idx->Index_type,
            ];
        }
        $grouped[$name]['columns'][$idx->Seq_in_index] = $idx->Column_name;
    }

    if (empty($grouped)) {
        echo "   No indexes found!\n";
    } else {
        foreach ($grouped as $name => $info) {
            ksort($info['columns']);
            $cols = implode(', ', $info['columns']);
            $unique = $info['unique'] ? '🔑 UNIQUE' : '📇 INDEX';
            $type = $info['type'];

            if ($name === 'PRIMARY') {
                echo "   🔐 PRIMARY KEY: ({$cols})\n";
            } else {
                echo "   {$unique}: {$name}\n";
                echo "      Columns: ({$cols})\n";
                echo "      Type: {$type}\n";
            }
        }
    }
    echo "\n";
}
