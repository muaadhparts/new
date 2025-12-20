<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   فحص brand_quality_id في merchant_products                 \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// فحص NULL values
$nullCount = DB::table('merchant_products')
    ->whereNull('brand_quality_id')
    ->where('status', 1)
    ->count();

$totalActive = DB::table('merchant_products')
    ->where('status', 1)
    ->count();

echo "📊 الإحصائيات:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "   Total Active Listings: {$totalActive}\n";
echo "   NULL brand_quality_id: {$nullCount}\n";
echo "   Percentage: " . number_format(($nullCount/$totalActive)*100, 2) . "%\n\n";

if ($nullCount > 0) {
    echo "⚠️  توجد {$nullCount} سجلات نشطة بدون brand_quality_id\n\n";
    echo "💡 التوصية:\n";
    echo "   1. تحديث هذه السجلات لتحمل brand_quality_id افتراضي\n";
    echo "   2. أو إنشاء brand_quality عام (مثل: 'Generic')\n";
    echo "   3. تحديث جميع NULL values لهذا الـ brand\n\n";

    // عرض عينة
    $samples = DB::table('merchant_products')
        ->join('products', 'products.id', '=', 'merchant_products.product_id')
        ->join('users', 'users.id', '=', 'merchant_products.user_id')
        ->whereNull('merchant_products.brand_quality_id')
        ->where('merchant_products.status', 1)
        ->select('merchant_products.id as mp_id', 'products.name', 'products.sku',
                 'users.shop_name', 'merchant_products.price')
        ->limit(10)
        ->get();

    echo "📋 عينة من السجلات بدون brand_quality_id:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    foreach ($samples as $s) {
        $shopName = $s->shop_name ?: 'Unknown';
        echo "\n┌─ MP ID: {$s->mp_id}\n";
        echo "│  ├─ Product: {$s->name}\n";
        echo "│  ├─ SKU: {$s->sku}\n";
        echo "│  ├─ Vendor: {$shopName}\n";
        echo "│  └─ Price: " . number_format($s->price, 2) . " SAR\n";
    }

    echo "\n\n⚡ سكريبت تصحيح (اختياري):\n";
    echo "─────────────────────────────────────────────────────────────\n";
    echo "-- خيار 1: إنشاء brand_quality عام\n";
    echo "INSERT INTO brand_qualities (name_en, name_ar, status, created_at, updated_at)\n";
    echo "VALUES ('Generic', 'عام', 1, NOW(), NOW());\n\n";
    echo "-- خيار 2: تحديث جميع NULL values\n";
    echo "UPDATE merchant_products\n";
    echo "SET brand_quality_id = (SELECT id FROM brand_qualities WHERE name_en = 'Generic')\n";
    echo "WHERE brand_quality_id IS NULL AND status = 1;\n";

} else {
    echo "✅ ممتاز! جميع السجلات النشطة تحمل brand_quality_id\n";
    echo "   لا حاجة لأي تحديث\n";
}

echo "\n\n";

// فحص التكرارات (نفس المنتج من نفس التاجر بـ brands مختلفة)
echo "🔍 فحص المنتجات المتكررة بـ brand_quality مختلف:\n";
echo "─────────────────────────────────────────────────────────────\n";

$duplicates = DB::select("
    SELECT
        mp1.product_id,
        mp1.user_id,
        p.name,
        p.sku,
        u.shop_name,
        COUNT(DISTINCT mp1.brand_quality_id) as brand_count,
        COUNT(*) as total_listings
    FROM merchant_products mp1
    JOIN products p ON p.id = mp1.product_id
    JOIN users u ON u.id = mp1.user_id
    WHERE mp1.status = 1
    GROUP BY mp1.product_id, mp1.user_id, p.name, p.sku, u.shop_name
    HAVING COUNT(*) > 1
    LIMIT 10
");

if (count($duplicates) > 0) {
    echo "✅ وُجد " . count($duplicates) . " منتج متكرر عند نفس التاجر\n\n";

    foreach ($duplicates as $dup) {
        echo "┌─ المنتج: {$dup->name} (SKU: {$dup->sku})\n";
        echo "│  ├─ التاجر: {$dup->shop_name}\n";
        echo "│  ├─ عدد Brands مختلفة: {$dup->brand_count}\n";
        echo "│  └─ إجمالي Listings: {$dup->total_listings}\n\n";

        // تفاصيل كل listing
        $details = DB::table('merchant_products as mp')
            ->leftJoin('brand_qualities as bq', 'bq.id', '=', 'mp.brand_quality_id')
            ->where('mp.product_id', $dup->product_id)
            ->where('mp.user_id', $dup->user_id)
            ->where('mp.status', 1)
            ->select('mp.id', 'mp.price', 'mp.brand_quality_id', 'bq.name_ar', 'bq.name_en')
            ->get();

        echo "   📦 Listings:\n";
        foreach ($details as $d) {
            $brandName = $d->name_ar ?: $d->name_en ?: 'NULL';
            echo "      • MP ID: {$d->id} | Brand: {$brandName} | السعر: " . number_format($d->price, 2) . " SAR\n";
        }
        echo "\n";
    }

    echo "✅ Cart Key سيميز بينهم بـ merchant_product_id\n";
} else {
    echo "⚠️  لا توجد منتجات متكررة حالياً\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   انتهى الفحص                                               \n";
echo "═══════════════════════════════════════════════════════════════\n\n";
