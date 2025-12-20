<?php

/**
 * Quick seeder for Saudi locations
 *
 * Usage: php seed_saudi_locations.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;

echo "=== Seeding Saudi Arabia Locations ===\n\n";

try {
    DB::beginTransaction();

    // Create or get Saudi Arabia
    $saudi = Country::firstOrCreate(
        ['country_code' => 'SA'],
        [
            'country_name' => 'Saudi Arabia',
            'country_name_ar' => 'المملكة العربية السعودية',
            'tax' => 15,
            'status' => 1
        ]
    );

    echo "✓ Country: {$saudi->country_name} (ID: {$saudi->id})\n\n";

    // Create main regions
    $regions = [
        ['name' => 'Riyadh Region', 'name_ar' => 'منطقة الرياض'],
        ['name' => 'Makkah Region', 'name_ar' => 'منطقة مكة المكرمة'],
        ['name' => 'Eastern Region', 'name_ar' => 'المنطقة الشرقية'],
        ['name' => 'Madinah Region', 'name_ar' => 'منطقة المدينة المنورة'],
    ];

    foreach ($regions as $region) {
        $state = State::firstOrCreate(
            [
                'country_id' => $saudi->id,
                'state' => $region['name']
            ],
            [
                'state_ar' => $region['name_ar'],
                'tax' => 0,
                'status' => 1,
                'owner_id' => 0
            ]
        );

        echo "✓ Region: {$state->state} (ID: {$state->id})\n";
    }

    // Create major cities
    $cities = [
        ['Riyadh Region', 'Riyadh', 'الرياض'],
        ['Riyadh Region', 'Al Kharj', 'الخرج'],
        ['Makkah Region', 'Jeddah', 'جدة'],
        ['Makkah Region', 'Mecca', 'مكة المكرمة'],
        ['Makkah Region', 'Taif', 'الطائف'],
        ['Eastern Region', 'Dammam', 'الدمام'],
        ['Eastern Region', 'Al Khobar', 'الخبر'],
        ['Eastern Region', 'Dhahran', 'الظهران'],
        ['Madinah Region', 'Medina', 'المدينة المنورة'],
        ['Madinah Region', 'Yanbu', 'ينبع'],
    ];

    echo "\n";

    foreach ($cities as $cityData) {
        $stateName = $cityData[0];
        $cityName = $cityData[1];
        $cityNameAr = $cityData[2];

        $state = State::where('country_id', $saudi->id)
            ->where('state', $stateName)
            ->first();

        if ($state) {
            $city = City::firstOrCreate(
                [
                    'country_id' => $saudi->id,
                    'state_id' => $state->id,
                    'city_name' => $cityName
                ],
                [
                    'city_name_ar' => $cityNameAr,
                    'status' => 1
                ]
            );

            echo "✓ City: {$city->city_name} → {$state->state}\n";
        }
    }

    DB::commit();

    echo "\n=== Seeding Complete ===\n";
    echo "Total Countries: " . Country::count() . "\n";
    echo "Total States: " . State::count() . "\n";
    echo "Total Cities: " . City::count() . "\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
