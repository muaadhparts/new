<?php

/**
 * Test Script: Create Test Tryoto Shipment
 * يختبر إنشاء شحنة حقيقية في Tryoto
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Models\ShipmentStatusLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "🚀 Testing Tryoto Shipment Creation\n";
echo "====================================\n\n";

// Configuration
$tryotoBaseUrl = 'https://api.tryoto.com'; // Replace with actual Tryoto API URL
$tryotoApiKey = env('TRYOTO_API_KEY', 'your-api-key-here');

// Step 1: Get test order
echo "Step 1: Getting test order...\n";
$order = Order::latest()->first();

if (!$order) {
    echo "❌ No orders found. Creating a test order...\n";
    exit(1);
}

echo "✅ Order found: {$order->order_number}\n";
echo "   Customer: {$order->customer_name}\n";
echo "   City: {$order->customer_city}\n";
echo "   Address: {$order->customer_address}\n\n";

// Step 2: Get vendor info
echo "Step 2: Getting vendor info...\n";
$cart = $order->cart;
$vendorId = null;

if (is_array($cart) && isset($cart['items'])) {
    foreach ($cart['items'] as $item) {
        if (isset($item['item']['user_id'])) {
            $vendorId = $item['item']['user_id'];
            break;
        }
    }
}

if (!$vendorId) {
    echo "⚠️  No vendor found in cart. Looking for any vendor...\n";
    $vendor = User::where('is_vendor', 2)->first();
    if ($vendor) {
        $vendorId = $vendor->id;
    } else {
        echo "❌ No vendors found in database.\n";
        exit(1);
    }
} else {
    $vendor = User::find($vendorId);
}

if (!$vendor) {
    echo "❌ Vendor not found with ID: {$vendorId}\n";
    exit(1);
}

echo "✅ Vendor: {$vendor->name}\n";
echo "   Warehouse City: " . ($vendor->warehouse_city ?? $vendor->shop_city ?? 'N/A') . "\n";
echo "   Warehouse Address: " . ($vendor->warehouse_address ?? $vendor->shop_address ?? 'N/A') . "\n\n";

// Step 3: Prepare shipment data
echo "Step 3: Preparing shipment data...\n";

$originCity = $vendor->warehouse_city ?? $vendor->shop_city ?? 'Riyadh';
$originAddress = $vendor->warehouse_address ?? $vendor->shop_address ?? 'Test Warehouse';

$shipmentData = [
    'origin' => [
        'city' => $originCity,
        'address' => $originAddress,
        'name' => $vendor->name,
        'phone' => $vendor->phone ?? '0500000000',
    ],
    'destination' => [
        'city' => $order->customer_city,
        'address' => $order->customer_address,
        'name' => $order->customer_name,
        'phone' => $order->customer_phone,
    ],
    'package' => [
        'weight' => 2, // kg
        'dimensions' => [
            'length' => 30,
            'width' => 20,
            'height' => 15,
        ],
        'description' => 'Order ' . $order->order_number,
    ],
    'delivery_option_id' => 1, // Test delivery option
    'reference_number' => $order->order_number,
];

echo "📦 Shipment Data:\n";
echo json_encode($shipmentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Step 4: Simulate API call
echo "Step 4: Simulating Tryoto API call...\n";
echo "⚠️  Note: This is a simulation. Replace with actual Tryoto API endpoint.\n\n";

// Example of what the real API call would look like:
/*
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $tryotoApiKey,
    'Content-Type' => 'application/json',
])->post($tryotoBaseUrl . '/shipments', $shipmentData);

if ($response->successful()) {
    $data = $response->json();
    $trackingNumber = $data['tracking_number'] ?? null;
    $shipmentId = $data['shipment_id'] ?? null;
} else {
    echo "❌ API Error: " . $response->body() . "\n";
    exit(1);
}
*/

