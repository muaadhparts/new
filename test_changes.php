<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\QualityBrand;

echo "=== فحص التغييرات ===\n\n";

// فحص Product
echo "1. فحص المنتجات:\n";
$product1 = Product::where('sku', '260603AU0B')->first();
if ($product1) {
    echo "   SKU: {$product1->sku}\n";
    echo "   name: {$product1->name}\n";
    echo "   label_en: {$product1->label_en}\n";
    echo "   label_ar: {$product1->label_ar}\n";

    // فحص في اللغة العربية
    app()->setLocale('ar');
    echo "   localized_name (ar): {$product1->localized_name}\n";
    echo "   showName() (ar): {$product1->showName()}\n";

    // فحص في اللغة الإنجليزية
    app()->setLocale('en');
    echo "   localized_name (en): {$product1->localized_name}\n";
    echo "   showName() (en): {$product1->showName()}\n";
}

// فحص Brand
echo "\n2. فحص البراندات:\n";
$brand = Brand::first();
if ($brand) {
    echo "   name: {$brand->name}\n";
    echo "   name_ar: {$brand->name_ar}\n";

    app()->setLocale('ar');
    echo "   localized_name (ar): {$brand->localized_name}\n";

    app()->setLocale('en');
    echo "   localized_name (en): {$brand->localized_name}\n";
}

// فحص Category
echo "\n3. فحص التصنيفات:\n";
$category = Category::first();
if ($category) {
    echo "   name: {$category->name}\n";
    echo "   name_ar: " . ($category->name_ar ?? 'N/A') . "\n";

    app()->setLocale('ar');
    echo "   localized_name (ar): {$category->localized_name}\n";

    app()->setLocale('en');
    echo "   localized_name (en): {$category->localized_name}\n";
}

// فحص QualityBrand
echo "\n4. فحص جودة البراند:\n";
$quality = QualityBrand::first();
if ($quality) {
    echo "   name_en: {$quality->name_en}\n";
    echo "   name_ar: " . ($quality->name_ar ?? 'N/A') . "\n";
    echo "   logo: " . ($quality->logo ?? 'N/A') . "\n";
    echo "   logo_url: " . ($quality->logo_url ?? 'N/A') . "\n";

    app()->setLocale('ar');
    echo "   localized_name (ar): {$quality->localized_name}\n";
    echo "   display_name (ar): {$quality->display_name}\n";

    app()->setLocale('en');
    echo "   localized_name (en): {$quality->localized_name}\n";
    echo "   display_name (en): {$quality->display_name}\n";
}

// فحص MerchantProduct مع qualityBrand
echo "\n5. فحص MerchantProduct مع العلاقات:\n";
$mp = \App\Models\MerchantProduct::with(['product', 'qualityBrand', 'user'])
    ->whereHas('product', function($q) {
        $q->where('sku', '260603AU0B');
    })
    ->first();

if ($mp) {
    echo "   product_id: {$mp->product_id}\n";
    echo "   brand_quality_id: {$mp->brand_quality_id}\n";
    echo "   user_id: {$mp->user_id}\n";

    if ($mp->product) {
        app()->setLocale('ar');
        echo "   product localized_name (ar): {$mp->product->localized_name}\n";
    }

    if ($mp->qualityBrand) {
        app()->setLocale('ar');
        echo "   qualityBrand localized_name (ar): {$mp->qualityBrand->localized_name}\n";
        echo "   qualityBrand logo_url: " . ($mp->qualityBrand->logo_url ?? 'N/A') . "\n";
    }
}

// فحص ProductFitments
echo "\n6. فحص ProductFitments:\n";
$fitments = \App\Models\ProductFitment::with(['product', 'category', 'subcategory', 'childcategory'])
    ->whereHas('product', function($q) {
        $q->where('sku', '260603AU0B');
    })
    ->get();

if ($fitments->count() > 0) {
    echo "   عدد الـ fitments للمنتج: {$fitments->count()}\n";
    foreach ($fitments as $fit) {
        echo "   - category_id: {$fit->category_id}";
        if ($fit->category) {
            echo " ({$fit->category->localized_name})";
        }
        echo "\n";
    }
} else {
    echo "   لا توجد fitments للمنتج\n";
}

echo "\n=== اكتمل الفحص ===\n";
