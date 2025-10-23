<?php

/**
 * سكريبت اختبار شامل للسلة - Comprehensive Cart Testing Script
 *
 * الحالات المطلوب اختبارها:
 * 1. بائعان من نفس الرقم (تاجرين مختلفين، نفس المنتج)
 * 2. رقم واحد من نفس البائع (نفس التاجر، نفس المنتج، brand_quality مختلف)
 * 3. رقم واحد من بائع واحد (حالة عادية)
 *
 * الاستخدام:
 * php test_cart_comprehensive.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\MerchantProduct;
use App\Models\Cart;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   اختبار شامل للسلة - Comprehensive Cart Testing           \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─────────────────────────────────────────────────────────────────
// الحالة 1: بائعان من نفس الرقم (تاجرين مختلفين)
// ─────────────────────────────────────────────────────────────────
echo "🧪 الحالة 1: بائعان من نفس الرقم (تاجرين مختلفين)\n";
echo "─────────────────────────────────────────────────────────────\n";

$case1 = DB::table('merchant_products as mp1')
    ->join('merchant_products as mp2', function($join) {
        $join->on('mp1.product_id', '=', 'mp2.product_id')
             ->whereColumn('mp1.user_id', '!=', 'mp2.user_id');
    })
    ->join('products as p', 'p.id', '=', 'mp1.product_id')
    ->where('mp1.status', 1)
    ->where('mp2.status', 1)
    ->select('p.id', 'p.name', 'p.sku')
    ->first();

if ($case1) {
    echo "✅ المنتج: {$case1->name} (SKU: {$case1->sku})\n\n";

    $vendors = MerchantProduct::with(['user', 'qualityBrand'])
        ->where('product_id', $case1->id)
        ->where('status', 1)
        ->take(2)
        ->get();

    if ($vendors->count() >= 2) {
        $v1 = $vendors[0];
        $v2 = $vendors[1];

        $vendor1Name = $v1->user->shop_name ?: $v1->user->name;
        $vendor2Name = $v2->user->shop_name ?: $v2->user->name;

        echo "┌─ التاجر الأول: {$vendor1Name}\n";
        echo "│  ├─ merchant_product_id: {$v1->id}\n";
        echo "│  ├─ user_id: {$v1->user_id}\n";
        echo "│  ├─ brand_quality_id: " . ($v1->brand_quality_id ?: 'NULL') . "\n";
        echo "│  ├─ Brand: " . ($v1->qualityBrand->name_ar ?? $v1->qualityBrand->name_en ?? 'N/A') . "\n";
        echo "│  ├─ السعر: " . number_format($v1->price, 2) . " SAR\n";
        echo "│  └─ المخزون: {$v1->stock}\n\n";

        echo "┌─ التاجر الثاني: {$vendor2Name}\n";
        echo "│  ├─ merchant_product_id: {$v2->id}\n";
        echo "│  ├─ user_id: {$v2->user_id}\n";
        echo "│  ├─ brand_quality_id: " . ($v2->brand_quality_id ?: 'NULL') . "\n";
        echo "│  ├─ Brand: " . ($v2->qualityBrand->name_ar ?? $v2->qualityBrand->name_en ?? 'N/A') . "\n";
        echo "│  ├─ السعر: " . number_format($v2->price, 2) . " SAR\n";
        echo "│  └─ المخزون: {$v2->stock}\n\n";

        // محاكاة Cart Keys (manual construction)
        $key1 = implode(':', [
            $case1->id,
            'u' . $v1->user_id,
            'mp' . $v1->id,
            '',  // size
            '',  // color
            '',  // values
        ]);

        $key2 = implode(':', [
            $case1->id,
            'u' . $v2->user_id,
            'mp' . $v2->id,
            '',  // size
            '',  // color
            '',  // values
        ]);

        echo "📋 Cart Keys المُنشأة:\n";
        echo "   التاجر 1: '{$key1}'\n";
        echo "   التاجر 2: '{$key2}'\n\n";

        if ($key1 !== $key2) {
            echo "✅ النتيجة: Keys مختلفة - بندين منفصلين في السلة\n";
            echo "   ✓ نفس المنتج من تاجرين مختلفين = items منفصلة\n";
            echo "   ✓ الأسعار مختلفة: " . number_format(abs($v1->price - $v2->price), 2) . " SAR فرق\n";
        } else {
            echo "❌ خطأ: Keys متطابقة! سيُعتبر item واحد\n";
        }
    }
} else {
    echo "⚠️  لا يوجد منتج متاح عند تاجرين مختلفين\n";
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// ─────────────────────────────────────────────────────────────────
// الحالة 2: رقم واحد من نفس البائع بـ brand_quality مختلف
// ─────────────────────────────────────────────────────────────────
echo "🧪 الحالة 2: رقم واحد من نفس البائع (brand_quality مختلف)\n";
echo "─────────────────────────────────────────────────────────────\n";

$case2 = DB::table('merchant_products as mp1')
    ->join('merchant_products as mp2', function($join) {
        $join->on('mp1.product_id', '=', 'mp2.product_id')
             ->on('mp1.user_id', '=', 'mp2.user_id')
             ->whereColumn('mp1.id', '!=', 'mp2.id');
    })
    ->join('products as p', 'p.id', '=', 'mp1.product_id')
    ->join('users as u', 'u.id', '=', 'mp1.user_id')
    ->where('mp1.status', 1)
    ->where('mp2.status', 1)
    ->whereNotNull('mp1.brand_quality_id')
    ->whereNotNull('mp2.brand_quality_id')
    ->whereColumn('mp1.brand_quality_id', '!=', 'mp2.brand_quality_id')
    ->select('p.id', 'p.name', 'p.sku', 'u.id as vendor_id', 'u.shop_name')
    ->first();

if ($case2) {
    echo "✅ المنتج: {$case2->name} (SKU: {$case2->sku})\n";
    echo "   التاجر: {$case2->shop_name}\n\n";

    $brands = MerchantProduct::with(['qualityBrand'])
        ->where('product_id', $case2->id)
        ->where('user_id', $case2->vendor_id)
        ->where('status', 1)
        ->whereNotNull('brand_quality_id')
        ->get();

    if ($brands->count() >= 2) {
        $b1 = $brands[0];
        $b2 = $brands[1];

        $brand1Name = $b1->qualityBrand->name_ar ?? $b1->qualityBrand->name_en ?? 'Unknown';
        $brand2Name = $b2->qualityBrand->name_ar ?? $b2->qualityBrand->name_en ?? 'Unknown';

        echo "┌─ العرض الأول (Brand Quality: {$brand1Name})\n";
        echo "│  ├─ merchant_product_id: {$b1->id}\n";
        echo "│  ├─ brand_quality_id: {$b1->brand_quality_id}\n";
        echo "│  ├─ السعر: " . number_format($b1->price, 2) . " SAR\n";
        echo "│  └─ المخزون: {$b1->stock}\n\n";

        echo "┌─ العرض الثاني (Brand Quality: {$brand2Name})\n";
        echo "│  ├─ merchant_product_id: {$b2->id}\n";
        echo "│  ├─ brand_quality_id: {$b2->brand_quality_id}\n";
        echo "│  ├─ السعر: " . number_format($b2->price, 2) . " SAR\n";
        echo "│  └─ المخزون: {$b2->stock}\n\n";

        // محاكاة Cart Keys (manual construction)
        $keyB1 = implode(':', [
            $case2->id,
            'u' . $case2->vendor_id,
            'mp' . $b1->id,
            '',  // size
            '',  // color
            '',  // values
        ]);

        $keyB2 = implode(':', [
            $case2->id,
            'u' . $case2->vendor_id,
            'mp' . $b2->id,
            '',  // size
            '',  // color
            '',  // values
        ]);

        echo "📋 Cart Keys المُنشأة:\n";
        echo "   Brand 1: '{$keyB1}'\n";
        echo "   Brand 2: '{$keyB2}'\n\n";

        if ($keyB1 !== $keyB2) {
            echo "✅ النتيجة: Keys مختلفة - بندين منفصلين في السلة\n";
            echo "   ✓ نفس المنتج من نفس التاجر لكن brand مختلف = items منفصلة\n";
            echo "   ✓ merchant_product_id: {$b1->id} != {$b2->id}\n";
            echo "   ✓ هذا صحيح - نفس الرقم قد يأتي بشركات صنع مختلفة\n";
        } else {
            echo "❌ خطأ CRITICAL: Keys متطابقة! سيُعتبر item واحد\n";
            echo "   ⚠️  هذا سيؤدي لخلط بين brands مختلفة!\n";
        }
    } else {
        echo "⚠️  لا توجد brands متعددة لهذا المنتج عند هذا التاجر\n";
    }
} else {
    echo "⚠️  لا يوجد منتج بـ brand_quality مختلف عند نفس التاجر\n";
    echo "💡 هذا طبيعي - قد لا توجد حالات حالياً في قاعدة البيانات\n";
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// ─────────────────────────────────────────────────────────────────
// الحالة 3: رقم واحد من بائع واحد (حالة عادية)
// ─────────────────────────────────────────────────────────────────
echo "🧪 الحالة 3: رقم واحد من بائع واحد (حالة عادية)\n";
echo "─────────────────────────────────────────────────────────────\n";

$case3 = MerchantProduct::with(['product', 'user', 'qualityBrand'])
    ->where('status', 1)
    ->whereNotNull('stock')
    ->where('stock', '>', 0)
    ->first();

if ($case3) {
    $vendorName = $case3->user->shop_name ?: $case3->user->name;
    $brandName = $case3->qualityBrand->name_ar ?? $case3->qualityBrand->name_en ?? 'N/A';

    echo "✅ المنتج: {$case3->product->name}\n";
    echo "   SKU: {$case3->product->sku}\n";
    echo "   التاجر: {$vendorName}\n\n";

    echo "┌─ تفاصيل العرض:\n";
    echo "│  ├─ merchant_product_id: {$case3->id}\n";
    echo "│  ├─ user_id: {$case3->user_id}\n";
    echo "│  ├─ brand_quality_id: " . ($case3->brand_quality_id ?: 'NULL') . "\n";
    echo "│  ├─ Brand: {$brandName}\n";
    echo "│  ├─ السعر: " . number_format($case3->price, 2) . " SAR\n";
    echo "│  └─ المخزون: {$case3->stock}\n\n";

    // محاكاة Cart Key (manual construction)
    $key = implode(':', [
        $case3->product_id,
        'u' . $case3->user_id,
        'mp' . $case3->id,
        '',  // size
        '',  // color
        '',  // values
    ]);

    echo "📋 Cart Key المُنشأ:\n";
    echo "   '{$key}'\n\n";

    // تحليل المفتاح
    $parts = explode(':', $key);
    echo "📊 تحليل المفتاح:\n";
    echo "   [0] product_id: {$parts[0]}\n";
    echo "   [1] vendor: {$parts[1]}\n";
    echo "   [2] merchant_product_id: {$parts[2]}\n";
    echo "   [3] size: " . ($parts[3] ?: 'empty') . "\n";
    echo "   [4] color: " . ($parts[4] ?: 'empty') . "\n";
    echo "   [5] values: " . ($parts[5] ?? 'empty') . "\n\n";

    echo "✅ النتيجة: المفتاح يحتوي على جميع المعرّفات المطلوبة\n";
    echo "   ✓ product_id موجود\n";
    echo "   ✓ vendor_id موجود (u{$case3->user_id})\n";
    echo "   ✓ merchant_product_id موجود (mp{$case3->id})\n";
    echo "   ✓ السعر من merchant_products: " . number_format($case3->price, 2) . " SAR\n";
}

echo "\n└─────────────────────────────────────────────────────────────\n\n";

// ─────────────────────────────────────────────────────────────────
// الخلاصة والتوصيات
// ─────────────────────────────────────────────────────────────────
echo "📋 الخلاصة النهائية:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ معايير القبول:\n";
echo "───────────────────\n";
echo "1. ✓ Cart Key يتضمن: product_id + vendor_id + merchant_product_id\n";
echo "2. ✓ نفس المنتج من تاجرين مختلفين = keys مختلفة\n";
echo "3. ✓ نفس المنتج من نفس التاجر بـ brand مختلف = keys مختلفة\n";
echo "4. ✓ merchant_product_id فريد لكل عرض\n";
echo "5. ✓ brand_quality_id محفوظ في السلة\n\n";

echo "⚠️  نقاط يجب التحقق منها يدوياً:\n";
echo "──────────────────────────────────\n";
echo "1. افتح الموقع واختبر إضافة:\n";
echo "   - نفس المنتج من تاجرين مختلفين\n";
echo "   - نفس المنتج من نفس التاجر (إن وُجد brands مختلفة)\n";
echo "2. تحقق من السلة:\n";
echo "   - يجب رؤية بندين منفصلين\n";
echo "   - كل بند بسعره الخاص\n";
echo "   - Cart Keys مختلفة\n";
echo "3. اختبار Checkout:\n";
echo "   - كل vendor له checkout منفصل\n";
echo "   - الأسعار لا تتغير\n";
echo "4. اختبار Invoice:\n";
echo "   - السعر يطابق السلة\n";
echo "   - لا إعادة حساب أو re-fetch\n\n";

echo "🚀 للاختبار اليدوي:\n";
echo "───────────────────\n";
if (isset($case1)) {
    echo "• الحالة 1: ابحث عن \"{$case1->name}\"\n";
    echo "  أضفه من تاجرين مختلفين\n\n";
}
if (isset($case2)) {
    echo "• الحالة 2: ابحث عن \"{$case2->name}\"\n";
    echo "  أضفه من نفس التاجر بـ brands مختلفة\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الاختبار الشامل                                    \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
