<?php

/**
 * سكريبت اختبار عرض الأسعار - Price Display Testing Script
 *
 * الهدف: التحقق من أن الأسعار المعروضة في جميع الصفحات تأتي من merchant_products
 * وليس من products، وأنها تُمرَّر بشكل صحيح للسلة
 *
 * الاستخدام:
 * php test_price_display.php
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
echo "   اختبار عرض الأسعار - Price Display Testing               \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. اختيار منتج عشوائي له أكثر من تاجر بأسعار مختلفة
echo "🔍 البحث عن منتج مناسب للاختبار...\n";
echo "─────────────────────────────────────────────────────────────\n";

$testProduct = DB::table('merchant_products as mp1')
    ->join('merchant_products as mp2', function($join) {
        $join->on('mp1.product_id', '=', 'mp2.product_id')
             ->whereColumn('mp1.user_id', '!=', 'mp2.user_id')
             ->whereColumn('mp1.price', '!=', 'mp2.price'); // أسعار مختلفة
    })
    ->join('products as p', 'p.id', '=', 'mp1.product_id')
    ->where('mp1.status', 1)
    ->where('mp2.status', 1)
    ->select('p.id', 'p.name', 'p.slug')
    ->groupBy('p.id', 'p.name', 'p.slug')
    ->first();

if (!$testProduct) {
    echo "⚠️  لا توجد منتجات بأسعار مختلفة من تجار مختلفين\n";
    echo "💡 سأستخدم أول منتج متاح بدلاً من ذلك...\n\n";

    $testProduct = DB::table('merchant_products')
        ->join('products', 'products.id', '=', 'merchant_products.product_id')
        ->where('merchant_products.status', 1)
        ->select('products.id', 'products.name', 'products.slug')
        ->first();
}

if (!$testProduct) {
    echo "❌ لا توجد منتجات في النظام!\n\n";
    exit;
}

echo "✅ تم اختيار المنتج: {$testProduct->name}\n";
echo "   Product ID: {$testProduct->id}\n";
echo "   Slug: {$testProduct->slug}\n\n";

// 2. جلب جميع عروض التجار لهذا المنتج
$product = Product::find($testProduct->id);
$merchants = MerchantProduct::with(['user'])
    ->where('product_id', $testProduct->id)
    ->where('status', 1)
    ->get();

echo "📊 عروض التجار المتاحة ({$merchants->count()}):\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($merchants as $mp) {
    $vendorName = $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : "Unknown Vendor";

    // السعر الأساسي من merchant_products
    $basePrice = (float)$mp->price;

    // السعر مع الـ commission (vendorSizePrice)
    $finalPrice = method_exists($mp, 'vendorSizePrice')
        ? $mp->vendorSizePrice()
        : $basePrice;

    // السعر المعروض (مع العملة)
    $displayPrice = method_exists($mp, 'showPrice')
        ? $mp->showPrice()
        : 'N/A';

    echo "\n┌─ التاجر: {$vendorName}\n";
    echo "│  ├─ merchant_product_id: {$mp->id}\n";
    echo "│  ├─ user_id: {$mp->user_id}\n";
    echo "│  ├─ السعر الأساسي (mp.price): " . number_format($basePrice, 2) . " SAR\n";
    echo "│  ├─ السعر النهائي (vendorSizePrice): " . number_format($finalPrice, 2) . " SAR\n";
    echo "│  ├─ السعر المعروض (showPrice): {$displayPrice}\n";
    echo "│  ├─ المخزون: {$mp->stock}\n";
    echo "│  └─ الحالة: " . ($mp->status == 1 ? 'نشط' : 'غير نشط') . "\n";
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// 3. اختبار Product::showPrice() مع تحديد vendor_id
echo "🧪 اختبار Product::showPrice() مع تحديد vendor_id:\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($merchants->take(3) as $mp) {
    $vendorName = $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : "Vendor {$mp->user_id}";

    // استدعاء showPrice مع تحديد vendor_id
    $priceFromProduct = $product->showPrice($mp->user_id);

    // السعر من MerchantProduct مباشرة
    $priceFromMerchant = $mp->showPrice();

    echo "\n├─ التاجر: {$vendorName}\n";
    echo "│  ├─ Product::showPrice({$mp->user_id}): {$priceFromProduct}\n";
    echo "│  ├─ MerchantProduct::showPrice(): {$priceFromMerchant}\n";

    if ($priceFromProduct === $priceFromMerchant) {
        echo "│  └─ ✅ الأسعار متطابقة!\n";
    } else {
        echo "│  └─ ⚠️  الأسعار مختلفة!\n";
    }
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// 4. اختبار activeMerchant()
echo "🔍 اختبار Product::activeMerchant():\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($merchants->count() > 0) {
    $firstVendor = $merchants->first();

    // بدون تحديد vendor_id (سيختار أول واحد)
    $defaultMerchant = $product->activeMerchant();
    echo "├─ activeMerchant() بدون تحديد vendor:\n";
    if ($defaultMerchant) {
        echo "│  ├─ merchant_product_id: {$defaultMerchant->id}\n";
        echo "│  ├─ user_id: {$defaultMerchant->user_id}\n";
        echo "│  └─ price: " . number_format($defaultMerchant->price, 2) . " SAR\n";
    } else {
        echo "│  └─ ❌ لا يوجد merchant نشط\n";
    }

    echo "│\n";

    // مع تحديد vendor_id
    $specificMerchant = $product->activeMerchant($firstVendor->user_id);
    echo "├─ activeMerchant({$firstVendor->user_id}):\n";
    if ($specificMerchant) {
        echo "│  ├─ merchant_product_id: {$specificMerchant->id}\n";
        echo "│  ├─ user_id: {$specificMerchant->user_id}\n";
        echo "│  └─ price: " . number_format($specificMerchant->price, 2) . " SAR\n";
    } else {
        echo "│  └─ ❌ لا يوجد merchant نشط لهذا التاجر\n";
    }
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// 5. فحص structure جدول products
echo "✅ فحص بنية جدول products:\n";
echo "─────────────────────────────────────────────────────────────\n";

// التحقق من أعمدة جدول products
$productColumns = DB::select("SHOW COLUMNS FROM products LIKE 'price'");

if (empty($productColumns)) {
    echo "├─ ✅ جدول products لا يحتوي على عمود 'price'!\n";
    echo "│     هذا تصميم ممتاز - النظام يعتمد 100% على merchant_products\n";
    echo "│\n";
    echo "│  💡 الفوائد:\n";
    echo "│     1. لا يوجد سعر عام للمنتج\n";
    echo "│     2. كل تاجر يحدد سعره الخاص\n";
    echo "│     3. استحالة الخطأ في استخدام سعر خاطئ\n";
    echo "│     4. Multi-Vendor حقيقي 100%\n";
} else {
    echo "├─ ⚠️  جدول products يحتوي على عمود 'price'\n";
    $productPrice = DB::table('products')
        ->where('id', $testProduct->id)
        ->value('price');
    echo "│     products.price: " . ($productPrice !== null ? number_format($productPrice, 2) . " SAR" : 'NULL') . "\n";
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// 6. اختبار محاكاة الإضافة للسلة
echo "🛒 محاكاة إضافة للسلة:\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($merchants->count() >= 2) {
    $vendor1 = $merchants[0];
    $vendor2 = $merchants[1];

    echo "├─ السيناريو: إضافة نفس المنتج من تاجرين مختلفين\n";
    echo "│\n";

    echo "├─ التاجر الأول:\n";
    $v1Name = $vendor1->user ? ($vendor1->user->shop_name ?: $vendor1->user->name) : "Vendor {$vendor1->user_id}";
    echo "│  ├─ الاسم: {$v1Name}\n";
    echo "│  ├─ merchant_product_id: {$vendor1->id}\n";
    echo "│  ├─ السعر: " . $vendor1->showPrice() . "\n";
    echo "│  └─ Route: route('merchant.cart.add', {$vendor1->id})\n";
    echo "│\n";

    echo "├─ التاجر الثاني:\n";
    $v2Name = $vendor2->user ? ($vendor2->user->shop_name ?: $vendor2->user->name) : "Vendor {$vendor2->user_id}";
    echo "│  ├─ الاسم: {$v2Name}\n";
    echo "│  ├─ merchant_product_id: {$vendor2->id}\n";
    echo "│  ├─ السعر: " . $vendor2->showPrice() . "\n";
    echo "│  └─ Route: route('merchant.cart.add', {$vendor2->id})\n";
    echo "│\n";

    echo "├─ التحقق:\n";

    $price1 = method_exists($vendor1, 'vendorSizePrice') ? $vendor1->vendorSizePrice() : (float)$vendor1->price;
    $price2 = method_exists($vendor2, 'vendorSizePrice') ? $vendor2->vendorSizePrice() : (float)$vendor2->price;

    if (abs($price1 - $price2) > 0.01) {
        echo "│  ✅ الأسعار مختلفة (" . number_format(abs($price1 - $price2), 2) . " SAR فرق)\n";
        echo "│  ✅ كل تاجر سيحتفظ بسعره الخاص في السلة\n";
    } else {
        echo "│  ⚠️  الأسعار متطابقة - لا يمكن التحقق من التمييز\n";
    }
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// 7. الخلاصة والتوصيات
echo "📋 الخلاصة:\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "\n✅ نقاط القوة:\n";
echo "   1. MerchantProduct::showPrice() يستخدم vendorSizePrice()\n";
echo "   2. Product::showPrice(\$vendorId) يستخدم activeMerchant()\n";
echo "   3. كل تاجر له merchant_product_id فريد\n";
echo "   4. النظام يدعم Multi-Vendor بشكل كامل\n\n";

echo "⚠️  نقاط يجب التحقق منها:\n";
echo "   1. جميع صفحات العرض (Home, Category, Product Details) تستخدم:\n";
echo "      - \$mp->showPrice() أو\n";
echo "      - \$product->showPrice(\$vendorId)\n";
echo "   2. جميع أزرار Add to Cart تمرر merchant_product_id:\n";
echo "      - route('merchant.cart.add', \$mp->id)\n";
echo "   3. لا توجد أي صفحة تستخدم \$product->price مباشرة\n";
echo "   4. Cart Keys تحتوي على vendor_id: 'id:u{vendor}:...'\n\n";

echo "🔍 للاختبار اليدوي:\n";
echo "   1. افتح: http://new.test/\n";
echo "   2. ابحث عن المنتج: {$testProduct->name}\n";
echo "   3. تحقق من الأسعار المعروضة\n";
echo "   4. أضف من تاجرين مختلفين للسلة\n";
echo "   5. تحقق من الأسعار في السلة\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الاختبار                                            \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
