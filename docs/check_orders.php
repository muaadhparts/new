<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Recent Orders Check ===\n\n";

$orders = App\Models\Order::orderBy('id', 'desc')->take(10)->get();

if ($orders->isEmpty()) {
    echo "No orders found in database.\n";
} else {
    foreach ($orders as $order) {
        echo sprintf(
            "Order #%d | %s | Vendor IDs: %s | Amount: %.2f %s | Method: %s | Date: %s\n",
            $order->id,
            $order->order_number,
            $order->vendor_ids ?? 'NULL',
            $order->pay_amount ?? 0,
            $order->currency_sign ?? '',
            $order->method ?? 'N/A',
            $order->created_at->format('Y-m-d H:i:s')
        );

        // Decode vendor_ids to check content
        if ($order->vendor_ids) {
            $vendorIds = json_decode($order->vendor_ids, true);
            if (is_array($vendorIds) && !empty($vendorIds)) {
                echo "  └─ Decoded Vendors: " . implode(', ', $vendorIds) . "\n";
            }
        }

        // Check cart content
        if ($order->cart) {
            $cart = json_decode($order->cart, true);
            if (isset($cart['items'])) {
                $vendorsInCart = [];
                foreach ($cart['items'] as $item) {
                    $vendorId = $item['item']['user_id'] ?? null;
                    if ($vendorId && !in_array($vendorId, $vendorsInCart)) {
                        $vendorsInCart[] = $vendorId;
                    }
                }
                echo "  └─ Vendors in Cart: " . implode(', ', $vendorsInCart) . "\n";
            }
        }

        echo "\n";
    }
}

echo "\n=== Vendor Statistics ===\n";
$vendorOrders = DB::table('orders')
    ->whereNotNull('vendor_ids')
    ->where('vendor_ids', '!=', '')
    ->where('vendor_ids', '!=', '[]')
    ->count();

echo "Orders with vendor_ids: $vendorOrders\n";

echo "\n=== Check Complete ===\n";
