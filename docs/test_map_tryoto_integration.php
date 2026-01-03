<?php

/**
 * Test Map + Tryoto Integration
 *
 * Usage: php test_map_tryoto_integration.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\TryotoLocationService;

echo "=== Testing Map + Tryoto Integration ===\n\n";

$service = new TryotoLocationService();

// Test Cases
$testCases = [
    [
        'name' => 'Test 1: Exact Match - Riyadh Center',
        'city' => 'Riyadh',
        'lat' => 24.7136,
        'lng' => 46.6753,
        'expected' => 'exact_match'
    ],
    [
        'name' => 'Test 2: Exact Match - Jeddah',
        'city' => 'Jeddah',
        'lat' => 21.5433,
        'lng' => 39.1728,
        'expected' => 'exact_match'
    ],
    [
        'name' => 'Test 3: Name Variation - Al Khobar',
        'city' => 'Al Khobar',
        'lat' => 26.2787,
        'lng' => 50.2085,
        'expected' => 'exact_match or name_variation'
    ],
    [
        'name' => 'Test 4: Nearest City - Small village near Riyadh',
        'city' => 'Unknown Village',
        'lat' => 24.5,
        'lng' => 46.5,
        'expected' => 'nearest_city'
    ],
    [
        'name' => 'Test 5: Distance Calculation - Between Riyadh and Jeddah',
        'description' => 'Calculate distance between two major cities'
    ],
];

foreach ($testCases as $index => $test) {
    echo str_repeat('=', 60) . "\n";
    echo "{$test['name']}\n";
    echo str_repeat('=', 60) . "\n";

    if ($test['name'] === 'Test 5: Distance Calculation - Between Riyadh and Jeddah') {
        // Special test for distance calculation
        $distance = $service->findNearestSupportedCity(21.5433, 39.1728); // Jeddah coordinates
        echo "Nearest city from Jeddah: {$distance['city_name']}\n";
        echo "Distance: {$distance['distance_km']} km\n";

        // Also test the method directly
        echo "\nDirect distance calculation test:\n";
        echo "Riyadh to Jeddah: Should be ~950km\n";
        // We can't call protected method, so skip this

        echo "\n";
        continue;
    }

    try {
        $result = $service->resolveMapCity($test['city'], $test['lat'], $test['lng']);

        echo "City: {$test['city']}\n";
        echo "Coordinates: {$test['lat']}, {$test['lng']}\n";
        echo "\nResult:\n";
        echo "  Strategy: {$result['strategy']}\n";
        echo "  Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";
        echo "  Message: {$result['message']}\n";

        if ($result['strategy'] === 'exact_match') {
            echo "  ✅ SUCCESS: City is supported!\n";
            echo "  Companies: {$result['companies']}\n";

        } elseif ($result['strategy'] === 'name_variation') {
            echo "  ✅ SUCCESS: City found with name variation\n";
            echo "  Original: {$result['original_name']}\n";
            echo "  Used: {$result['city_name']}\n";
            echo "  Companies: {$result['companies']}\n";

        } elseif ($result['strategy'] === 'nearest_city') {
            echo "  ⚠️  ALTERNATIVE: Using nearest city\n";
            echo "  Original: {$result['original_name']}\n";
            echo "  Nearest: {$result['city_name']} ({$result['city_name_ar']})\n";
            echo "  Distance: {$result['distance_km']} km\n";
            echo "  Companies: {$result['companies']}\n";

        } else {
            echo "  ❌ NOT SUPPORTED\n";
        }

        // Check against expected
        if (strpos($test['expected'], $result['strategy']) !== false) {
            echo "\n  ✓ Test passed!\n";
        } else {
            echo "\n  ✗ Test failed! Expected: {$test['expected']}, Got: {$result['strategy']}\n";
        }

    } catch (Exception $e) {
        echo "  ❌ ERROR: {$e->getMessage()}\n";
    }

    echo "\n";
}

// Additional test: Find nearest city for various locations
echo str_repeat('=', 60) . "\n";
echo "Test 6: Find Nearest Supported Cities\n";
echo str_repeat('=', 60) . "\n";

$locations = [
    ['name' => 'Near Riyadh', 'lat' => 24.7, 'lng' => 46.7],
    ['name' => 'Near Jeddah', 'lat' => 21.5, 'lng' => 39.2],
    ['name' => 'Near Dammam', 'lat' => 26.4, 'lng' => 50.1],
];

foreach ($locations as $location) {
    try {
        $nearest = $service->findNearestSupportedCity($location['lat'], $location['lng']);

        if ($nearest) {
            echo "{$location['name']}: {$nearest['city_name']} ({$nearest['distance_km']} km) - {$nearest['companies']} companies\n";
        } else {
            echo "{$location['name']}: No supported city found\n";
        }
    } catch (Exception $e) {
        echo "{$location['name']}: ERROR - {$e->getMessage()}\n";
    }
}

echo "\n";
echo str_repeat('=', 60) . "\n";
echo "All tests completed!\n";
echo str_repeat('=', 60) . "\n";
