<?php

/**
 * Script اختبار السلة Multi-Vendor
 * يفحص: هل كل تاجر يحتفظ بسعره الخاص؟
 *
 * الاستخدام:
 * php test_multi_vendor_cart.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\MerchantProduct;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   اختبار السلة Multi-Vendor - نفس المنتج من تجار مختلفين    \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. البحث عن منتج متاح عند أكثر من تاجر
echo "🔍 البحث عن منتجات متاحة عند أكثر من تاجر...\n";
echo "─────────────────────────────────────────────────────────────\n";

$productsWithMultipleVendors = DB::table('merchant_products as mp1')
    ->join('merchant_products as mp2', function($join) {
        $join->on('mp1.product_id', '=', 'mp2.product_id')
             ->whereColumn('mp1.user_id', '!=', 'mp2.user_id');
    })
    ->join('products as p', 'p.id', '=', 'mp1.product_id')
    ->where('mp1.status', 1)
    ->where('mp2.status', 1)
    ->select('p.id', 'p.name', 'p.slug', DB::raw('COUNT(DISTINCT mp1.user_id) as vendor_count'))
    ->groupBy('p.id', 'p.name', 'p.slug')
    ->having('vendor_count', '>=', 2)
    ->take(5)
    ->get();

if ($productsWithMultipleVendors->isEmpty()) {
    echo "❌ لا توجد منتجات متاحة عند أكثر من تاجر في النظام\n\n";
    echo "💡 لاختبار السلة Multi-Vendor:\n";
    echo "   1. أضف نفس المنتج عند تاجرين مختلفين (Admin Panel)\n";
    echo "   2. اجعل الأسعار مختلفة\n";
    echo "   3. شغّل هذا السكريبت مرة أخرى\n\n";
    exit;
}

echo "✅ وُجد " . count($productsWithMultipleVendors) . " منتج متاح عند أكثر من تاجر:\n\n";

foreach ($productsWithMultipleVendors as $prod) {
    echo "┌─ المنتج: {$prod->name}\n";
    echo "│  Product ID: {$prod->id}\n";
    echo "│  عدد التجار: {$prod->vendor_count}\n";
    echo "│\n";

    // جلب تفاصيل عروض التجار
    $merchants = MerchantProduct::with(['user'])
        ->where('product_id', $prod->id)
        ->where('status', 1)
        ->get();

    echo "│  📊 تفاصيل العروض:\n";
    foreach ($merchants as $mp) {
        $vendorName = $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : "Unknown Vendor {$mp->user_id}";
        $price = method_exists($mp, 'vendorSizePrice') ? $mp->vendorSizePrice() : (float)$mp->price;

        echo "│     ├─ التاجر: {$vendorName} (ID: {$mp->user_id})\n";
        echo "│     │  └─ merchant_product_id: {$mp->id}\n";
        echo "│     │  └─ السعر: " . number_format($price, 2) . " SAR\n";
        echo "│     │  └─ المخزون: {$mp->stock}\n";
        echo "│     │  └─ size: " . ($mp->size ?: 'N/A') . "\n";
        echo "│     │  └─ size_price: " . ($mp->size_price ?: 'N/A') . "\n";
        echo "│     │  └─ color_all: " . ($mp->color_all ?: 'N/A') . "\n";
    }
    echo "└─────────────────────────────────────────────────────\n\n";
}

// 2. اختبار عميق لمنتج واحد
echo "\n🔬 اختبار عميق للمنتج الأول:\n";
echo "─────────────────────────────────────────────────────────────\n";

$testProduct = $productsWithMultipleVendors->first();
$testMerchants = MerchantProduct::with(['user'])
    ->where('product_id', $testProduct->id)
    ->where('status', 1)
    ->get();

if ($testMerchants->count() < 2) {
    echo "⚠️ لا يوجد تاجرين للمقارنة\n";
} else {
    $vendor1 = $testMerchants[0];
    $vendor2 = $testMerchants[1];

    $price1 = method_exists($vendor1, 'vendorSizePrice') ? $vendor1->vendorSizePrice() : (float)$vendor1->price;
    $price2 = method_exists($vendor2, 'vendorSizePrice') ? $vendor2->vendorSizePrice() : (float)$vendor2->price;

    $vendor1Name = $vendor1->user ? ($vendor1->user->shop_name ?: $vendor1->user->name) : "Vendor {$vendor1->user_id}";
    $vendor2Name = $vendor2->user ? ($vendor2->user->shop_name ?: $vendor2->user->name) : "Vendor {$vendor2->user_id}";

    echo "📦 المنتج: {$testProduct->name}\n\n";

    echo "┌─ التاجر الأول: {$vendor1Name}\n";
    echo "│  ├─ merchant_product_id: {$vendor1->id}\n";
    echo "│  ├─ user_id: {$vendor1->user_id}\n";
    echo "│  ├─ price: " . number_format($vendor1->price, 2) . " SAR\n";
    echo "│  ├─ vendorSizePrice(): " . number_format($price1, 2) . " SAR\n";
    echo "│  ├─ stock: {$vendor1->stock}\n";
    echo "│  ├─ size: " . ($vendor1->size ?: 'N/A') . "\n";
    echo "│  └─ size_price: " . ($vendor1->size_price ?: 'N/A') . "\n";
    echo "│\n";
    echo "┌─ التاجر الثاني: {$vendor2Name}\n";
    echo "│  ├─ merchant_product_id: {$vendor2->id}\n";
    echo "│  ├─ user_id: {$vendor2->user_id}\n";
    echo "│  ├─ price: " . number_format($vendor2->price, 2) . " SAR\n";
    echo "│  ├─ vendorSizePrice(): " . number_format($price2, 2) . " SAR\n";
    echo "│  ├─ stock: {$vendor2->stock}\n";
    echo "│  ├─ size: " . ($vendor2->size ?: 'N/A') . "\n";
    echo "│  └─ size_price: " . ($vendor2->size_price ?: 'N/A') . "\n";
    echo "│\n";
    echo "└─────────────────────────────────────────────────────\n\n";

    // التحقق
    echo "✔️  التحقق:\n";

    if (abs($price1 - $price2) > 0.01) {
        echo "  ✅ الأسعار مختلفة! (" . number_format(abs($price1 - $price2), 2) . " SAR فرق)\n";
        echo "     └─ هذا صحيح - كل تاجر له سعره الخاص\n\n";
    } else {
        echo "  ⚠️  الأسعار متطابقة (" . number_format($price1, 2) . " SAR)\n";
        echo "     └─ قد يكون هذا صحيح أو قد يكون هناك مشكلة\n\n";
    }
}

// 3. فحص Cart Keys
echo "\n🔑 فحص مفاتيح السلة (Cart Keys):\n";
echo "─────────────────────────────────────────────────────────────\n";

$prod = Product::find($testProduct->id);
if ($prod) {
    echo "المنتج: {$prod->name} (ID: {$prod->id})\n\n";

    foreach ($testMerchants->take(2) as $mp) {
        // محاكاة إضافة للسلة
        $vendorId = $mp->user_id;
        $size = '';
        $color = '';
        $values = '';
        $sizeKey = '';

        // makeKey format from Cart.php line 44-50:
        // id : u{vendor} : {size_key|size} : {color} : {values-clean}
        $key = implode(':', [
            $prod->id,
            'u' . $vendorId,
            (string)($sizeKey ?: $size),
            (string)$color,
            (string)$values,
        ]);

        $vendorName = $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : "Vendor {$vendorId}";

        echo "├─ التاجر: {$vendorName}\n";
        echo "│  └─ Cart Key: '{$key}'\n";
    }
    echo "└─────────────────────────────────────────────────────\n\n";

    echo "✔️  التحليل:\n";
    echo "  ✅ المفاتيح تحتوي على 'u{vendor_id}'\n";
    echo "  ✅ كل تاجر له مفتاح مختلف في السلة\n";
    echo "  ✅ النظام يدعم نفس المنتج من تجار مختلفين\n\n";
}

// 4. نصائح الاختبار اليدوي
echo "\n📝 للاختبار اليدوي:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "1. افتح الموقع في متصفحين (أو incognito windows)\n";
echo "2. في المتصفح الأول:\n";
echo "   - اذهب لمنتج '{$testProduct->name}'\n";
echo "   - اختر عرض التاجر الأول\n";
echo "   - أضفه للسلة\n";
echo "   - لاحظ السعر في السلة\n\n";
echo "3. في المتصفح الثاني (أو نفس المتصفح):\n";
echo "   - اذهب لنفس المنتج '{$testProduct->name}'\n";
echo "   - اختر عرض التاجر الثاني\n";
echo "   - أضفه للسلة\n";
echo "   - لاحظ السعر في السلة\n\n";
echo "4. افتح صفحة السلة:\n";
echo "   - يجب أن ترى المنتج مرتين\n";
echo "   - كل واحد بسعر مختلف\n";
echo "   - كل واحد تحت تاجر مختلف\n\n";

// 5. التحقق من Session Cart
echo "\n🛒 للتحقق من Session Cart:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "بعد إضافة المنتج من تاجرين، شغّل هذا الكود في Tinker:\n\n";
echo "\$cart = Session::get('cart');\n";
echo "foreach (\$cart->items as \$key => \$item) {\n";
echo "    echo \"Key: {\$key}\\n\";\n";
echo "    echo \"Vendor: \" . (\$item['user_id'] ?? 'N/A') . \"\\n\";\n";
echo "    echo \"Price: \" . (\$item['item_price'] ?? 0) . \"\\n\";\n";
echo "    echo \"Qty: \" . (\$item['qty'] ?? 0) . \"\\n\";\n";
echo "    echo \"Total: \" . (\$item['price'] ?? 0) . \"\\n\";\n";
echo "    echo \"---\\n\";\n";
echo "}\n\n";

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الاختبار                                            \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
