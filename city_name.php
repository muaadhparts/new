<?php

use Illuminate\Support\Facades\DB;

$cities = DB::table('cities')->pluck('city_name');

$data = [];
foreach ($cities as $city) {
    $data[$city] = $city;
}

file_put_contents(
    base_path('resources/lang/en/cities.php'),
    "<?php\nreturn " . var_export($data, true) . ";"
);

echo "✅ تم إنشاء الملف: resources/lang/en/cities.php\n";
