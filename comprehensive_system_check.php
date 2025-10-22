#!/usr/bin/env php
<?php
/**
 * Comprehensive System Operational Check
 * فحص تشغيلي شامل للنظام بعد توحيد منطق الدفع والعزل للبائعين
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "   فحص تشغيلي شامل للنظام - Comprehensive System Check\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

$results = [];
$errors = [];
$warnings = [];

// ═══════════════════════════════════════════════════════════════════════════════
// 1️⃣ انتقال البيانات بين صفحات الدفع
// ═══════════════════════════════════════════════════════════════════════════════
echo "1️⃣  فحص انتقال البيانات بين صفحات الدفع (step1 → step2 → step3)\n";
echo str_repeat("─", 79) . "\n";

// Check CheckoutController for session handling
$checkoutController = file_get_contents(__DIR__ . '/app/Http/Controllers/Front/CheckoutController.php');

$check1_1 = strpos($checkoutController, 'vendor_step1') !== false;
$check1_2 = strpos($checkoutController, 'vendor_step2') !== false;
$check1_3 = strpos($checkoutController, 'checkout_vendor_id') !== false;

if ($check1_1 && $check1_2 && $check1_3) {
    echo "   ✅ CheckoutController يستخدم vendor-specific session keys\n";
    $results[] = "CheckoutController: Vendor session isolation implemented";
} else {
    echo "   ⚠️  CheckoutController قد لا يستخدم vendor-specific sessions بشكل كامل\n";
    $warnings[] = "CheckoutController: Check vendor session implementation";
}

// Check for session merging issues
$check1_4 = preg_match('/array_merge.*Session::get\(.*cart/i', $checkoutController);
if ($check1_4) {
    echo "   ⚠️  تحذير: يوجد array_merge مع session cart (قد يسبب دمج بيانات)\n";
    $warnings[] = "CheckoutController: array_merge with cart detected";
} else {
    echo "   ✅ لا توجد عمليات دمج مشبوهة للسلة\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// 2️⃣ إنشاء الطلب في قاعدة البيانات
// ═══════════════════════════════════════════════════════════════════════════════
echo "2️⃣  فحص إنشاء الطلب في قاعدة البيانات\n";
echo str_repeat("─", 79) . "\n";

// Get recent orders for analysis
$recentOrders = Order::orderBy('id', 'desc')->take(20)->get();

$singleVendorCount = 0;
$multiVendorCount = 0;
$invalidOrders = [];

foreach ($recentOrders as $order) {
    $vendorIds = json_decode($order->vendor_ids, true);

    if (!is_array($vendorIds)) {
        $invalidOrders[] = "Order #{$order->id}: vendor_ids is not valid JSON";
        continue;
    }

    if (count($vendorIds) === 1) {
        $singleVendorCount++;

        // Check order items match vendor
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

                // Convert to int for proper comparison (JSON may store as string)
                $vendorIdsInt = array_map('intval', $vendorIds);
                $cartVendorsInt = array_map('intval', $cartVendors);
                sort($vendorIdsInt);
                sort($cartVendorsInt);

                if ($vendorIdsInt !== $cartVendorsInt) {
                    $errors[] = "Order #{$order->id}: vendor_ids mismatch with cart vendors";
                }
            }
        }
    } else {
        $multiVendorCount++;
        // Check if this is recent (post-fix)
        $orderDate = $order->created_at;
        $fixDate = \Carbon\Carbon::parse('2025-10-20 12:00:00');

        if ($orderDate->gt($fixDate)) {
            $errors[] = "Order #{$order->id}: Multi-vendor order created AFTER fix date ({$orderDate})";
        }
    }
}

echo "   إجمالي الطلبات المفحوصة: {$recentOrders->count()}\n";
echo "   ✅ طلبات بائع واحد: $singleVendorCount\n";
echo "   ⚠️  طلبات متعددة البائعين: $multiVendorCount\n";

if (!empty($invalidOrders)) {
    echo "   ❌ طلبات غير صالحة: " . count($invalidOrders) . "\n";
    foreach ($invalidOrders as $inv) {
        echo "      - $inv\n";
        $errors[] = $inv;
    }
}

// Check VendorOrders
$vendorOrdersCount = VendorOrder::count();
$recentVendorOrders = VendorOrder::orderBy('id', 'desc')->take(10)->get();

echo "\n   📦 فحص VendorOrders:\n";
echo "   إجمالي: $vendorOrdersCount\n";

foreach ($recentVendorOrders as $vo) {
    $mainOrder = Order::find($vo->order_id);
    if ($mainOrder) {
        $vendorIds = json_decode($mainOrder->vendor_ids, true);
        if (is_array($vendorIds) && in_array($vo->user_id, $vendorIds)) {
            echo "   ✅ VendorOrder #{$vo->id} → Order #{$vo->order_id} (Vendor: {$vo->user_id})\n";
        } else {
            echo "   ❌ VendorOrder #{$vo->id} vendor mismatch\n";
            $errors[] = "VendorOrder #{$vo->id}: vendor_id {$vo->user_id} not in main order vendor_ids";
        }
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// 3️⃣ الفواتير والإيميلات
// ═══════════════════════════════════════════════════════════════════════════════
echo "3️⃣  فحص الفواتير والإيميلات\n";
echo str_repeat("─", 79) . "\n";

// Check email templates
$emailPaths = [
    'resources/views/email/order/*.blade.php',
    'resources/views/email/*.blade.php',
    'resources/views/vendor/email/*.blade.php'
];

$emailTemplatesFound = 0;
foreach ($emailPaths as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    $emailTemplatesFound += count($files);
}

echo "   قوالب الإيميل الموجودة: $emailTemplatesFound\n";

// Check MuaadhMailer
if (file_exists(__DIR__ . '/app/Classes/MuaadhMailer.php')) {
    $mailerContent = file_get_contents(__DIR__ . '/app/Classes/MuaadhMailer.php');

    if (strpos($mailerContent, 'sendAutoOrderMail') !== false) {
        echo "   ✅ MuaadhMailer::sendAutoOrderMail موجودة\n";
    }

    if (strpos($mailerContent, 'vendor') !== false || strpos($mailerContent, 'Vendor') !== false) {
        echo "   ✅ MuaadhMailer تحتوي على منطق vendor\n";
    } else {
        echo "   ⚠️  MuaadhMailer قد لا تحتوي على منطق vendor\n";
        $warnings[] = "MuaadhMailer: No vendor-specific logic detected";
    }
}

// Check invoice views
$invoiceViews = [
    'resources/views/user/order/invoice.blade.php',
    'resources/views/admin/order/invoice.blade.php',
    'resources/views/vendor/order/invoice.blade.php'
];

foreach ($invoiceViews as $view) {
    if (file_exists(__DIR__ . '/' . $view)) {
        echo "   ✅ {$view} موجودة\n";

        $content = file_get_contents(__DIR__ . '/' . $view);
        if (strpos($content, 'vendor_id') !== false || strpos($content, 'user_id') !== false) {
            echo "      └─ تحتوي على منطق vendor\n";
        }
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// 4️⃣ Webhooks وCallbacks
// ═══════════════════════════════════════════════════════════════════════════════
echo "4️⃣  فحص Webhooks وCallbacks\n";
echo str_repeat("─", 79) . "\n";

$webhookControllers = [
    'MyFatoorahController' => 'app/Http/Controllers/Payment/Checkout/MyFatoorahController.php',
    'PaypalController' => 'app/Http/Controllers/Payment/Checkout/PaypalController.php',
    'StripeController' => 'app/Http/Controllers/Payment/Checkout/StripeController.php',
    'PaystackController' => 'app/Http/Controllers/Payment/Checkout/PaystackController.php'
];

foreach ($webhookControllers as $name => $path) {
    if (!file_exists(__DIR__ . '/' . $path)) {
        echo "   ⚠️  {$name} غير موجود\n";
        continue;
    }

    $content = file_get_contents(__DIR__ . '/' . $path);

    // Check for webhook/notify/callback methods
    $hasWebhook = preg_match('/function\s+(webhook|notify|callback)/i', $content);
    $hasOrderDuplication = preg_match('/Order.*create.*Order.*create/is', $content);
    $hasVendorCheck = strpos($content, 'vendor_id') !== false || strpos($content, 'vendor_ids') !== false;

    echo "   📡 {$name}:\n";

    if ($hasWebhook) {
        echo "      ✅ يحتوي على webhook/notify/callback\n";
    } else {
        echo "      ⚠️  لا يحتوي على webhook method واضح\n";
    }

    if ($hasOrderDuplication) {
        echo "      ⚠️  تحذير: قد يوجد Order->create متعدد (خطر تكرار الطلب)\n";
        $warnings[] = "{$name}: Potential duplicate order creation";
    }

    if ($hasVendorCheck) {
        echo "      ✅ يحتوي على فحص vendor_id/vendor_ids\n";
    }

    // Check for prepareOrderData usage
    if (strpos($content, 'prepareOrderData') !== false) {
        echo "      ✅ يستخدم prepareOrderData()\n";
    } elseif (strpos($content, 'PriceHelper::getOrderTotal') !== false) {
        echo "      ❌ لا يزال يستخدم PriceHelper::getOrderTotal\n";
        $errors[] = "{$name}: Still using PriceHelper::getOrderTotal";
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// 5️⃣ Logs والأخطاء
// ═══════════════════════════════════════════════════════════════════════════════
echo "5️⃣  فحص Logs والأخطاء\n";
echo str_repeat("─", 79) . "\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    echo "   📄 Laravel Log: " . number_format($logSize / 1024, 2) . " KB\n";

    // Get last 1000 lines
    $logLines = [];
    $handle = fopen($logFile, 'r');
    if ($handle) {
        // Seek to end and read last portion
        fseek($handle, max(0, $logSize - 100000), SEEK_SET);
        while (($line = fgets($handle)) !== false) {
            $logLines[] = $line;
        }
        fclose($handle);
    }

    $logContent = implode('', array_slice($logLines, -1000));

    // Check for specific errors
    $priceHelperErrors = preg_match_all('/PriceHelper::getOrderTotal/i', $logContent, $matches);
    $undefinedTotalErrors = preg_match_all('/Undefined (index|offset|array key):.*total/i', $logContent, $matches2);
    $vendorErrors = preg_match_all('/vendor_id.*error|error.*vendor_id/i', $logContent, $matches3);

    if ($priceHelperErrors > 0) {
        echo "   ❌ يوجد $priceHelperErrors سطر يحتوي على PriceHelper::getOrderTotal\n";
        $errors[] = "Laravel Log: $priceHelperErrors references to PriceHelper::getOrderTotal";
    } else {
        echo "   ✅ لا توجد إشارات لـ PriceHelper::getOrderTotal\n";
    }

    if ($undefinedTotalErrors > 0) {
        echo "   ❌ يوجد $undefinedTotalErrors خطأ Undefined index: total\n";
        $errors[] = "Laravel Log: $undefinedTotalErrors 'Undefined index: total' errors";
    } else {
        echo "   ✅ لا توجد أخطاء Undefined index: total\n";
    }

    if ($vendorErrors > 0) {
        echo "   ⚠️  يوجد $vendorErrors خطأ متعلق بـ vendor_id\n";
        $warnings[] = "Laravel Log: $vendorErrors vendor_id related errors";
    } else {
        echo "   ✅ لا توجد أخطاء متعلقة بـ vendor_id\n";
    }

    // Check for recent errors (last 24 hours)
    $recentErrors = preg_match_all('/\[20\d{2}-\d{2}-\d{2}.*ERROR/i', $logContent);
    echo "   📊 أخطاء حديثة (آخر 1000 سطر): $recentErrors\n";

} else {
    echo "   ⚠️  ملف Laravel Log غير موجود\n";
    $warnings[] = "Laravel Log file not found";
}

// Check for duplicate orders in database
echo "\n   🔍 فحص الطلبات المكررة:\n";

$duplicateCheck = DB::select("
    SELECT order_number, COUNT(*) as count
    FROM orders
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY order_number
    HAVING count > 1
");

if (count($duplicateCheck) > 0) {
    echo "   ❌ يوجد " . count($duplicateCheck) . " order_number مكرر:\n";
    foreach ($duplicateCheck as $dup) {
        echo "      - {$dup->order_number} ({$dup->count} مرات)\n";
        $errors[] = "Duplicate order_number: {$dup->order_number} ({$dup->count} times)";
    }
} else {
    echo "   ✅ لا توجد طلبات مكررة\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// النتيجة النهائية
// ═══════════════════════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "   📊 ملخص النتائج - Summary Report\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "✅ حالات ناجحة: " . count($results) . "\n";
foreach ($results as $r) {
    echo "   • $r\n";
}

echo "\n⚠️  تحذيرات: " . count($warnings) . "\n";
foreach ($warnings as $w) {
    echo "   • $w\n";
}

echo "\n❌ أخطاء: " . count($errors) . "\n";
foreach ($errors as $e) {
    echo "   • $e\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";

if (count($errors) === 0 && count($warnings) < 3) {
    echo "   🎉 النظام في حالة جيدة - System is in good condition\n";
    exit(0);
} elseif (count($errors) === 0) {
    echo "   ⚠️  النظام يعمل ولكن توجد تحذيرات - System working with warnings\n";
    exit(1);
} else {
    echo "   ❌ يوجد أخطاء تحتاج إلى معالجة - Errors need attention\n";
    exit(2);
}
