#!/usr/bin/env php
<?php
/**
 * Analyze vendor_ids mismatch errors
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

echo "\n=== تحليل أخطاء vendor_ids mismatch ===\n\n";

$orderIds = [42, 41, 40, 28, 27, 26, 25, 24];

foreach ($orderIds as $orderId) {
    $order = Order::find($orderId);

    if (!$order) {
        echo "Order #$orderId: غير موجود\n\n";
        continue;
    }

    echo "Order #$orderId | {$order->order_number}\n";
    echo "Created: {$order->created_at}\n";
    echo "Method: {$order->method}\n";

    // Check vendor_ids field
    echo "vendor_ids (RAW): {$order->vendor_ids}\n";

    $vendorIds = json_decode($order->vendor_ids, true);

    if (is_array($vendorIds)) {
        echo "vendor_ids (Decoded): [" . implode(', ', $vendorIds) . "]\n";
        echo "Type of first element: " . gettype($vendorIds[0]) . "\n";
    } else {
        echo "vendor_ids: INVALID JSON\n";
    }

    // Check cart vendors
    if ($order->cart) {
        $cart = json_decode($order->cart, true);

        if (isset($cart['items'])) {
            $cartVendors = [];
            foreach ($cart['items'] as $item) {
                $vid = $item['item']['user_id'] ?? null;
                if ($vid !== null) {
                    if (!in_array($vid, $cartVendors)) {
                        $cartVendors[] = $vid;
                    }
                }
            }

            echo "Cart vendors: [" . implode(', ', $cartVendors) . "]\n";

            // Type comparison
            if (!empty($cartVendors)) {
                echo "Type of cart vendor: " . gettype($cartVendors[0]) . "\n";
            }

            // Deep comparison
            if (is_array($vendorIds)) {
                // Convert both to int for comparison
                $vendorIdsInt = array_map('intval', $vendorIds);
                $cartVendorsInt = array_map('intval', $cartVendors);

                sort($vendorIdsInt);
                sort($cartVendorsInt);

                if ($vendorIdsInt === $cartVendorsInt) {
                    echo "✅ MATCH (after int conversion)\n";
                } else {
                    echo "❌ MISMATCH even after conversion\n";
                    echo "   vendor_ids (int): [" . implode(', ', $vendorIdsInt) . "]\n";
                    echo "   cart vendors (int): [" . implode(', ', $cartVendorsInt) . "]\n";
                }
            }
        }
    } else {
        echo "Cart: NULL\n";
    }

    echo "\n" . str_repeat("-", 79) . "\n\n";
}

echo "\n=== التشخيص ===\n";
echo "السبب المحتمل: vendor_ids مخزن كـ string في JSON (مثل \"64\") بينما cart يحتوي int (64)\n";
echo "الحل: تحويل كلاهما إلى int قبل المقارنة، أو توحيد نوع البيانات عند الحفظ\n";
