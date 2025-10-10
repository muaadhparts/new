<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

echo "🧪 Testing Tryoto API with orderId parameter\n";
echo "============================================\n\n";

// Get token
$token = Cache::get('tryoto-token');
$isSandbox = config('services.tryoto.sandbox');
$baseUrl = $isSandbox ? config('services.tryoto.test.url') : config('services.tryoto.live.url');

if (!$token) {
    $refresh = $isSandbox
        ? (config('services.tryoto.test.token') ?? env('TRYOTO_TEST_REFRESH_TOKEN'))
        : (config('services.tryoto.live.token') ?? env('TRYOTO_REFRESH_TOKEN'));

    echo "🔄 Refreshing token...\n";
    $resp = Http::post($baseUrl . '/rest/v2/refreshToken', ['refresh_token' => $refresh]);
    if ($resp->successful()) {
        $token = $resp->json()['access_token'];
        Cache::put('tryoto-token', $token, now()->addMinutes(55));
        echo "✅ Token refreshed\n\n";
    } else {
        echo "❌ Token refresh failed: " . $resp->body() . "\n";
        exit(1);
    }
}

// Test payload WITH orderId
$testPayload = [
    'orderId' => 'TEST-ORDER-' . time(), // ⭐ Added orderId
    'deliveryOptionId' => '7175',
    'originCity' => 'Riyadh',
    'destinationCity' => 'Jeddah',
    'receiverName' => 'Test Customer',
    'receiverPhone' => '0501234567',
    'receiverAddress' => 'Test Address, Jeddah',
    'weight' => 1.0,
    'xlength' => 30,
    'xheight' => 30,
    'xwidth' => 30,
    'codAmount' => 100.0,
];

echo "📤 Sending test shipment request with orderId...\n";
echo json_encode($testPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$response = Http::withToken($token)->post($baseUrl . '/rest/v2/createShipment', $testPayload);

echo "📥 Response:\n";
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n\n";

if ($response->successful()) {
    $data = $response->json();
    echo "✅ SUCCESS!\n";
    echo "Tracking Number: " . ($data['trackingNumber'] ?? 'N/A') . "\n";
    echo "Shipment ID: " . ($data['shipmentId'] ?? 'N/A') . "\n";
} else {
    echo "❌ FAILED\n";
    $error = $response->json();
    echo "Error: " . ($error['otoErrorMessage'] ?? $error['errorMsg'] ?? 'Unknown error') . "\n";
}

echo "\n============================================\n";
