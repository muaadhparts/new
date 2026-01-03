#!/usr/bin/env php
<?php
/**
 * Payment Flow Verification Script
 * Tests vendor isolation and amount consistency throughout checkout
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PAYMENT FLOW VERIFICATION ===\n\n";

// 1. Check Recent Orders
echo "1️⃣  CHECKING RECENT ORDERS\n";
echo str_repeat("-", 80) . "\n";

$orders = App\Models\Order::orderBy('id', 'desc')->take(10)->get();

if ($orders->isEmpty()) {
    echo "❌ No orders found\n";
} else {
    foreach ($orders as $order) {
        $vendorIds = json_decode($order->vendor_ids, true);
        $vendorCount = is_array($vendorIds) ? count($vendorIds) : 0;

        $status = $vendorCount === 1 ? "✅" : "⚠️ ";

        echo sprintf(
            "%s Order #%d | %s | Amount: %.2f %s | Vendors: %s | Method: %s\n",
            $status,
            $order->id,
            $order->order_number,
            $order->pay_amount ?? 0,
            $order->currency_sign ?? '',
            $order->vendor_ids ?? 'NULL',
            $order->method ?? 'N/A'
        );

        // Decode and verify cart matches vendor_ids
        if ($order->cart) {
            $cart = json_decode($order->cart, true);
            if (isset($cart['items'])) {
                $cartVendors = [];
                foreach ($cart['items'] as $item) {
                    $vid = $item['item']['user_id'] ?? null;
                    if ($vid && !in_array($vid, $cartVendors)) {
                        $cartVendors[] = $vid;
                    }
                }

                $match = ($cartVendors === $vendorIds) ? "✅" : "❌";
                echo "   └─ Cart vendors: " . implode(', ', $cartVendors) . " $match\n";
            }
        }
        echo "\n";
    }
}

// 2. Check Payment Controllers
echo "\n2️⃣  PAYMENT CONTROLLER VERIFICATION\n";
echo str_repeat("-", 80) . "\n";

$controllersPath = __DIR__ . '/app/Http/Controllers/Payment/Checkout';
$controllers = [
    'CashOnDeliveryController.php',
    'PaypalController.php',
    'StripeController.php',
    'PaystackController.php',
    'ManualPaymentController.php',
    'WalletPaymentController.php',
    'SslController.php',
    'VoguepayController.php',
    'MercadopagoController.php',
    'MyFatoorahController.php',
    'AuthorizeController.php',
    'FlutterwaveController.php',
    'RazorpayController.php',
    'PaytmController.php',
    'InstamojoController.php',
];

foreach ($controllers as $controller) {
    $filepath = $controllersPath . '/' . $controller;

    if (!file_exists($filepath)) {
        echo "⚠️  $controller - NOT FOUND\n";
        continue;
    }

    $content = file_get_contents($filepath);

    // Check for old pattern (PriceHelper::getOrderTotal)
    $hasOldPattern = preg_match('/PriceHelper::getOrderTotal/', $content);

    // Check for new pattern (prepareOrderData)
    $hasNewPattern = preg_match('/prepareOrderData/', $content);

    if ($hasOldPattern) {
        echo "❌ $controller - STILL USING OLD METHOD (PriceHelper::getOrderTotal)\n";
    } elseif ($hasNewPattern) {
        echo "✅ $controller - Using prepareOrderData()\n";
    } else {
        echo "⚠️  $controller - Unknown pattern\n";
    }
}

// 3. Check for Multi-Vendor Orders (should be ZERO after fix)
echo "\n3️⃣  MULTI-VENDOR ORDER CHECK\n";
echo str_repeat("-", 80) . "\n";

$multiVendorOrders = App\Models\Order::whereNotNull('vendor_ids')
    ->where('vendor_ids', '!=', '')
    ->where('vendor_ids', '!=', '[]')
    ->get()
    ->filter(function($order) {
        $vendors = json_decode($order->vendor_ids, true);
        return is_array($vendors) && count($vendors) > 1;
    });

if ($multiVendorOrders->isEmpty()) {
    echo "✅ No multi-vendor orders found (All orders have single vendor)\n";
} else {
    echo "⚠️  Found " . $multiVendorOrders->count() . " multi-vendor orders:\n";
    foreach ($multiVendorOrders as $order) {
        $vendors = json_decode($order->vendor_ids, true);
        echo sprintf(
            "   Order #%d | %s | Vendors: %s | Date: %s\n",
            $order->id,
            $order->order_number,
            implode(', ', $vendors),
            $order->created_at->format('Y-m-d H:i:s')
        );
    }
}

// 4. Summary Statistics
echo "\n4️⃣  SUMMARY STATISTICS\n";
echo str_repeat("-", 80) . "\n";

$totalOrders = App\Models\Order::count();
$ordersWithVendors = App\Models\Order::whereNotNull('vendor_ids')
    ->where('vendor_ids', '!=', '')
    ->where('vendor_ids', '!=', '[]')
    ->count();

echo "Total Orders: $totalOrders\n";
echo "Orders with vendor_ids: $ordersWithVendors\n";

$singleVendorCount = App\Models\Order::whereNotNull('vendor_ids')
    ->where('vendor_ids', '!=', '')
    ->where('vendor_ids', '!=', '[]')
    ->get()
    ->filter(function($order) {
        $vendors = json_decode($order->vendor_ids, true);
        return is_array($vendors) && count($vendors) === 1;
    })
    ->count();

echo "Single vendor orders: $singleVendorCount\n";
echo "Multi vendor orders: " . ($ordersWithVendors - $singleVendorCount) . "\n";

// 5. Test Recommendations
echo "\n5️⃣  MANUAL TEST CHECKLIST\n";
echo str_repeat("-", 80) . "\n";
echo "
[ ] Test 1: Single Vendor A Checkout
    1. Clear cart completely
    2. Add products ONLY from Vendor ID: 59
    3. Proceed through Step 1 (addresses)
    4. Proceed through Step 2 (shipping)
    5. At Step 3, note the EXACT total displayed (e.g., 68.80 SAR)
    6. Select payment method and complete
    7. Check database: vendor_ids should be [59]
    8. Check payment gateway log: amount should match Step 3 exactly

[ ] Test 2: Single Vendor B Checkout
    1. Clear cart completely
    2. Add products ONLY from Vendor ID: 64
    3. Complete full checkout
    4. Verify vendor_ids = [64]
    5. Verify amount matches Step 3

[ ] Test 3: Multi-Vendor Scenario (Should create SEPARATE orders)
    1. Add products from Vendor 59
    2. Add products from Vendor 64
    3. System should split into TWO separate checkouts
    4. Each checkout should show only that vendor's total
    5. Two separate orders should be created

[ ] Test 4: Payment Gateway Amount Verification
    1. Enable MyFatoorah test mode logs
    2. Complete checkout with specific amount (e.g., 68.80 SAR)
    3. Check MyFatoorah API request log
    4. Verify 'InvoiceValue' matches Step 3 exactly
    5. No recalculation should occur

";

echo "\n✅ VERIFICATION COMPLETE\n";
echo "Review results above and perform manual tests if needed.\n\n";
