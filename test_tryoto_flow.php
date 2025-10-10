<?php

/**
 * Test Script: Tryoto Complete Flow
 * يختبر العملية الكاملة من إنشاء طلب حتى استقبال Webhook
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Models\ShipmentStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🚀 Testing Tryoto Complete Flow\n";
echo "================================\n\n";

// Step 1: Get or Create Test Order
echo "Step 1: Checking for test orders...\n";
$order = Order::latest()->first();

if (!$order) {
    echo "❌ No orders found in database. Please create an order first.\n";
    exit(1);
}

echo "✅ Found Order: {$order->order_number}\n";
echo "   Customer: {$order->customer_name}\n";
echo "   Status: {$order->status}\n";
echo "   City: {$order->customer_city}\n\n";

// Step 2: Check vendor_shipping_id for tracking info
echo "Step 2: Checking shipping info...\n";
if ($order->vendor_shipping_id) {
    $shippingData = is_string($order->vendor_shipping_id)
        ? json_decode($order->vendor_shipping_id, true)
        : $order->vendor_shipping_id;

    if (isset($shippingData['oto']) && is_array($shippingData['oto'])) {
        echo "✅ Shipping data found:\n";
        foreach ($shippingData['oto'] as $info) {
            echo "   - Company: {$info['company']}\n";
            echo "   - Tracking: {$info['trackingNumber']}\n";
            echo "   - Shipment ID: {$info['shipmentId']}\n\n";
        }
    } else {
        echo "⚠️  No Tryoto shipping data found in order.\n";
        echo "   This order may not have been shipped via Tryoto.\n\n";
    }
} else {
    echo "⚠️  No vendor_shipping_id found.\n\n";
}

// Step 3: Check ShipmentStatusLog
echo "Step 3: Checking shipment status logs...\n";
$logs = ShipmentStatusLog::where('order_id', $order->id)
    ->orderBy('created_at', 'desc')
    ->get();

if ($logs->isEmpty()) {
    echo "⚠️  No shipment logs found for this order.\n";
    echo "   The order may not have been processed through Tryoto yet.\n\n";
} else {
    echo "✅ Found {$logs->count()} shipment log(s):\n";
    foreach ($logs as $log) {
        echo "   - Tracking: {$log->tracking_number}\n";
        echo "     Status: {$log->status} ({$log->status_ar})\n";
        echo "     Date: {$log->status_date}\n";
        echo "     Message: {$log->message_ar}\n\n";
    }
}

// Step 4: Simulate Webhook Test
echo "Step 4: Simulating Tryoto Webhook...\n";

if ($logs->isNotEmpty()) {
    $testTrackingNumber = $logs->first()->tracking_number;

    echo "📡 Sending test webhook for tracking: {$testTrackingNumber}\n";

    $webhookUrl = env('APP_URL') . '/webhooks/tryoto';
    $payload = [
        'trackingNumber' => $testTrackingNumber,
        'shipmentId' => 'TEST-SHIPMENT-' . time(),
        'status' => 'in_transit',
        'location' => 'Riyadh Distribution Center',
        'latitude' => 24.7136,
        'longitude' => 46.6753,
        'message' => 'Package is in transit',
        'statusDate' => now()->toDateTimeString(),
    ];

    echo "   Webhook URL: {$webhookUrl}\n";
    echo "   Payload: " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Test using cURL
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "   Response Code: {$httpCode}\n";
    echo "   Response Body: {$response}\n\n";

    if ($httpCode === 200) {
        echo "✅ Webhook processed successfully!\n";

        // Check if new log was created
        $newLog = ShipmentStatusLog::where('tracking_number', $testTrackingNumber)
            ->where('status', 'in_transit')
            ->latest()
            ->first();

        if ($newLog) {
            echo "✅ New shipment log created successfully!\n";
            echo "   Status: {$newLog->status_ar}\n";
            echo "   Message: {$newLog->message_ar}\n";
        } else {
            echo "⚠️  Expected new log not found in database.\n";
        }
    } else {
        echo "❌ Webhook failed with code {$httpCode}\n";
    }
} else {
    echo "⚠️  Cannot test webhook - no tracking number available.\n";
    echo "   Please complete a real order with Tryoto shipping first.\n";
}

echo "\n================================\n";
echo "🏁 Test Complete!\n\n";

// Step 5: Show Test Endpoints
echo "📋 Available Test Endpoints:\n";
echo "   1. Webhook Test: " . env('APP_URL') . "/webhooks/tryoto/test\n";
echo "   2. Track Order: " . env('APP_URL') . "/order/track/load/{order_number}\n";
echo "   3. Success Page: " . env('APP_URL') . "/success/{order_number}\n\n";

echo "💡 Next Steps:\n";
echo "   1. Complete a real order through the checkout process\n";
echo "   2. Check if Tryoto API creates a shipment\n";
echo "   3. Configure Tryoto webhook URL in their dashboard\n";
echo "   4. Test receiving real webhook updates\n\n";
