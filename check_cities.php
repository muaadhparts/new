<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Check ===\n";

// Check country
$country = App\Models\Country::where('country_name', 'Saudi Arabia')
    ->orWhere('country_code', 'SA')
    ->first();

if ($country) {
    echo "Country found: ID={$country->id}, Name={$country->country_name}, is_synced={$country->is_synced}\n";

    // Check cities count
    $totalCities = App\Models\City::where('country_id', $country->id)->count();
    echo "Total cities for this country: {$totalCities}\n";

    // Check tryoto_supported cities
    $supportedCities = App\Models\City::where('country_id', $country->id)
        ->where('tryoto_supported', 1)
        ->count();
    echo "Cities with tryoto_supported=1: {$supportedCities}\n";

    // Sample cities
    echo "\n=== Sample Cities ===\n";
    $cities = App\Models\City::where('country_id', $country->id)->limit(5)->get();
    foreach ($cities as $city) {
        echo "- {$city->city_name} | tryoto_supported: " . ($city->tryoto_supported ?? 'NULL') . "\n";
    }

    // Search for Riyadh
    echo "\n=== Search for Riyadh ===\n";
    $riyadh = App\Models\City::where('country_id', $country->id)
        ->where(function($q) {
            $q->where('city_name', 'like', '%Riyadh%')
              ->orWhere('city_name', 'like', '%riyadh%')
              ->orWhere('city_name', 'like', '%RIYADH%');
        })
        ->get();

    foreach ($riyadh as $city) {
        echo "Found: {$city->city_name} | tryoto_supported: {$city->tryoto_supported}\n";
    }

} else {
    echo "Country NOT FOUND!\n";

    // List all countries
    echo "\n=== All Countries ===\n";
    $countries = App\Models\Country::all();
    foreach ($countries as $c) {
        echo "- {$c->country_name} (code: {$c->country_code}, is_synced: {$c->is_synced})\n";
    }
}
