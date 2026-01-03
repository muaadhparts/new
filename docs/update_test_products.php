<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== تحديث بيانات تجريبية للمنتجين ===\n\n";

// المنتج الأول 260603AU0B
$product1 = DB::table('products')->where('part_number', '260603AU0B')->first();
if ($product1) {
    echo "تحديث المنتج 1 (260603AU0B)...\n";

    DB::table('products')->where('id', $product1->id)->update([
        'label_en' => 'Side Mirror Cover Left',
        'label_ar' => 'غطاء مرآة جانبية يسار',
    ]);

    // التأكد من وجود brand_quality للمنتج
    $merchantProduct1 = DB::table('merchant_products')->where('product_id', $product1->id)->first();
    if ($merchantProduct1) {
        // التأكد من وجود brand_quality_id
        if (!$merchantProduct1->brand_quality_id) {
            // جلب أول brand_quality متاح
            $quality = DB::table('brand_qualities')->first();
            if ($quality) {
                DB::table('merchant_products')
                    ->where('id', $merchantProduct1->id)
                    ->update(['brand_quality_id' => $quality->id]);
                echo "  - تم ربط brand_quality_id = {$quality->id}\n";
            }
        } else {
            echo "  - brand_quality_id موجود بالفعل = {$merchantProduct1->brand_quality_id}\n";
        }
    }

    echo "  - تم تحديث المنتج بـ label_en و label_ar\n";
} else {
    echo "المنتج 260603AU0B غير موجود\n";
}

// المنتج الثاني 1520831u0b (قد يكون بحروف كبيرة أو صغيرة)
$product2 = DB::table('products')->whereRaw('LOWER(part_number) = ?', ['1520831u0b'])->first();
if ($product2) {
    echo "\nتحديث المنتج 2 (1520831u0b)...\n";

    DB::table('products')->where('id', $product2->id)->update([
        'label_en' => 'Oil Filter Element',
        'label_ar' => 'عنصر فلتر الزيت',
    ]);

    // التأكد من وجود brand_quality للمنتج
    $merchantProduct2 = DB::table('merchant_products')->where('product_id', $product2->id)->first();
    if ($merchantProduct2) {
        // التأكد من وجود brand_quality_id
        if (!$merchantProduct2->brand_quality_id) {
            // جلب أول brand_quality متاح
            $quality = DB::table('brand_qualities')->first();
            if ($quality) {
                DB::table('merchant_products')
                    ->where('id', $merchantProduct2->id)
                    ->update(['brand_quality_id' => $quality->id]);
                echo "  - تم ربط brand_quality_id = {$quality->id}\n";
            }
        } else {
            echo "  - brand_quality_id موجود بالفعل = {$merchantProduct2->brand_quality_id}\n";
        }
    }

    echo "  - تم تحديث المنتج بـ label_en و label_ar\n";
} else {
    echo "\nالمنتج 1520831u0b غير موجود\n";
}

// تحديث بعض البراندات بـ name_ar
echo "\n=== تحديث بعض البراندات بـ name_ar ===\n";
$brands = DB::table('brands')->whereNull('name_ar')->orWhere('name_ar', '')->take(5)->get();
foreach ($brands as $brand) {
    // تحويل الاسم الإنجليزي لنسخة عربية تقريبية
    $arabicName = $brand->name; // سنستخدم نفس الاسم مع إضافة علامة
    DB::table('brands')->where('id', $brand->id)->update([
        'name_ar' => 'براند: ' . $brand->name,
    ]);
    echo "  - تم تحديث البراند {$brand->name}\n";
}

// تحديث بعض الكاتجوريز بـ name_ar
echo "\n=== تحديث بعض التصنيفات بـ name_ar ===\n";
$categories = DB::table('categories')->whereNull('name_ar')->orWhere('name_ar', '')->take(5)->get();
foreach ($categories as $cat) {
    DB::table('categories')->where('id', $cat->id)->update([
        'name_ar' => 'قسم: ' . $cat->name,
    ]);
    echo "  - تم تحديث التصنيف {$cat->name}\n";
}

// تحديث بعض الـ subcategories بـ name_ar
$subcategories = DB::table('subcategories')->whereNull('name_ar')->orWhere('name_ar', '')->take(5)->get();
foreach ($subcategories as $subcat) {
    DB::table('subcategories')->where('id', $subcat->id)->update([
        'name_ar' => 'فرع: ' . $subcat->name,
    ]);
    echo "  - تم تحديث الفرع {$subcat->name}\n";
}

// تحديث بعض الـ childcategories بـ name_ar
$childcategories = DB::table('childcategories')->whereNull('name_ar')->orWhere('name_ar', '')->take(5)->get();
foreach ($childcategories as $child) {
    DB::table('childcategories')->where('id', $child->id)->update([
        'name_ar' => 'تصنيف فرعي: ' . $child->name,
    ]);
    echo "  - تم تحديث التصنيف الفرعي {$child->name}\n";
}

// تحديث بعض brand_qualities بـ name_ar
echo "\n=== تحديث brand_qualities بـ name_ar ===\n";
$qualities = DB::table('brand_qualities')->whereNull('name_ar')->orWhere('name_ar', '')->take(5)->get();
foreach ($qualities as $quality) {
    DB::table('brand_qualities')->where('id', $quality->id)->update([
        'name_ar' => 'جودة: ' . ($quality->name_en ?: 'غير محدد'),
    ]);
    echo "  - تم تحديث الجودة {$quality->name_en}\n";
}

echo "\n=== اكتمل التحديث ===\n";
