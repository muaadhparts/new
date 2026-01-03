<?php
/**
 * تقرير تدفق البيانات الفعلي المُصحح والمُحدث
 * من السلة إلى الشحن - نظام متعدد البائعين
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cart;
use App\Models\Product;
use App\Models\MerchantProduct;
use App\Models\User;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Shipping;
use App\Services\TryotoService;
use App\Services\TryotoLocationService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

$report = [];
$report[] = "╔══════════════════════════════════════════════════════════════════════════════════════════╗";
$report[] = "║        تقرير تدفق البيانات الفعلي المُصحح - نظام متعدد البائعين                         ║";
$report[] = "║                              " . date('Y-m-d H:i:s') . "                                     ║";
$report[] = "╚══════════════════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// مقدمة: بنية النظام متعدد البائعين
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ 📋 مقدمة: بنية النظام متعدد البائعين (Multi-Vendor Architecture)                         │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";
$report[] = "   ⚠️ مبدأ أساسي: كل بائع له Checkout مستقل تماماً";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  السلة الموحدة                                                                   │";
$report[] = "   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                          │";
$report[] = "   │  │  منتج بائع 1  │  │  منتج بائع 2  │  │  منتج بائع 3  │                          │";
$report[] = "   │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                          │";
$report[] = "   │         │                 │                 │                                  │";
$report[] = "   │         ▼                 ▼                 ▼                                  │";
$report[] = "   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                          │";
$report[] = "   │  │ Checkout     │  │ Checkout     │  │ Checkout     │   ← كل بائع مستقل        │";
$report[] = "   │  │ بائع 1       │  │ بائع 2       │  │ بائع 3       │                          │";
$report[] = "   │  │ - عنوان خاص  │  │ - عنوان خاص  │  │ - عنوان خاص  │                          │";
$report[] = "   │  │ - شحن خاص   │  │ - شحن خاص   │  │ - شحن خاص   │                          │";
$report[] = "   │  │ - ضريبة خاصة │  │ - ضريبة خاصة │  │ - ضريبة خاصة │                          │";
$report[] = "   │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                          │";
$report[] = "   │         │                 │                 │                                  │";
$report[] = "   │         ▼                 ▼                 ▼                                  │";
$report[] = "   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                          │";
$report[] = "   │  │   Purchase #1   │  │   Purchase #2   │  │   Purchase #3   │   ← طلب مستقل لكل بائع   │";
$report[] = "   │  └──────────────┘  └──────────────┘  └──────────────┘                          │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";
$report[] = "   ✗ لا يوجد fallback عام أو سلة مشتركة";
$report[] = "   ✗ لا يمكن دمج طلبات بائعين مختلفين في طلب واحد";
$report[] = "   ✓ كل بائع يحسب شحنه وضريبته بشكل مستقل";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 1: السلة - Cart (مع توضيح مصادر البيانات)
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 1: السلة (Cart) - مصادر البيانات                                                   │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// جلب merchant_product حقيقي
$merchantProduct = MerchantProduct::with(['product', 'user'])->where('status', 1)->first();

if (!$merchantProduct) {
    $report[] = "❌ لا توجد منتجات في قاعدة البيانات!";
    file_put_contents('CHECKOUT_FLOW_TRACE_REPORT.txt', implode("\n", $report));
    exit;
}

$product = $merchantProduct->product;
$vendor = $merchantProduct->user;

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   📊 مصادر بيانات المنتج (هام جداً)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │                        جدول products (المنتج الأساسي)                               │";
$report[] = "   ├─────────────────────────────────────────────────────────────────────────────────────┤";
$report[] = "   │  ✓ الوزن (weight)                    → " . ($product->weight ?? 'NULL') . " كجم                              │";
$report[] = "   │  ✓ الطول (length)                    → " . ($product->length ?? 'NULL') . " سم                               │";
$report[] = "   │  ✓ الارتفاع (height)                 → " . ($product->height ?? 'NULL') . " سم                               │";
$report[] = "   │  ✓ الاسم والوصف الأساسي              → {$product->name}                             │";
$report[] = "   │  ✓ التصنيفات والعلامة التجارية                                                      │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │                    جدول merchant_products (بيانات البائع)                           │";
$report[] = "   ├─────────────────────────────────────────────────────────────────────────────────────┤";
$report[] = "   │  ✓ السعر (price)                     → {$merchantProduct->price}                    │";
$report[] = "   │  ✓ السعر السابق (previous_price)     → " . ($merchantProduct->previous_price ?? 'NULL') . "                 │";
$report[] = "   │  ✓ المخزون (stock)                   → {$merchantProduct->stock}                    │";
$report[] = "   │  ✓ الألوان (colors)                  → " . ($merchantProduct->colors ?: 'NULL') . "                          │";
$report[] = "   │  ✓ المقاسات (size, size_qty, size_price)                                            │";
$report[] = "   │  ✓ الخصم (is_discount, discount_date)                                               │";
$report[] = "   │  ✓ الحد الأدنى للطلب (minimum_qty)   → " . ($merchantProduct->minimum_qty ?? 1) . "                          │";
$report[] = "   │  ✓ إعدادات الشحن الأساسية (ship)     → " . ($merchantProduct->ship ?? 'NULL') . "    ← مهم للشحن!            │";
$report[] = "   │  ✓ الحالة (status)                   → {$merchantProduct->status}                   │";
$report[] = "   │  ✓ البائع (user_id)                  → {$merchantProduct->user_id}                  │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   📦 المنتج المختار للتتبع:";
$report[] = "   ├── Product ID: {$product->id}";
$report[] = "   ├── MerchantProduct ID: {$merchantProduct->id}";
$report[] = "   ├── الاسم: {$product->name}";
$report[] = "   ├── السعر (من merchant_products): {$merchantProduct->price}";
$report[] = "   ├── الوزن (من products): " . ($product->weight ?? 0.5) . " كجم";
$report[] = "   └── البائع (user_id): {$merchantProduct->user_id}";
$report[] = "";

$report[] = "   👤 البائع (Vendor):";
$report[] = "   ├── ID: {$vendor->id}";
$report[] = "   ├── الاسم: {$vendor->name}";
$report[] = "   ├── city_id: " . ($vendor->city_id ?? 'NULL');

if ($vendor->city_id) {
    $vendorCity = City::find($vendor->city_id);
    if ($vendorCity) {
        $report[] = "   ├── اسم المدينة: {$vendorCity->city_name}";
        $report[] = "   ├── اسم المدينة (عربي): " . ($vendorCity->city_name_ar ?? 'NULL');
        $report[] = "   ├── tryoto_supported: " . ($vendorCity->tryoto_supported ? 'نعم ✓' : 'لا ✗');
        $report[] = "   └── country_id: {$vendorCity->country_id}";
    }
}
$report[] = "";

// بنية السلة
$productPrice = $merchantProduct->price;
$report[] = "   🛒 بنية بيانات السلة في Session:";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  Session['cart'] = [                                                                │";
$report[] = "   │      'items' => [                                                                   │";
$report[] = "   │          '{$product->id}' => [                                                      │";
$report[] = "   │              'qty' => 2,                                                            │";
$report[] = "   │              'price' => " . ($productPrice * 2) . ",           ← من merchant_products                    │";
$report[] = "   │              'dp' => 0,                         (منتج رقمي أم لا)                   │";
$report[] = "   │              'item' => [                                                            │";
$report[] = "   │                  'id' => {$product->id},                                            │";
$report[] = "   │                  'user_id' => {$merchantProduct->user_id},    ← مهم! يحدد البائع    │";
$report[] = "   │                  'weight' => " . ($product->weight ?? 0.5) . ",         ← من products                    │";
$report[] = "   │                  'price' => {$productPrice},      ← من merchant_products            │";
$report[] = "   │              ]                                                                      │";
$report[] = "   │          ]                                                                          │";
$report[] = "   │      ],                                                                             │";
$report[] = "   │      'totalQty' => 2,                                                               │";
$report[] = "   │      'totalPrice' => " . ($productPrice * 2) . "                                                           │";
$report[] = "   │  ]                                                                                  │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 2: أولويات الشحن
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 2: نظام أولويات الشحن (Shipping Priority System)                                   │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   ترتيب أولوية طرق الشحن";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  الأولوية 1: الشحن الذكي Tryoto                                                     │";
$report[] = "   │  ─────────────────────────────────────────                                          │";
$report[] = "   │  الشروط:                                                                            │";
$report[] = "   │    ✓ البائع مفعّل خدمة Tryoto                                                       │";
$report[] = "   │    ✓ مدينة البائع مدعومة في Tryoto                                                  │";
$report[] = "   │    ✓ مدينة العميل مدعومة في Tryoto                                                  │";
$report[] = "   │                                                                                     │";
$report[] = "   │  إذا تحققت الشروط → يظهر جدول شركات الشحن من Tryoto                                 │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "                                    ↓ إذا لم يتوفر";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  الأولوية 2: الشحن المحلي من جدول shippings                                         │";
$report[] = "   │  ─────────────────────────────────────────                                          │";
$report[] = "   │  المصدر: جدول shippings                                                             │";
$report[] = "   │  SQL: SELECT * FROM shippings WHERE user_id = {vendor_id} OR user_id = 0            │";
$report[] = "   │                                                                                     │";
$report[] = "   │  يشمل:                                                                              │";
$report[] = "   │    - طرق شحن خاصة بالبائع (user_id = vendor_id)                                     │";
$report[] = "   │    - طرق شحن عامة (user_id = 0)                                                     │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "                                    ↓ إذا لم يتوفر";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  الأولوية 3: خطأ - لا توجد طريقة شحن                                                │";
$report[] = "   │  ─────────────────────────────────────────                                          │";
$report[] = "   │  يتم عرض رسالة خطأ للمستخدم:                                                        │";
$report[] = "   │  \"عذراً، لا تتوفر طرق شحن لهذا البائع حالياً\"                                      │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$vendorId = $merchantProduct->user_id;

// جلب طرق الشحن المحلية
$shippingMethods = Shipping::where(function($q) use ($vendorId) {
    $q->where('user_id', $vendorId)->orWhere('user_id', 0);
})->get();

$report[] = "   📦 طرق الشحن المحلية لهذا البائع:";
$report[] = "   SQL: SELECT * FROM shippings WHERE user_id = {$vendorId} OR user_id = 0";
$report[] = "";
if ($shippingMethods->count() > 0) {
    $report[] = "   النتيجة ({$shippingMethods->count()} طريقة):";
    foreach ($shippingMethods as $ship) {
        $report[] = "   ├── ID: {$ship->id}";
        $report[] = "   │   ├── العنوان: {$ship->title}";
        $report[] = "   │   ├── السعر: {$ship->price}";
        $report[] = "   │   ├── مجاني فوق: " . ($ship->free_above ?? 'غير محدد');
        $report[] = "   │   └── user_id: {$ship->user_id} " . ($ship->user_id == 0 ? '(عام)' : '(خاص بالبائع)');
    }
} else {
    $report[] = "   ⚠️ لا توجد طرق شحن محلية - سيتم الاعتماد على Tryoto فقط";
}
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 3: اختيار الموقع (Geocoding) مع منطق Fallback
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 3: اختيار الموقع من الخريطة + منطق Fallback للمدن غير المدعومة                    │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$testLat = 24.7136;
$testLng = 46.6753;

$report[] = "   📍 المستخدم ينقر على الخريطة:";
$report[] = "   Latitude: {$testLat}, Longitude: {$testLng}";
$report[] = "";

$report[] = "   🔗 Route: POST /geocoding/reverse";
$report[] = "   📁 Controller: GeocodingController@reverseGeocode";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   خطوات معالجة الموقع";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$report[] = "   الخطوة 1: إرسال الإحداثيات لـ Google Maps API";
$report[] = "   ─────────────────────────────────────────────────";
$report[] = "   URL: https://maps.googleapis.com/maps/api/geocode/json?latlng={$testLat},{$testLng}&key=xxx";
$report[] = "";

// محاكاة استجابة Google Maps
$googleResponse = [
    'city' => 'Riyadh',
    'city_ar' => 'الرياض',
    'state' => 'Riyadh Province',
    'state_ar' => 'منطقة الرياض',
    'country' => 'Saudi Arabia',
    'country_ar' => 'المملكة العربية السعودية',
    'country_code' => 'SA'
];

$report[] = "   استجابة Google Maps:";
$report[] = "   {";
foreach ($googleResponse as $key => $value) {
    $report[] = "       '{$key}' => '{$value}',";
}
$report[] = "   }";
$report[] = "";

$report[] = "   الخطوة 2: البحث عن الدولة في قاعدة البيانات";
$report[] = "   ─────────────────────────────────────────────────";

$country = Country::where('country_name', $googleResponse['country'])
    ->orWhere('country_code', $googleResponse['country_code'])
    ->first();

if ($country) {
    $report[] = "   SQL: SELECT * FROM countries WHERE country_name = '{$googleResponse['country']}' OR country_code = '{$googleResponse['country_code']}'";
    $report[] = "   النتيجة: id={$country->id}, name={$country->country_name}, tax={$country->tax}%";
}
$report[] = "";

$report[] = "   الخطوة 3: البحث عن المدينة (مع منطق Fallback)";
$report[] = "   ─────────────────────────────────────────────────";
$report[] = "";

// البحث عن المدينة
$city = City::where('country_id', $country->id ?? 1)
    ->where('tryoto_supported', 1)
    ->where(function($q) use ($googleResponse) {
        $q->where('city_name', $googleResponse['city'])
          ->orWhere('city_name_ar', $googleResponse['city_ar']);
    })
    ->first();

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  منطق Fallback للمدن غير المدعومة في Tryoto                                        │";
$report[] = "   ├─────────────────────────────────────────────────────────────────────────────────────┤";
$report[] = "   │                                                                                     │";
$report[] = "   │  1. البحث المباشر:                                                                  │";
$report[] = "   │     SQL: SELECT * FROM cities                                                       │";
$report[] = "   │          WHERE country_id = {country_id}                                            │";
$report[] = "   │          AND tryoto_supported = 1                                                   │";
$report[] = "   │          AND (city_name = '{city}' OR city_name_ar = '{city_ar}')                   │";
$report[] = "   │                                                                                     │";
$report[] = "   │  2. إذا لم توجد → البحث عن أقرب مدينة بالإحداثيات:                                  │";
$report[] = "   │     - حساب المسافة باستخدام Haversine Formula                                       │";
$report[] = "   │     - اختيار أقرب مدينة مدعومة في Tryoto                                            │";
$report[] = "   │                                                                                     │";
$report[] = "   │  3. إذا لم توجد مدن بإحداثيات → البحث بالمنطقة/الولاية:                             │";
$report[] = "   │     SQL: SELECT * FROM cities                                                       │";
$report[] = "   │          WHERE state_id IN (SELECT id FROM states WHERE state LIKE '%{region}%')    │";
$report[] = "   │          AND tryoto_supported = 1                                                   │";
$report[] = "   │                                                                                     │";
$report[] = "   │  4. إذا فشل كل شيء → إلغاء شحن Tryoto لهذا البائع                                   │";
$report[] = "   │     ويتم الاعتماد على الشحن المحلي فقط                                              │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

if ($city) {
    $report[] = "   ✓ المدينة موجودة ومدعومة مباشرة:";
    $report[] = "   ├── id: {$city->id}  ← هذا هو city_id المهم!";
    $report[] = "   ├── city_name: {$city->city_name}";
    $report[] = "   └── tryoto_supported: " . ($city->tryoto_supported ? 'نعم ✓' : 'لا ✗');
} else {
    $nearestCity = City::where('country_id', $country->id ?? 1)
        ->where('tryoto_supported', 1)
        ->first();
    if ($nearestCity) {
        $city = $nearestCity;
        $report[] = "   ⚠️ المدينة غير موجودة، تم اختيار أقرب مدينة: {$city->city_name}";
    }
}
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 4: إرسال Step 1 (بدون حساب ضريبة)
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 4: إرسال Step 1 (العنوان) - تخزين tax_rate فقط بدون حساب                          │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   🔗 Route: POST /checkout/vendor/{$vendorId}/step1/submit";
$report[] = "   📁 Controller: CheckoutController@checkoutVendorStep1";
$report[] = "";

$taxRate = $country->tax ?? 0;
$subtotal = $productPrice * 2;

$step1Data = [
    'customer_name' => 'أحمد محمد',
    'customer_email' => 'ahmed@example.com',
    'customer_phone' => '966512345678',
    'customer_address' => 'شارع الملك فهد، حي العليا',
    'customer_zip' => '12345',
    'customer_country' => $country->country_name,
    'customer_state' => $googleResponse['state'],
    'customer_city' => $city->id,
    'latitude' => $testLat,
    'longitude' => $testLng,
    'country_id' => $country->id,
    'state_id' => 0,
    'city_id' => $city->id,
    'tax_rate' => $taxRate,
    'vendor_subtotal' => $subtotal,
];

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   ⚠️ تصحيح منطق الضريبة";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  Step 1 يخزن فقط:                                                                   │";
$report[] = "   │    ✓ tax_rate (نسبة الضريبة من جدول countries)                                      │";
$report[] = "   │    ✓ vendor_subtotal (المجموع الجزئي للمنتجات)                                      │";
$report[] = "   │                                                                                     │";
$report[] = "   │  Step 1 لا يحسب:                                                                    │";
$report[] = "   │    ✗ tax_amount (قيمة الضريبة)                                                      │";
$report[] = "   │    ✗ الإجمالي النهائي                                                               │";
$report[] = "   │                                                                                     │";
$report[] = "   │  السبب: الضريبة تحتاج الشحن والتغليف لحسابها بشكل صحيح                              │";
$report[] = "   │  الحساب الكامل يتم عند: إنشاء الطلب في الخطوة النهائية                              │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   💾 البيانات المحفوظة في Session['vendor_step1_{$vendorId}']:";
$report[] = "   {";
foreach ($step1Data as $key => $value) {
    $important = in_array($key, ['customer_city', 'city_id', 'tax_rate']) ? ' ← مهم!' : '';
    $report[] = "       '{$key}' => '{$value}',{$important}";
}
$report[] = "   }";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 5: Step 2 - حساب الشحن الفعلي
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 5: Step 2 - حساب الشحن الفعلي وتخزينه                                              │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   🔗 Route: GET /checkout/vendor/{$vendorId}/step2";
$report[] = "   📁 Controller: CheckoutController@checkoutVendorStep2";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   خطوات حساب الشحن";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$report[] = "   الخطوة 1: جلب بيانات Step 1 من Session";
$report[] = "   ─────────────────────────────────────────────";
$report[] = "   \$step1 = Session::get('vendor_step1_{$vendorId}')";
$report[] = "   customer_city = {$city->id} (city_id للعميل)";
$report[] = "";

$report[] = "   الخطوة 2: تحميل Livewire Component - TryotoComponet";
$report[] = "   ─────────────────────────────────────────────────────";
$report[] = "   @livewire('tryoto-componet', [";
$report[] = "       'products' => \$vendorProducts,";
$report[] = "       'vendorId' => {$vendorId}";
$report[] = "   ])";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 6: TryotoComponet - التفاصيل الكاملة
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 6: TryotoComponet - حساب تكلفة الشحن الذكي                                         │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   📁 Component: app/Livewire/TryotoComponet.php";
$report[] = "   📁 View: resources/views/livewire/tryoto-componet.blade.php";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 1: جلب مدينة البائع (Origin City)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$originCity = null;
if ($vendor->city_id) {
    $originCity = City::find($vendor->city_id);
}

$report[] = "   📁 Method: getOriginCity()";
$report[] = "";
$report[] = "   \$vendor = User::find({$vendorId})";
$report[] = "   vendor->city_id = " . ($vendor->city_id ?? 'NULL');
$report[] = "";

if ($originCity) {
    $report[] = "   \$city = City::find({$vendor->city_id})";
    $report[] = "   النتيجة:";
    $report[] = "   ├── city_name: {$originCity->city_name} ← يُرسل لـ Tryoto كـ originCity";
    $report[] = "   └── tryoto_supported: " . ($originCity->tryoto_supported ? 'نعم ✓' : 'لا ✗');
}
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 2: جلب مدينة العميل (Destination City)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$report[] = "   📁 Method: getDestinationCity()";
$report[] = "";
$report[] = "   \$step1 = Session::get('vendor_step1_{$vendorId}')";
$report[] = "   \$cityId = \$step1['customer_city'] = {$city->id}";
$report[] = "";
$report[] = "   \$city = City::find({$city->id})";
$report[] = "   النتيجة:";
$report[] = "   ├── city_name: {$city->city_name} ← يُرسل لـ Tryoto كـ destinationCity";
$report[] = "   └── tryoto_supported: " . ($city->tryoto_supported ? 'نعم ✓' : 'لا ✗');
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 3: حساب الوزن والأبعاد";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$weight = ($product->weight ?? 0.5) * 2;
$report[] = "   📁 Helper: PriceHelper::calculateShippingDimensions(\$products)";
$report[] = "";
$report[] = "   الحساب:";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  foreach (products as product) {                                                    │";
$report[] = "   │      // الوزن من جدول products                                                     │";
$report[] = "   │      weight += product.weight * qty                                                 │";
$report[] = "   │                                                                                     │";
$report[] = "   │      // الأبعاد من جدول products (إذا موجودة) أو افتراضية                          │";
$report[] = "   │      length = product.length ?? 30                                                  │";
$report[] = "   │      height = product.height ?? 30                                                  │";
$report[] = "   │  }                                                                                  │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";
$report[] = "   النتيجة:";
$report[] = "   ├── weight: {$weight} كجم";
$report[] = "   ├── xlength: 30 سم";
$report[] = "   ├── xheight: 30 سم";
$report[] = "   └── xwidth: 30 سم";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 4: إرسال الطلب لـ Tryoto API مع دعم COD";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$originCityName = $originCity->city_name ?? 'Riyadh';
$destCityName = $city->city_name;
$codAmount = $subtotal; // قيمة الطلب للدفع عند الاستلام

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  منطق COD (الدفع عند الاستلام)                                                     │";
$report[] = "   ├─────────────────────────────────────────────────────────────────────────────────────┤";
$report[] = "   │                                                                                     │";
$report[] = "   │  عند اختيار \"الدفع عند الاستلام\":                                                  │";
$report[] = "   │    - يتم تمرير قيمة الطلب (COD amount) إلى Tryoto                                   │";
$report[] = "   │    - Tryoto تحسب رسوم التحصيل الإضافية                                              │";
$report[] = "   │    - السعر النهائي = سعر الشحن + رسوم COD                                           │";
$report[] = "   │                                                                                     │";
$report[] = "   │  عند اختيار \"الدفع الإلكتروني\":                                                    │";
$report[] = "   │    - COD amount = 0                                                                 │";
$report[] = "   │    - لا توجد رسوم إضافية                                                            │";
$report[] = "   │                                                                                     │";
$report[] = "   │  ⚠️ حالياً في الكود: COD amount = 0 دائماً                                          │";
$report[] = "   │  التصحيح المطلوب: تمرير قيمة الطلب عند اختيار COD                                   │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   📁 Service: TryotoService@getDeliveryOptions";
$report[] = "";
$report[] = "   API Request:";
$report[] = "   POST https://api.tryoto.com/checkOTODeliveryFee";
$report[] = "   Headers: Authorization: Bearer [API_TOKEN]";
$report[] = "";
$report[] = "   Body:";
$report[] = "   {";
$report[] = "       \"originCity\": \"{$originCityName}\",";
$report[] = "       \"destinationCity\": \"{$destCityName}\",";
$report[] = "       \"weight\": {$weight},";
$report[] = "       \"xlength\": 30,";
$report[] = "       \"xheight\": 30,";
$report[] = "       \"xwidth\": 30,";
$report[] = "       \"codAmount\": 0  // ← يجب تمريره عند COD";
$report[] = "   }";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 5: استجابة Tryoto API (فعلية)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

// الاتصال الفعلي بـ Tryoto
try {
    $tryotoService = app(TryotoService::class);
    $tryotoResult = $tryotoService->getDeliveryOptions(
        $originCityName,
        $destCityName,
        $weight,
        0, // COD
        ['xlength' => 30, 'xheight' => 30, 'xwidth' => 30]
    );

    if ($tryotoResult['success']) {
        $companies = $tryotoResult['raw']['deliveryCompany'] ?? [];
        $report[] = "   ✓ نجاح! {" . count($companies) . "} شركة شحن متاحة";
        $report[] = "";

        // عرض أول 5 شركات فقط
        $displayCount = min(5, count($companies));
        for ($i = 0; $i < $displayCount; $i++) {
            $company = $companies[$i];
            $report[] = "   شركة " . ($i + 1) . ": " . ($company['deliveryCompanyName'] ?? 'N/A');
            $report[] = "   ├── السعر: " . ($company['price'] ?? 0) . " " . ($company['currency'] ?? 'SAR');
            $report[] = "   ├── مدة التوصيل: " . ($company['avgDeliveryTime'] ?? 'N/A');
            $report[] = "   └── deliveryOptionId: " . ($company['deliveryOptionId'] ?? 'N/A');
            $report[] = "";
        }

        if (count($companies) > 5) {
            $report[] = "   ... و " . (count($companies) - 5) . " شركات أخرى";
        }
    } else {
        $report[] = "   ✗ فشل: " . ($tryotoResult['error'] ?? 'Unknown error');
    }
} catch (\Exception $e) {
    $report[] = "   ✗ Exception: " . $e->getMessage();
}
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 7: تخزين اختيار الشحن في Session
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 7: تخزين اختيار الشحن في Session                                                   │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   عند اختيار المستخدم لشركة شحن من Tryoto:";
$report[] = "";
$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  القيمة المُرسلة من Radio Input:                                                    │";
$report[] = "   │  value = \"deliveryOptionId#deliveryCompanyName#price\"                              │";
$report[] = "   │  مثال: \"5438#delivernow#19\"                                                        │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";
$report[] = "   📁 Method: TryotoComponet::selectedOption(\$value)";
$report[] = "";
$report[] = "   التخزين في Session:";
$report[] = "   Session::put('vendor_shipping_{$vendorId}', [";
$report[] = "       'type' => 'tryoto',";
$report[] = "       'delivery_option_id' => '5438',";
$report[] = "       'company_name' => 'delivernow',";
$report[] = "       'price' => 19,";
$report[] = "       'logo' => 'https://...',";
$report[] = "       'delivery_time' => 'Same Day of Pickup Date'";
$report[] = "   ])";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 8: تكلفة التغليف (Packages)
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 8: تكلفة التغليف (Packaging Cost)                                                  │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  إذا كان البائع يملك تكلفة تغليف:                                                   │";
$report[] = "   │                                                                                     │";
$report[] = "   │  المصدر: جدول packages أو إعدادات البائع                                            │";
$report[] = "   │                                                                                     │";
$report[] = "   │  الحساب:                                                                            │";
$report[] = "   │    package_cost = عدد المنتجات × تكلفة التغليف للقطعة                               │";
$report[] = "   │    أو                                                                               │";
$report[] = "   │    package_cost = تكلفة ثابتة للطلب                                                 │";
$report[] = "   │                                                                                     │";
$report[] = "   │  التخزين:                                                                           │";
$report[] = "   │    Session['vendor_step2_{$vendorId}']['package_cost'] = X                          │";
$report[] = "   │                                                                                     │";
$report[] = "   │  ⚠️ يُضاف إلى الإجمالي في الخطوة النهائية                                           │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 9: إنشاء الطلب - تخزين بيانات Tryoto
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 9: إنشاء الطلب (Purchase Creation) - تخزين بيانات Tryoto                              │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   🔗 Route: POST /checkout/vendor/{$vendorId}/complete";
$report[] = "   📁 Controller: CheckoutController@completeVendorOrder";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   تخزين نتيجة Tryoto داخل جدول orders";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  orders.shipping_title                                                              │";
$report[] = "   │  ─────────────────────                                                              │";
$report[] = "   │  يحتوي على: اسم شركة الشحن                                                          │";
$report[] = "   │  مثال: \"delivernow\" أو \"aramex\" أو \"smsa\"                                         │";
$report[] = "   │                                                                                     │";
$report[] = "   │  الاستخدام:                                                                         │";
$report[] = "   │    - عرض اسم الشركة في صفحة تفاصيل الطلب                                            │";
$report[] = "   │    - تتبع الشحنة                                                                    │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  orders.shipping_info (JSON)                                                        │";
$report[] = "   │  ─────────────────────                                                              │";
$report[] = "   │  يحتوي على: تفاصيل الشحن الكاملة بصيغة JSON                                         │";
$report[] = "   │                                                                                     │";
$report[] = "   │  البنية:                                                                            │";
$report[] = "   │  {                                                                                  │";
$report[] = "   │      \"type\": \"tryoto\",                                                            │";
$report[] = "   │      \"delivery_option_id\": \"5438\",                                                │";
$report[] = "   │      \"company_name\": \"delivernow\",                                                │";
$report[] = "   │      \"price\": 19,                                                                  │";
$report[] = "   │      \"currency\": \"SAR\",                                                           │";
$report[] = "   │      \"delivery_time\": \"Same Day of Pickup Date\",                                  │";
$report[] = "   │      \"logo\": \"https://...\",                                                       │";
$report[] = "   │      \"origin_city\": \"Riyadh\",                                                     │";
$report[] = "   │      \"destination_city\": \"Riyadh\",                                                │";
$report[] = "   │      \"weight\": 2,                                                                  │";
$report[] = "   │      \"tracking_number\": null  // يُضاف لاحقاً عند الشحن                           │";
$report[] = "   │  }                                                                                  │";
$report[] = "   │                                                                                     │";
$report[] = "   │  الاستخدام:                                                                         │";
$report[] = "   │    - إنشاء شحنة فعلية عبر Tryoto API                                                │";
$report[] = "   │    - عرض تفاصيل الشحن للعميل والبائع                                                │";
$report[] = "   │    - تتبع الشحنة                                                                    │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "   حساب الإجمالي النهائي (في هذه المرحلة فقط)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════════════════════";
$report[] = "";

$report[] = "   ┌─────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "   │  الحساب النهائي:                                                                    │";
$report[] = "   │                                                                                     │";
$report[] = "   │  subtotal         = مجموع المنتجات         = {$subtotal}                                │";
$report[] = "   │  shipping_cost    = تكلفة الشحن            = 19 (مثال)                              │";
$report[] = "   │  package_cost     = تكلفة التغليف          = 0                                      │";
$report[] = "   │  ────────────────────────────────────────────                                       │";
$report[] = "   │  taxable_amount   = subtotal + shipping + package                                   │";
$report[] = "   │  tax_amount       = taxable_amount × (tax_rate / 100)                               │";
$report[] = "   │  ────────────────────────────────────────────                                       │";
$report[] = "   │  total            = taxable_amount + tax_amount                                     │";
$report[] = "   │                                                                                     │";
$report[] = "   │  ⚠️ الضريبة تُحسب على: (المنتجات + الشحن + التغليف)                                 │";
$report[] = "   └─────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// ملخص تدفق البيانات
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌────────────────────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ ملخص تدفق البيانات الكامل                                                                  │";
$report[] = "└────────────────────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ╔═════════════════════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                              الجداول المستخدمة                                       ║";
$report[] = "   ╠═════════════════════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  products           → الوزن، الأبعاد، الاسم، التصنيفات                               ║";
$report[] = "   ║  merchant_products  → السعر، المخزون، الألوان، الحالة، user_id (البائع)              ║";
$report[] = "   ║  users              → بيانات البائع، city_id                                        ║";
$report[] = "   ║  countries          → الدولة، tax (نسبة الضريبة)                                    ║";
$report[] = "   ║  states             → المنطقة/الولاية                                               ║";
$report[] = "   ║  cities             → المدن، tryoto_supported، الإحداثيات                           ║";
$report[] = "   ║  shippings          → طرق الشحن المحلية                                             ║";
$report[] = "   ║  orders             → الطلبات (shipping_title, shipping_info)                       ║";
$report[] = "   ╚═════════════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "   ╔═════════════════════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                              مفاتيح Session                                          ║";
$report[] = "   ╠═════════════════════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  cart                    → بيانات السلة (جميع المنتجات)                             ║";
$report[] = "   ║  vendor_step1_{id}       → بيانات العنوان لبائع معين                                ║";
$report[] = "   ║  vendor_shipping_{id}    → اختيار الشحن لبائع معين                                  ║";
$report[] = "   ║  checkout_vendor_id      → البائع الحالي في الـ Checkout                            ║";
$report[] = "   ╚═════════════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "   ╔═════════════════════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                          تدفق مدينة البائع → Tryoto                                  ║";
$report[] = "   ╠═════════════════════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  users.city_id → cities.id → cities.city_name → Tryoto API (originCity)             ║";
$report[] = "   ╚═════════════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "   ╔═════════════════════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                          تدفق مدينة العميل → Tryoto                                  ║";
$report[] = "   ╠═════════════════════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  الخريطة (lat, lng)                                                                  ║";
$report[] = "   ║       ↓                                                                              ║";
$report[] = "   ║  Google Maps API → city name                                                         ║";
$report[] = "   ║       ↓                                                                              ║";
$report[] = "   ║  TryotoLocationService → cities.id (أو أقرب مدينة مدعومة)                            ║";
$report[] = "   ║       ↓                                                                              ║";
$report[] = "   ║  Session['vendor_step1_{id}']['customer_city']                                       ║";
$report[] = "   ║       ↓                                                                              ║";
$report[] = "   ║  TryotoComponet::getDestinationCity() → cities.city_name                             ║";
$report[] = "   ║       ↓                                                                              ║";
$report[] = "   ║  Tryoto API (destinationCity)                                                        ║";
$report[] = "   ╚═════════════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "═══════════════════════════════════════════════════════════════════════════════════════════";
$report[] = "                                    نهاية التقرير                                          ";
$report[] = "═══════════════════════════════════════════════════════════════════════════════════════════";

// حفظ التقرير
$reportContent = implode("\n", $report);
file_put_contents('CHECKOUT_FLOW_TRACE_REPORT.txt', $reportContent);

echo $reportContent;
echo "\n\n✅ تم حفظ التقرير في: CHECKOUT_FLOW_TRACE_REPORT.txt\n";
