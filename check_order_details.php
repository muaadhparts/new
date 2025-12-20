<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ShipmentStatusLog;

$orderNumber = 'H96k1760089836';

echo "🔍 Checking Order: {$orderNumber}\n";
echo "=================================\n\n";

$order = Order::where('order_number', $orderNumber)->first();

if (!$order) {
    echo "❌ Order not found!\n";
    exit(1);
}

echo "📦 Order Details:\n";
echo "   Order Number: {$order->order_number}\n";
echo "   Status: {$order->status}\n";
echo "   Customer: {$order->customer_name}\n";
echo "   City: {$order->customer_city}\n";
echo "   Address: {$order->customer_address}\n\n";

echo "🚚 Shipping Details:\n";
echo "   Shipping Method: {$order->shipping}\n";
echo "   Shipping Title: " . ($order->shipping_title ?? 'N/A') . "\n";
echo "   Pickup Location: " . ($order->pickup_location ?? 'N/A') . "\n";
echo "   Is Shipping: " . ($order->is_shipping ?? 'N/A') . "\n\n";

echo "💰 Payment:\n";
echo "   Method: {$order->method}\n";
echo "   Amount: {$order->pay_amount} SAR\n\n";

echo "📊 Vendor Shipping Data:\n";
if ($order->vendor_shipping_id) {
    $shippingData = is_string($order->vendor_shipping_id)
        ? json_decode($order->vendor_shipping_id, true)
        : $order->vendor_shipping_id;

    echo json_encode($shippingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Check if Tryoto shipping exists
    if (isset($shippingData['oto']) && is_array($shippingData['oto'])) {
        echo "✅ Tryoto Shipping Found:\n";
        foreach ($shippingData['oto'] as $info) {
            echo "   - Company: " . ($info['company'] ?? 'N/A') . "\n";
            echo "   - Tracking: " . ($info['trackingNumber'] ?? 'N/A') . "\n";
            echo "   - Shipment ID: " . ($info['shipmentId'] ?? 'N/A') . "\n";
            echo "   - Price: " . ($info['price'] ?? 'N/A') . " SAR\n\n";
        }
    } else {
        echo "⚠️  No Tryoto shipping data found\n\n";
    }
} else {
    echo "   NULL (No shipping data)\n\n";
}

echo "📋 Shipment Status Logs:\n";
$logs = ShipmentStatusLog::where('order_id', $order->id)
    ->orderBy('status_date', 'asc')
    ->get();

if ($logs->isEmpty()) {
    echo "   ❌ No shipment logs found\n";
    echo "   This means the order was NOT sent to a shipping company yet.\n\n";
} else {
    echo "   ✅ Found {$logs->count()} log(s):\n";
    foreach ($logs as $log) {
        echo "   - [{$log->status_date}] {$log->status} ({$log->status_ar})\n";
        echo "     Company: {$log->company_name}\n";
        echo "     Tracking: {$log->tracking_number}\n";
        echo "     Location: {$log->location}\n";
        echo "     Message: {$log->message_ar}\n\n";
    }
}

echo "=================================\n";
echo "📝 Analysis:\n\n";

if ($order->shipping === 'pickup') {
    echo "⚠️  ISSUE FOUND:\n";
    echo "   The shipping method is 'pickup' which means:\n";
    echo "   - Customer chose to pick up the order themselves\n";
    echo "   - No shipping company will be used\n";
    echo "   - Order will NOT be sent to Tryoto or any delivery service\n\n";

    echo "   Pickup Location: " . ($order->pickup_location ?? 'Not set') . "\n";
    echo "   (This is where customer should go to pick up the order)\n\n";

    echo "💡 Solution:\n";
    echo "   - If you want shipping, customer must choose a shipping method (not pickup)\n";
    echo "   - Or manually create a shipment for this order\n\n";
} else {
    if (empty($order->vendor_shipping_id)) {
        echo "⚠️  ISSUE: vendor_shipping_id is empty\n";
        echo "   This means no shipment was created for this order.\n\n";

        echo "💡 Possible Reasons:\n";
        echo "   1. Payment was not confirmed yet (status: {$order->status})\n";
        echo "   2. Tryoto API failed to create shipment\n";
        echo "   3. Error in MyFatoorahController->createOtoShipments()\n\n";

        echo "   Check logs: storage/logs/laravel.log\n";
    } else {
        echo "✅ Shipping data exists\n";
        if ($logs->isEmpty()) {
            echo "⚠️  But no tracking logs found\n";
            echo "   Initial log might not have been created\n";
        } else {
            echo "✅ Tracking is active\n";
        }
    }
}