// Simulate success response
echo "✅ Simulating successful response...\n";
$trackingNumber = 'TRY-TEST-' . time();
$shipmentId = 'SHIP-' . uniqid();
$companyName = 'Smsa Express';

echo "   Tracking Number: {$trackingNumber}\n";
echo "   Shipment ID: {$shipmentId}\n";
echo "   Company: {$companyName}\n\n";

// Step 5: Save shipment log
echo "Step 5: Creating shipment log...\n";

$log = ShipmentStatusLog::create([
    'order_id' => $order->id,
    'vendor_id' => $vendorId,
    'tracking_number' => $trackingNumber,
    'shipment_id' => $shipmentId,
    'company_name' => $companyName,
    'status' => 'created',
    'status_ar' => 'تم إنشاء الشحنة',
    'message' => 'Shipment created successfully',
    'message_ar' => 'تم إنشاء الشحنة بنجاح. في انتظار استلام السائق من المستودع.',
    'location' => $originCity,
    'status_date' => now(),
    'raw_data' => [
        'test' => true,
        'simulation' => true,
        'created_at' => now()->toDateTimeString(),
    ],
]);

echo "✅ Shipment log created with ID: {$log->id}\n\n";

// Step 6: Update order vendor_shipping_id
echo "Step 6: Updating order shipping data...\n";

$shippingData = [
    'oto' => [
        [
            'trackingNumber' => $trackingNumber,
            'shipmentId' => $shipmentId,
            'company' => $companyName,
            'price' => 25.00,
            'vendor_id' => $vendorId,
        ]
    ]
];

$order->vendor_shipping_id = json_encode($shippingData);
$order->save();

echo "✅ Order updated with shipping data\n\n";

// Step 7: Test webhook with this shipment
echo "Step 7: Testing webhook with created shipment...\n";

$webhookUrl = env('APP_URL') . '/webhooks/tryoto';
$webhookPayload = [
    'trackingNumber' => $trackingNumber,
    'shipmentId' => $shipmentId,
    'status' => 'picked_up',
    'location' => $originCity . ' Warehouse',
    'latitude' => 24.7136,
    'longitude' => 46.6753,
    'message' => 'Package has been picked up from warehouse',
    'statusDate' => now()->addMinutes(5)->toDateTimeString(),
];

echo "📡 Sending webhook to: {$webhookUrl}\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
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
echo "   Response: {$response}\n\n";

if ($httpCode === 200) {
    echo "✅ Webhook processed successfully!\n\n";

    // Check updated log
    $updatedLog = ShipmentStatusLog::where('tracking_number', $trackingNumber)
        ->where('status', 'picked_up')
        ->first();

    if ($updatedLog) {
        echo "✅ New status log created:\n";
        echo "   Status: {$updatedLog->status_ar}\n";
        echo "   Message: {$updatedLog->message_ar}\n";
        echo "   Location: {$updatedLog->location}\n\n";
    }
} else {
    echo "❌ Webhook failed!\n\n";
}

// Step 8: Show results
echo "====================================\n";
echo "🎉 Test Complete!\n\n";

echo "📊 Summary:\n";
echo "   Order: {$order->order_number}\n";
echo "   Tracking: {$trackingNumber}\n";
echo "   Status Logs: " . ShipmentStatusLog::where('tracking_number', $trackingNumber)->count() . "\n\n";

echo "🔗 Test Links:\n";
echo "   1. Track Order: " . env('APP_URL') . "/order/track/load/{$order->order_number}\n";
echo "   2. Track by Number: " . env('APP_URL') . "/order/track/load/{$trackingNumber}\n";
echo "   3. Success Page: " . env('APP_URL') . "/success/{$order->order_number}\n\n";

echo "💡 Next Steps:\n";
echo "   1. Visit the tracking page to see the shipment timeline\n";
echo "   2. Send more webhook updates to test different statuses\n";
echo "   3. Configure real Tryoto API credentials in .env\n";
echo "   4. Test with live Tryoto shipments\n\n";
