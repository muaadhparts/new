<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== جلب بيانات المنتجين ===\n\n";

// المنتج الأول
$product1 = DB::table('products')->where('part_number', '260603AU0B')->first();
echo "Product 1 (260603AU0B) from products:\n";
if ($product1) {
    print_r($product1);
} else {
    echo "Not found\n";
}

// المنتج الثاني
$product2 = DB::table('products')->whereRaw('LOWER(part_number) = ?', ['1520831u0b'])->first();
echo "\n\nProduct 2 (1520831u0b) from products:\n";
if ($product2) {
    print_r($product2);
} else {
    echo "Not found\n";
}

// جلب من merchant_products
if ($product1) {
    $mp1 = DB::table('merchant_products')->where('product_id', $product1->id)->get();
    echo "\n\nMerchant Products for product 1:\n";
    foreach ($mp1 as $mp) {
        print_r($mp);
    }
}

if ($product2) {
    $mp2 = DB::table('merchant_products')->where('product_id', $product2->id)->get();
    echo "\n\nMerchant Products for product 2:\n";
    foreach ($mp2 as $mp) {
        print_r($mp);
    }
}

// جلب من product_fitments
if ($product1) {
    $pf1 = DB::table('product_fitments')->where('product_id', $product1->id)->get();
    echo "\n\nProduct Fitments for product 1:\n";
    foreach ($pf1 as $pf) {
        print_r($pf);
    }
}

if ($product2) {
    $pf2 = DB::table('product_fitments')->where('product_id', $product2->id)->get();
    echo "\n\nProduct Fitments for product 2:\n";
    foreach ($pf2 as $pf) {
        print_r($pf);
    }
}

// جلب بيانات البراند للمنتج الأول
if ($product1 && $product1->brand_id) {
    $brand1 = DB::table('brands')->where('id', $product1->brand_id)->first();
    echo "\n\nBrand for product 1:\n";
    print_r($brand1);
}

// جلب بيانات brand_qualities للـ merchant_products
if ($product1) {
    $mp1_with_quality = DB::table('merchant_products')
        ->join('brand_qualities', 'merchant_products.brand_quality_id', '=', 'brand_qualities.id')
        ->where('merchant_products.product_id', $product1->id)
        ->select('brand_qualities.*')
        ->first();
    if ($mp1_with_quality) {
        echo "\n\nBrand Quality for product 1:\n";
        print_r($mp1_with_quality);
    }
}

// عرض هيكل الجداول
echo "\n\n=== هيكل الجداول ===\n";
echo "\nproducts columns: " . implode(', ', \Schema::getColumnListing('products'));
echo "\nmerchant_products columns: " . implode(', ', \Schema::getColumnListing('merchant_products'));
echo "\nbrands columns: " . implode(', ', \Schema::getColumnListing('brands'));
echo "\nbrand_qualities columns: " . implode(', ', \Schema::getColumnListing('brand_qualities'));
echo "\ncategories columns: " . implode(', ', \Schema::getColumnListing('categories'));
echo "\nsubcategories columns: " . implode(', ', \Schema::getColumnListing('subcategories'));
echo "\nchildcategories columns: " . implode(', ', \Schema::getColumnListing('childcategories'));
echo "\nproduct_fitments columns: " . implode(', ', \Schema::getColumnListing('product_fitments'));

// جلب عينة من الكاتجوري
if ($product1) {
    $cat = DB::table('categories')->where('id', $product1->category_id)->first();
    echo "\n\nCategory for product 1:\n";
    print_r($cat);

    if ($product1->subcategory_id) {
        $subcat = DB::table('subcategories')->where('id', $product1->subcategory_id)->first();
        echo "\n\nSubcategory for product 1:\n";
        print_r($subcat);
    }
}
