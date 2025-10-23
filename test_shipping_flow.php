<?php

/**
 * Script اختبار شامل لمسار الشحن
 *
 * الاستخدام:
 * php test_shipping_flow.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Shipping;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   اختبار شامل لمسار الشحن (Shipping Flow Test)              \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. فحص آخر 5 طلبات
echo "📊 فحص آخر 5 طلبات في النظام:\n";
echo "─────────────────────────────────────────────────────────────\n";

$recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get();

if ($recentOrders->isEmpty()) {
    echo "❌ لا توجد طلبات في النظام\n";
    echo "\n💡 يرجى:\n";
    echo "   1. إنشاء طلب جديد من الموقع\n";
    echo "   2. اختيار 'Ship To Address'\n";
    echo "   3. اختيار طريقة شحن\n";
    echo "   4. إكمال الدفع\n";
    echo "   5. تشغيل هذا السكريبت مرة أخرى\n\n";
    exit;
}

foreach ($recentOrders as $order) {
    echo "\n┌─ طلب #{$order->order_number} ─────────────────────\n";
    echo "│ التاريخ: " . $order->created_at->format('Y-m-d H:i:s') . "\n";
    echo "│ العميل: {$order->customer_name}\n";
    echo "│ الطريقة: {$order->method}\n";
    echo "│ الحالة: {$order->payment_status}\n";
    echo "│\n";
    echo "│ 🚚 بيانات الشحن:\n";
    echo "│   ├─ shipping: {$order->shipping}\n";
    echo "│   ├─ is_shipping: " . ($order->is_shipping ? '✅ Yes' : '❌ No') . "\n";

    // تحليل shipping_title
    if ($order->shipping_title) {
        $shippingTitle = is_string($order->shipping_title)
            ? json_decode($order->shipping_title, true)
            : $order->shipping_title;

        if (is_array($shippingTitle)) {
            echo "│   ├─ shipping_title: [JSON Array]\n";
            foreach ($shippingTitle as $vendorId => $shippingId) {
                $shipping = Shipping::find($shippingId);
                $title = $shipping ? $shipping->title : "Unknown ($shippingId)";
                echo "│   │   └─ Vendor {$vendorId}: {$title}\n";
            }
        } else {
            echo "│   ├─ shipping_title: {$shippingTitle}\n";
        }
    } else {
        echo "│   ├─ shipping_title: ⚠️  NULL\n";
    }

    echo "│   ├─ shipping_cost: " . number_format($order->shipping_cost, 2) . " {$order->currency_sign}\n";
    echo "│   ├─ packing_cost: " . number_format($order->packing_cost, 2) . " {$order->currency_sign}\n";
    echo "│   └─ total: " . number_format($order->pay_amount, 2) . " {$order->currency_sign}\n";
    echo "└─────────────────────────────────────────────────────\n";
}

// 2. فحص إحصائيات الشحن
echo "\n\n📈 إحصائيات الشحن:\n";
echo "─────────────────────────────────────────────────────────────\n";

$stats = [
    'total_orders' => Order::count(),
    'with_shipping_title' => Order::whereNotNull('shipping_title')->count(),
    'with_shipping_cost' => Order::where('shipping_cost', '>', 0)->count(),
    'ship_to_address' => Order::where('shipping', 'shipto')->count(),
    'pick_up' => Order::where('shipping', 'pickup')->count(),
];

echo "├─ إجمالي الطلبات: {$stats['total_orders']}\n";
echo "├─ طلبات بها shipping_title: {$stats['with_shipping_title']} (" .
     round(($stats['with_shipping_title'] / max($stats['total_orders'], 1)) * 100, 1) . "%)\n";
echo "├─ طلبات بها shipping_cost: {$stats['with_shipping_cost']} (" .
     round(($stats['with_shipping_cost'] / max($stats['total_orders'], 1)) * 100, 1) . "%)\n";
echo "├─ Ship To Address: {$stats['ship_to_address']}\n";
echo "└─ Pick Up: {$stats['pick_up']}\n";

// 3. فحص طرق الشحن المتاحة
echo "\n\n🚚 طرق الشحن المتاحة في النظام:\n";
echo "─────────────────────────────────────────────────────────────\n";

$shippings = Shipping::all();

if ($shippings->isEmpty()) {
    echo "⚠️  لا توجد طرق شحن مفعّلة في النظام!\n";
} else {
    foreach ($shippings as $shipping) {
        echo "├─ ID: {$shipping->id} | {$shipping->title} | " .
             number_format($shipping->price, 2) . " " .
             (DB::table('currencies')->where('is_default', 1)->first()->sign ?? '') . "\n";
    }
}

// 4. فحص آخر طلب بالتفصيل
echo "\n\n🔍 فحص تفصيلي لآخر طلب:\n";
echo "─────────────────────────────────────────────────────────────\n";

$lastOrder = Order::orderBy('created_at', 'desc')->first();

if ($lastOrder) {
    echo "✅ الطلب: #{$lastOrder->order_number}\n\n";

    // فحص البيانات المحفوظة
    echo "📦 البيانات المحفوظة في قاعدة البيانات:\n";
    echo "  ├─ is_shipping: " . var_export($lastOrder->is_shipping, true) . "\n";
    echo "  ├─ shipping_title: " . var_export($lastOrder->shipping_title, true) . "\n";
    echo "  ├─ shipping_cost: " . var_export($lastOrder->shipping_cost, true) . "\n";
    echo "  ├─ shipping: " . var_export($lastOrder->shipping, true) . "\n";
    echo "  ├─ shipping_name: " . var_export($lastOrder->shipping_name, true) . "\n";
    echo "  ├─ shipping_address: " . var_export($lastOrder->shipping_address, true) . "\n";
    echo "  └─ vendor_shipping_id: " . var_export($lastOrder->vendor_shipping_id, true) . "\n";

    // التحقق من الصحة
    echo "\n✔️  التحقق من الصحة:\n";

    $checks = [];

    if ($lastOrder->shipping == 'shipto' && $lastOrder->dp == 0) {
        // يجب أن يكون هناك shipping_title أو shipping_cost
        $checks[] = [
            'test' => 'هل يوجد shipping_title أو shipping_cost؟',
            'pass' => !empty($lastOrder->shipping_title) || $lastOrder->shipping_cost > 0,
            'value' => "title: " . (!empty($lastOrder->shipping_title) ? '✅' : '❌') .
                      " | cost: " . ($lastOrder->shipping_cost > 0 ? '✅' : '❌')
        ];
    }

    if ($lastOrder->shipping == 'pickup') {
        $checks[] = [
            'test' => 'هل pickup_location محفوظ؟',
            'pass' => !empty($lastOrder->pickup_location),
            'value' => $lastOrder->pickup_location ?? 'NULL'
        ];
    }

    $checks[] = [
        'test' => 'هل is_shipping له قيمة صحيحة؟',
        'pass' => in_array($lastOrder->is_shipping, [0, 1]),
        'value' => $lastOrder->is_shipping
    ];

    foreach ($checks as $check) {
        $icon = $check['pass'] ? '✅' : '❌';
        echo "  {$icon} {$check['test']}\n";
        echo "     └─ {$check['value']}\n";
    }
}

// 5. التوصيات
echo "\n\n💡 التوصيات:\n";
echo "─────────────────────────────────────────────────────────────\n";

$recommendations = [];

if ($stats['with_shipping_title'] < $stats['total_orders'] * 0.5) {
    $recommendations[] = "⚠️  أقل من 50% من الطلبات بها shipping_title - تحقق من Step2";
}

if ($stats['with_shipping_cost'] < $stats['ship_to_address']) {
    $recommendations[] = "⚠️  بعض طلبات Ship To Address بدون shipping_cost";
}

if (empty($recommendations)) {
    echo "✅ النظام يعمل بشكل صحيح!\n";
} else {
    foreach ($recommendations as $rec) {
        echo "$rec\n";
    }
}

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الاختبار                                            \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "📝 للاختبار اليدوي:\n";
echo "   1. قم بزيارة: " . env('APP_URL', 'http://localhost') . "\n";
echo "   2. أضف منتجات للسلة\n";
echo "   3. انتقل للـ Checkout\n";
echo "   4. اختر 'Ship To Address'\n";
echo "   5. اختر طريقة شحن\n";
echo "   6. أكمل الدفع (يمكنك استخدام Cash On Delivery)\n";
echo "   7. تحقق من صفحة Success\n";
echo "   8. تحقق من الفاتورة في لوحة التحكم\n";
echo "   9. تحقق من الإيميل المرسل\n\n";
