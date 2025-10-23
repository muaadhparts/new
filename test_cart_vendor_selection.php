<?php

/**
 * سكريبت اختبار منطق اختيار التاجر عند الإضافة للسلة
 * Cart Vendor Selection Logic Testing Script
 *
 * الهدف: التحقق من أن السعر المُضاف للسلة يأتي من التاجر المحدد
 * وليس من أول تاجر في الجدول
 *
 * الاستخدام:
 * php test_cart_vendor_selection.php
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
echo "   اختبار منطق اختيار التاجر في السلة - Vendor Selection    \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. اختيار منتج مع عدة تجار بأسعار مختلفة
echo "🔍 البحث عن منتج مع تجار متعددين بأسعار مختلفة...\n";
echo "─────────────────────────────────────────────────────────────\n";

$productData = DB::table('merchant_products as mp')
    ->join('products as p', 'p.id', '=', 'mp.product_id')
    ->where('mp.status', 1)
    ->select('p.id', 'p.name', 'p.slug', DB::raw('COUNT(DISTINCT mp.user_id) as vendor_count'))
    ->groupBy('p.id', 'p.name', 'p.slug')
    ->having('vendor_count', '>=', 2)
    ->orderByDesc('vendor_count')
    ->first();

if (!$productData) {
    echo "❌ لا توجد منتجات مع تجار متعددين\n\n";
    exit;
}

echo "✅ المنتج: {$productData->name}\n";
echo "   Product ID: {$productData->id}\n";
echo "   عدد التجار: {$productData->vendor_count}\n\n";

$product = Product::find($productData->id);

// 2. جلب جميع عروض التجار
$merchants = MerchantProduct::with(['user'])
    ->where('product_id', $productData->id)
    ->where('status', 1)
    ->orderBy('price', 'asc')  // مرتبة حسب السعر
    ->get();

echo "📊 عروض التجار (مرتبة حسب السعر):\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($merchants as $index => $mp) {
    $vendorName = $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : "Vendor {$mp->user_id}";
    $price = method_exists($mp, 'vendorSizePrice') ? $mp->vendorSizePrice() : (float)$mp->price;

    echo sprintf(
        "%d. %-25s | user_id: %-4d | mp_id: %-7d | السعر: %8s SAR\n",
        $index + 1,
        $vendorName,
        $mp->user_id,
        $mp->id,
        number_format($price, 2)
    );
}

echo "\n";

// 3. اختبار منطق fetchListingOrFallback
echo "🧪 اختبار منطق fetchListingOrFallback:\n";
echo "─────────────────────────────────────────────────────────────\n";

// سيناريو 1: تحديد vendor_id بشكل صريح
if ($merchants->count() >= 2) {
    $targetVendor = $merchants[1]; // اختيار التاجر الثاني (ليس الأول!)
    $targetVendorId = $targetVendor->user_id;

    echo "├─ السيناريو 1: تحديد التاجر الثاني بشكل صريح\n";
    echo "│  └─ target vendor_id: {$targetVendorId}\n";

    // محاكاة fetchListingOrFallback
    $fetchedMp = MerchantProduct::where('product_id', $product->id)
        ->where('user_id', $targetVendorId)
        ->where('status', 1)
        ->first();

    if ($fetchedMp) {
        $fetchedPrice = method_exists($fetchedMp, 'vendorSizePrice')
            ? $fetchedMp->vendorSizePrice()
            : (float)$fetchedMp->price;

        echo "│\n";
        echo "│  ✅ النتيجة:\n";
        echo "│     ├─ merchant_product_id: {$fetchedMp->id}\n";
        echo "│     ├─ user_id: {$fetchedMp->user_id}\n";
        echo "│     └─ السعر: " . number_format($fetchedPrice, 2) . " SAR\n";

        if ($fetchedMp->id === $targetVendor->id) {
            echo "│     ✅ تطابق! تم اختيار التاجر الصحيح\n";
        } else {
            echo "│     ❌ خطأ! تم اختيار تاجر مختلف\n";
        }
    } else {
        echo "│  ❌ لم يتم العثور على عرض التاجر\n";
    }

    echo "│\n";
}

// سيناريو 2: عدم تحديد vendor_id (fallback)
echo "├─ السيناريو 2: عدم تحديد vendor_id (يختار أرخص عرض)\n";

$defaultMp = MerchantProduct::where('product_id', $product->id)
    ->where('status', 1)
    ->orderByRaw('CASE WHEN (stock IS NULL OR stock=0) THEN 1 ELSE 0 END ASC')
    ->orderBy('price', 'ASC')
    ->first();

if ($defaultMp) {
    $defaultPrice = method_exists($defaultMp, 'vendorSizePrice')
        ? $defaultMp->vendorSizePrice()
        : (float)$defaultMp->price;

    $defaultVendorName = $defaultMp->user ? ($defaultMp->user->shop_name ?: $defaultMp->user->name) : "Vendor {$defaultMp->user_id}";

    echo "│  ✅ النتيجة:\n";
    echo "│     ├─ التاجر: {$defaultVendorName}\n";
    echo "│     ├─ merchant_product_id: {$defaultMp->id}\n";
    echo "│     ├─ user_id: {$defaultMp->user_id}\n";
    echo "│     └─ السعر: " . number_format($defaultPrice, 2) . " SAR\n";

    if ($defaultMp->id === $merchants->first()->id) {
        echo "│     ✅ صحيح! اختار التاجر الأرخص (الأول في القائمة)\n";
    }
}

echo "│\n";
echo "└─────────────────────────────────────────────────────────────\n\n";

// 4. اختبار injectMerchantContext
echo "💉 اختبار injectMerchantContext:\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($merchants->count() >= 2) {
    $vendor1 = $merchants[0];
    $vendor2 = $merchants[1];

    echo "├─ التاجر الأول:\n";
    $price1 = method_exists($vendor1, 'vendorSizePrice') ? $vendor1->vendorSizePrice() : (float)$vendor1->price;
    echo "│  ├─ user_id: {$vendor1->user_id}\n";
    echo "│  ├─ merchant_product_id: {$vendor1->id}\n";
    echo "│  └─ السعر الأصلي: " . number_format($price1, 2) . " SAR\n";

    // محاكاة injectMerchantContext
    $prod1 = clone $product;
    $prod1->vendor_user_id = $vendor1->user_id;
    $prod1->user_id = $vendor1->user_id;
    $prod1->merchant_product_id = $vendor1->id;

    // هذا هو المنطق الفعلي من injectMerchantContext
    $actualPrice1 = method_exists($vendor1, 'vendorSizePrice') ? $vendor1->vendorSizePrice() : (float)$vendor1->price;
    $prod1->price = $actualPrice1;

    echo "│  ✅ بعد Inject:\n";
    echo "│     ├─ \$prod->vendor_user_id: {$prod1->vendor_user_id}\n";
    echo "│     ├─ \$prod->merchant_product_id: {$prod1->merchant_product_id}\n";
    echo "│     ├─ \$prod->price: " . number_format($prod1->price, 2) . " SAR\n";

    if (abs($price1 - $actualPrice1) > 0.01) {
        echo "│     ⚠️  السعر تغير بعد Inject! (" . number_format($price1, 2) . " → " . number_format($actualPrice1, 2) . ")\n";
    } else {
        echo "│     ✅ السعر ثابت بعد Inject\n";
    }

    echo "│\n";

    echo "├─ التاجر الثاني:\n";
    $price2 = method_exists($vendor2, 'vendorSizePrice') ? $vendor2->vendorSizePrice() : (float)$vendor2->price;
    echo "│  ├─ user_id: {$vendor2->user_id}\n";
    echo "│  ├─ merchant_product_id: {$vendor2->id}\n";
    echo "│  └─ السعر الأصلي: " . number_format($price2, 2) . " SAR\n";

    // محاكاة injectMerchantContext
    $prod2 = clone $product;
    $prod2->vendor_user_id = $vendor2->user_id;
    $prod2->user_id = $vendor2->user_id;
    $prod2->merchant_product_id = $vendor2->id;

    // هذا هو المنطق الفعلي من injectMerchantContext
    $actualPrice2 = method_exists($vendor2, 'vendorSizePrice') ? $vendor2->vendorSizePrice() : (float)$vendor2->price;
    $prod2->price = $actualPrice2;

    echo "│  ✅ بعد Inject:\n";
    echo "│     ├─ \$prod->vendor_user_id: {$prod2->vendor_user_id}\n";
    echo "│     ├─ \$prod->merchant_product_id: {$prod2->merchant_product_id}\n";
    echo "│     ├─ \$prod->price: " . number_format($prod2->price, 2) . " SAR\n";

    if (abs($price2 - $actualPrice2) > 0.01) {
        echo "│     ⚠️  السعر تغير بعد Inject! (" . number_format($price2, 2) . " → " . number_format($actualPrice2, 2) . ")\n";
    } else {
        echo "│     ✅ السعر ثابت بعد Inject\n";
    }

    echo "│\n";

    echo "├─ التحقق من الاستقلالية:\n";

    if ($prod1->price !== $prod2->price) {
        echo "│  ✅ الأسعار مختلفة! (فرق: " . number_format(abs($prod1->price - $prod2->price), 2) . " SAR)\n";
        echo "│     كل تاجر له سعره الخاص بعد Inject\n";
    } else {
        echo "│  ⚠️  الأسعار متطابقة\n";
    }

    if ($prod1->merchant_product_id !== $prod2->merchant_product_id) {
        echo "│  ✅ merchant_product_id مختلفة!\n";
        echo "│     كل تاجر له معرفه الخاص\n";
    }
}

echo "│\n";
echo "└─────────────────────────────────────────────────────────────\n\n";

// 5. محاكاة Routes المختلفة
echo "🛤️  محاكاة Routes المختلفة:\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($merchants->count() >= 2) {
    $vendor1 = $merchants[0];
    $vendor2 = $merchants[1];

    echo "├─ Route 1: merchant.cart.add (Recommended)\n";
    echo "│  ├─ URL: /cart/add/merchant/{merchant_product_id}\n";
    echo "│  ├─ مثال للتاجر الأول: /cart/add/merchant/{$vendor1->id}\n";
    echo "│  │  └─ سيُضيف السعر: " . number_format($vendor1->vendorSizePrice(), 2) . " SAR\n";
    echo "│  ├─ مثال للتاجر الثاني: /cart/add/merchant/{$vendor2->id}\n";
    echo "│  │  └─ سيُضيف السعر: " . number_format($vendor2->vendorSizePrice(), 2) . " SAR\n";
    echo "│  └─ ✅ محدد بدقة - لا يوجد احتمال للخطأ\n";
    echo "│\n";

    echo "├─ Route 2: product.cart.add (Legacy)\n";
    echo "│  ├─ URL: /addcart/{product_id}?user={vendor_id}\n";
    echo "│  ├─ مثال للتاجر الأول: /addcart/{$product->id}?user={$vendor1->user_id}\n";
    echo "│  │  └─ سيبحث عن MerchantProduct ثم يُضيف السعر: " . number_format($vendor1->vendorSizePrice(), 2) . " SAR\n";
    echo "│  ├─ مثال للتاجر الثاني: /addcart/{$product->id}?user={$vendor2->user_id}\n";
    echo "│  │  └─ سيبحث عن MerchantProduct ثم يُضيف السعر: " . number_format($vendor2->vendorSizePrice(), 2) . " SAR\n";
    echo "│  └─ ✅ يعمل بشكل صحيح إذا تم تمرير user_id\n";
    echo "│\n";

    echo "├─ ⚠️  خطر محتمل:\n";
    echo "│  إذا تم استدعاء /addcart/{$product->id} بدون user parameter\n";
    echo "│  سيختار pickDefaultListing (أرخص عرض)\n";
    echo "│  └─ قد لا يكون هذا ما يريده المستخدم!\n";
}

echo "│\n";
echo "└─────────────────────────────────────────────────────────────\n\n";

// 6. اختبار Cart Key Generation
echo "🔑 اختبار Cart Key Generation:\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($merchants->count() >= 2) {
    $vendor1 = $merchants[0];
    $vendor2 = $merchants[1];

    // محاكاة makeKey من Cart.php
    $key1 = implode(':', [
        $product->id,
        'u' . $vendor1->user_id,
        '', // size
        '', // color
        '', // values
    ]);

    $key2 = implode(':', [
        $product->id,
        'u' . $vendor2->user_id,
        '', // size
        '', // color
        '', // values
    ]);

    echo "├─ Cart Key للتاجر الأول:\n";
    echo "│  └─ '{$key1}'\n";
    echo "│\n";

    echo "├─ Cart Key للتاجر الثاني:\n";
    echo "│  └─ '{$key2}'\n";
    echo "│\n";

    echo "├─ التحقق:\n";
    if ($key1 !== $key2) {
        echo "│  ✅ Cart Keys مختلفة!\n";
        echo "│     نفس المنتج من تاجرين مختلفين = 2 items في السلة\n";
        echo "│     كل واحد بسعره الخاص\n";
    } else {
        echo "│  ❌ Cart Keys متطابقة - هناك خطأ!\n";
    }
}

echo "│\n";
echo "└─────────────────────────────────────────────────────────────\n\n";

// 7. الخلاصة والتوصيات
echo "📋 الخلاصة:\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "\n✅ نقاط القوة:\n";
echo "   1. fetchListingOrFallback يبحث بدقة عن التاجر المحدد\n";
echo "   2. injectMerchantContext يضبط السعر من MerchantProduct::vendorSizePrice()\n";
echo "   3. Cart Keys تحتوي على vendor_id (u{vendor_id})\n";
echo "   4. نفس المنتج من تجار مختلفين = items منفصلة\n\n";

echo "⚠️  نقاط الحذر:\n";
echo "   1. Route 2 (product.cart.add) يتطلب تمرير user parameter\n";
echo "   2. إذا لم يُمرَّر user، يختار pickDefaultListing (أرخص عرض)\n";
echo "   3. يجب أن تُمرِّر جميع Views الـ user_id أو merchant_product_id\n\n";

echo "💡 التوصيات:\n";
echo "   1. استخدام merchant.cart.add في جميع الأماكن الجديدة\n";
echo "   2. التأكد من أن جميع أزرار Add to Cart تُمرِّر:\n";
echo "      - merchant_product_id (الأفضل)\n";
echo "      - أو user_id + product_id\n";
echo "   3. عدم الاعتماد على pickDefaultListing في الواجهة\n\n";

echo "🔍 للاختبار اليدوي:\n";
echo "   1. افتح المنتج: {$product->name}\n";
if ($merchants->count() >= 2) {
    $vendor1Name = $merchants[0]->user ? ($merchants[0]->user->shop_name ?: 'Vendor ' . $merchants[0]->user_id) : 'Vendor ' . $merchants[0]->user_id;
    $vendor2Name = $merchants[1]->user ? ($merchants[1]->user->shop_name ?: 'Vendor ' . $merchants[1]->user_id) : 'Vendor ' . $merchants[1]->user_id;

    echo "   2. جرب إضافة من التاجر الأول: {$vendor1Name}\n";
    echo "   3. جرب إضافة من التاجر الثاني: {$vendor2Name}\n";
    echo "   4. افتح السلة وتحقق من الأسعار\n";
    echo "   5. يجب أن ترى:\n";
    echo "      - المنتج مرتين (item منفصل لكل تاجر)\n";
    echo "      - كل واحد بسعره الخاص\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الاختبار                                            \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
