<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

echo "\n";
echo "====================================================================\n";
echo "        🔍 TRYOTO API EXPLORER - استكشاف endpoints المتاحة      \n";
echo "====================================================================\n";
echo "\n";

// Get token
$token = Cache::get('tryoto-token');
$baseUrl = 'https://api.tryoto.com';

if (!$token) {
    echo "❌ No token found. Run: php artisan tryoto:fetch-cities first\n";
    exit(1);
}

echo "✅ Token: " . substr($token, 0, 20) . "...\n";
echo "🌐 Base URL: {$baseUrl}\n";
echo "\n";

// Test endpoints
$endpoints = [
    ['GET', '/rest/v2/countries'],
    ['GET', '/rest/v2/getCountries'],
    ['GET', '/rest/v2/regions'],
    ['GET', '/rest/v2/states'],
    ['GET', '/rest/v2/cities'],
    ['GET', '/rest/v2/getCities'],
    ['GET', '/rest/v2/locations'],
    ['GET', '/rest/v2/supportedCities'],
    ['GET', '/rest/v2/supportedCountries'],
    ['POST', '/rest/v2/getCities', []],
    ['POST', '/rest/v2/getCountries', []],
];

echo "Testing " . count($endpoints) . " endpoints...\n\n";

$successful = [];

foreach ($endpoints as $endpoint) {
    $method = $endpoint[0];
    $path = $endpoint[1];
    $body = $endpoint[2] ?? null;

    echo "[{$method}] {$path}... ";

    try {
        if ($method === 'GET') {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get($baseUrl . $path);
        } else {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($baseUrl . $path, $body ?? []);
        }

        if ($response->successful()) {
            $data = $response->json();

            if (is_array($data) && !empty($data)) {
                echo "✅ SUCCESS\n";
                echo "   Keys: " . implode(', ', array_keys($data)) . "\n";
                echo "   Sample: " . json_encode(array_slice($data, 0, 1), JSON_UNESCAPED_UNICODE) . "\n";

                $successful[$path] = $data;
            } else {
                echo "⚠️  Empty response\n";
            }
        } else {
            echo "❌ Status: " . $response->status() . "\n";
        }

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "====================================================================\n";
echo "📊 Summary:\n";
echo "   Successful endpoints: " . count($successful) . "\n";

if (!empty($successful)) {
    echo "\n✅ Available data sources:\n";
    foreach (array_keys($successful) as $path) {
        echo "   - {$path}\n";
    }
}

echo "====================================================================\n\n";
