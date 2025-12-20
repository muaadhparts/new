<?php

/**
 * Quick test script for Tryoto city verification
 *
 * Usage: php test_tryoto_verification.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TryotoLocationService;
use App\Models\City;

echo "=== Testing Tryoto City Verification ===\n\n";

try {
    $service = new TryotoLocationService();

    // Test 1: Verify a known Saudi city
    echo "Test 1: Verifying 'Riyadh' with Tryoto API...\n";
    $result = $service->verifyCitySupport('Riyadh');

    if ($result['supported']) {
        echo "✓ SUCCESS: Riyadh is supported\n";
        echo "  Companies found: " . $result['company_count'] . "\n";
        echo "  Region: " . ($result['region'] ?? 'N/A') . "\n";
    } else {
        echo "✗ FAILED: Riyadh not supported\n";
        echo "  Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }

    echo "\n";

    // Test 2: Check if we can get a city from DB
    echo "Test 2: Getting a city from database...\n";
    $city = City::with(['state', 'country'])->first();

    if ($city) {
        echo "✓ SUCCESS: Found city in DB\n";
        echo "  City: {$city->city_name}\n";
        echo "  State: {$city->state->state}\n";
        echo "  Country: {$city->country->country_name}\n";

        echo "\nTest 3: Verifying this city with Tryoto...\n";
        $cityResult = $service->verifyCitySupport($city->city_name);

        if ($cityResult['supported']) {
            echo "✓ SUCCESS: {$city->city_name} is supported by Tryoto\n";
            echo "  Companies: " . $cityResult['company_count'] . "\n";
        } else {
            echo "✗ NOTICE: {$city->city_name} not supported by Tryoto\n";
            echo "  This is normal if the city is outside Tryoto coverage area\n";
        }
    } else {
        echo "✗ No cities in database\n";
    }

    echo "\n=== Tests Complete ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
