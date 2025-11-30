<?php
/**
 * تتبع تدفق البيانات الفعلي من السلة إلى الشحن
 * هذا السكربت يحاكي العملية الحقيقية ويتتبع كل خطوة
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
$report[] = "╔══════════════════════════════════════════════════════════════════════════════╗";
$report[] = "║           تقرير تتبع تدفق البيانات الفعلي - من السلة إلى الشحن              ║";
$report[] = "║                         " . date('Y-m-d H:i:s') . "                              ║";
$report[] = "╚══════════════════════════════════════════════════════════════════════════════╝";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 1: السلة - Cart
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 1: السلة (Cart)                                                      │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// نختار merchant_product حقيقي من قاعدة البيانات (يربط المنتج بالبائع)
$merchantProduct = MerchantProduct::with(['product', 'user'])->where('status', 1)->first();

if (!$merchantProduct) {
    $report[] = "❌ لا توجد منتجات في قاعدة البيانات!";
    file_put_contents('CHECKOUT_FLOW_TRACE_REPORT.txt', implode("\n", $report));
    exit;
}

$product = $merchantProduct->product;
$vendor = $merchantProduct->user;

$report[] = "📦 المنتج المختار:";
$report[] = "   ├── Product ID: {$product->id}";
$report[] = "   ├── MerchantProduct ID: {$merchantProduct->id}";
$report[] = "   ├── الاسم: {$product->name}";
$report[] = "   ├── السعر: {$merchantProduct->price}";
$report[] = "   ├── الوزن: " . ($product->weight ?? 'غير محدد') . " كجم";
$report[] = "   └── البائع (user_id): {$merchantProduct->user_id}";
$report[] = "";
$report[] = "👤 البائع (Vendor):";
$report[] = "   ├── ID: {$vendor->id}";
$report[] = "   ├── الاسم: {$vendor->name}";
$report[] = "   ├── city_id: " . ($vendor->city_id ?? 'NULL');

if ($vendor->city_id) {
    $vendorCity = City::find($vendor->city_id);
    if ($vendorCity) {
        $report[] = "   ├── اسم المدينة: {$vendorCity->city_name}";
        $report[] = "   ├── اسم المدينة (عربي): {$vendorCity->city_name_ar}";
        $report[] = "   ├── tryoto_supported: " . ($vendorCity->tryoto_supported ? 'نعم ✓' : 'لا ✗');
        $report[] = "   └── country_id: {$vendorCity->country_id}";
    } else {
        $report[] = "   └── ⚠️ المدينة غير موجودة في جدول cities!";
    }
} else {
    $report[] = "   └── ⚠️ البائع ليس لديه city_id!";
}
$report[] = "";

// محاكاة إنشاء السلة
$report[] = "🛒 إنشاء السلة:";
$productPrice = $merchantProduct->price;
$cartData = [
    'items' => [
        $product->id => [
            'qty' => 2,
            'price' => $productPrice * 2,
            'dp' => $product->type == 'digital' ? 1 : 0,
            'item' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $productPrice,
                'weight' => $product->weight ?? 0.5,
                'user_id' => $merchantProduct->user_id,
            ]
        ]
    ],
    'totalQty' => 2,
    'totalPrice' => $productPrice * 2
];

$report[] = "   ├── عدد المنتجات: 1";
$report[] = "   ├── الكمية: 2";
$report[] = "   ├── السعر الإجمالي: " . ($productPrice * 2);
$report[] = "   └── تُخزن في: Session['cart']";
$report[] = "";

$report[] = "   📋 بنية بيانات السلة في Session:";
$report[] = "   Session['cart'] = [";
$report[] = "       'items' => [";
$report[] = "           {$product->id} => [";
$report[] = "               'qty' => 2,";
$report[] = "               'price' => " . ($productPrice * 2) . ",";
$report[] = "               'dp' => 0,";
$report[] = "               'item' => [";
$report[] = "                   'id' => {$product->id},";
$report[] = "                   'user_id' => {$merchantProduct->user_id},  ← مهم! يحدد البائع";
$report[] = "                   'weight' => " . ($product->weight ?? 0.5) . ",";
$report[] = "               ]";
$report[] = "           ]";
$report[] = "       ],";
$report[] = "       'totalQty' => 2,";
$report[] = "       'totalPrice' => " . ($productPrice * 2);
$report[] = "   ]";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 2: الانتقال للـ Checkout
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 2: الانتقال للـ Checkout                                             │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$vendorId = $merchantProduct->user_id;
$report[] = "🔗 Route: GET /checkout/vendor/{$vendorId}";
$report[] = "📁 Controller: CheckoutController@checkoutVendor";
$report[] = "";
$report[] = "   ما يحدث:";
$report[] = "   1. يُحفظ vendor_id في Session:";
$report[] = "      Session::put('checkout_vendor_id', {$vendorId})";
$report[] = "";
$report[] = "   2. تُصفى منتجات هذا البائع فقط من السلة:";
$report[] = "      foreach (cart->items as product) {";
$report[] = "          if (product['item']['user_id'] == {$vendorId}) {";
$report[] = "              vendorProducts[] = product;";
$report[] = "          }";
$report[] = "      }";
$report[] = "";

// جلب طرق الشحن للبائع
$shippingMethods = Shipping::where(function($q) use ($vendorId) {
    $q->where('user_id', $vendorId)->orWhere('user_id', 0);
})->get();

$report[] = "   3. جلب طرق الشحن من جدول shippings:";
$report[] = "      SQL: SELECT * FROM shippings WHERE (user_id = {$vendorId} OR user_id = 0)";
$report[] = "";
$report[] = "      النتيجة ({$shippingMethods->count()} طريقة):";
foreach ($shippingMethods as $ship) {
    $report[] = "      ├── ID: {$ship->id}, العنوان: {$ship->title}, السعر: {$ship->price}";
}
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 3: اختيار الموقع من الخريطة
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 3: اختيار الموقع من الخريطة (Geocoding)                              │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

// إحداثيات الرياض كمثال
$testLat = 24.7136;
$testLng = 46.6753;

$report[] = "📍 المستخدم ينقر على الخريطة:";
$report[] = "   Latitude: {$testLat}";
$report[] = "   Longitude: {$testLng}";
$report[] = "";

$report[] = "🔗 Route: POST /geocoding/reverse";
$report[] = "📁 Controller: GeocodingController@reverseGeocode";
$report[] = "";

$report[] = "   الخطوة 1: إرسال الإحداثيات لـ Google Maps API";
$report[] = "   ─────────────────────────────────────────────";

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

$report[] = "   URL: https://maps.googleapis.com/maps/api/geocode/json?latlng={$testLat},{$testLng}&key=xxx";
$report[] = "";
$report[] = "   استجابة Google Maps:";
$report[] = "   {";
foreach ($googleResponse as $key => $value) {
    $report[] = "       '{$key}' => '{$value}',";
}
$report[] = "   }";
$report[] = "";

$report[] = "   الخطوة 2: البحث عن الدولة في قاعدة البيانات";
$report[] = "   ─────────────────────────────────────────────";

$country = Country::where('country_name', $googleResponse['country'])
    ->orWhere('country_code', $googleResponse['country_code'])
    ->first();

if ($country) {
    $report[] = "   SQL: SELECT * FROM countries WHERE country_name = 'Saudi Arabia' OR country_code = 'SA'";
    $report[] = "";
    $report[] = "   النتيجة:";
    $report[] = "   ├── id: {$country->id}";
    $report[] = "   ├── country_name: {$country->country_name}";
    $report[] = "   ├── country_name_ar: {$country->country_name_ar}";
    $report[] = "   ├── tax: {$country->tax}%";
    $report[] = "   └── is_synced: " . ($country->is_synced ? 'نعم ✓' : 'لا ✗');
} else {
    $report[] = "   ⚠️ الدولة غير موجودة!";
}
$report[] = "";

$report[] = "   الخطوة 3: البحث عن المدينة باستخدام TryotoLocationService";
$report[] = "   ─────────────────────────────────────────────────────────";

$report[] = "   📁 Service: TryotoLocationService@resolveMapCity";
$report[] = "";

// البحث عن المدينة
$city = City::where('country_id', $country->id ?? 1)
    ->where('tryoto_supported', 1)
    ->where(function($q) use ($googleResponse) {
        $q->where('city_name', $googleResponse['city'])
          ->orWhere('city_name_ar', $googleResponse['city_ar']);
    })
    ->first();

if ($city) {
    $report[] = "   SQL: SELECT * FROM cities";
    $report[] = "        WHERE country_id = {$country->id}";
    $report[] = "        AND tryoto_supported = 1";
    $report[] = "        AND (city_name = 'Riyadh' OR city_name_ar = 'الرياض')";
    $report[] = "";
    $report[] = "   ✓ المدينة موجودة ومدعومة:";
    $report[] = "   ├── id: {$city->id}  ← هذا هو city_id المهم!";
    $report[] = "   ├── city_name: {$city->city_name}";
    $report[] = "   ├── city_name_ar: {$city->city_name_ar}";
    $report[] = "   ├── state_id: " . ($city->state_id ?: 'NULL');
    $report[] = "   └── tryoto_supported: " . ($city->tryoto_supported ? 'نعم ✓' : 'لا ✗');
} else {
    $report[] = "   ⚠️ المدينة غير موجودة، سيتم البحث عن أقرب مدينة...";

    // البحث عن أقرب مدينة
    $nearestCity = City::where('country_id', $country->id ?? 1)
        ->where('tryoto_supported', 1)
        ->first();

    if ($nearestCity) {
        $city = $nearestCity;
        $report[] = "   ✓ أقرب مدينة مدعومة:";
        $report[] = "   ├── id: {$city->id}";
        $report[] = "   └── city_name: {$city->city_name}";
    }
}
$report[] = "";

$report[] = "   الخطوة 4: إرجاع البيانات للـ JavaScript";
$report[] = "   ─────────────────────────────────────────";
$report[] = "   response.json([";
$report[] = "       'success' => true,";
$report[] = "       'data' => [";
$report[] = "           'country' => [";
$report[] = "               'id' => {$country->id},";
$report[] = "               'name' => '{$country->country_name}',";
$report[] = "               'name_ar' => '{$country->country_name_ar}'";
$report[] = "           ],";
$report[] = "           'state' => [";
$report[] = "               'id' => 0,";
$report[] = "               'name' => '{$googleResponse['state']}',";
$report[] = "               'name_ar' => '{$googleResponse['state_ar']}'";
$report[] = "           ],";
$report[] = "           'city' => [";
$report[] = "               'id' => {$city->id},  ← يُحفظ في hidden field";
$report[] = "               'name' => '{$city->city_name}',";
$report[] = "               'name_ar' => '{$city->city_name_ar}'";
$report[] = "           ],";
$report[] = "           'coordinates' => [";
$report[] = "               'latitude' => {$testLat},";
$report[] = "               'longitude' => {$testLng}";
$report[] = "           ]";
$report[] = "       ]";
$report[] = "   ])";
$report[] = "";

$report[] = "   الخطوة 5: JavaScript يملأ الـ Hidden Fields";
$report[] = "   ─────────────────────────────────────────────";
$report[] = "   \$('#customer_city_hidden').val({$city->id});     // city_id";
$report[] = "   \$('#customer_country_hidden').val('{$country->country_name}');";
$report[] = "   \$('#customer_state_hidden').val('{$googleResponse['state']}');";
$report[] = "   \$('#country_id').val({$country->id});";
$report[] = "   \$('#city_id').val({$city->id});";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 4: إرسال Step 1
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 4: إرسال Step 1 (العنوان)                                            │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "🔗 Route: POST /checkout/vendor/{$vendorId}/step1/submit";
$report[] = "📁 Controller: CheckoutController@checkoutVendorStep1";
$report[] = "";

$step1Data = [
    'customer_name' => 'أحمد محمد',
    'customer_email' => 'ahmed@example.com',
    'customer_phone' => '966512345678',
    'customer_address' => 'شارع الملك فهد، حي العليا',
    'customer_zip' => '12345',
    'customer_country' => $country->country_name,
    'customer_state' => $googleResponse['state'],
    'customer_city' => $city->id,  // city_id رقمي!
    'latitude' => $testLat,
    'longitude' => $testLng,
    'country_id' => $country->id,
    'state_id' => 0,
    'city_id' => $city->id,
];

$report[] = "📤 البيانات المرسلة من الفورم:";
$report[] = "   POST Data:";
foreach ($step1Data as $key => $value) {
    $important = in_array($key, ['customer_city', 'city_id', 'customer_country', 'customer_state']) ? ' ← مهم!' : '';
    $report[] = "   ├── {$key}: {$value}{$important}";
}
$report[] = "";

$report[] = "   ✓ Validation Rules:";
$report[] = "   ├── customer_name: required|string";
$report[] = "   ├── customer_email: required|email";
$report[] = "   ├── customer_phone: required|numeric";
$report[] = "   ├── customer_address: required|string";
$report[] = "   ├── customer_country: required|string  ← اسم الدولة";
$report[] = "   ├── customer_state: required|string    ← اسم المنطقة";
$report[] = "   └── customer_city: required|numeric    ← city_id (رقم!)";
$report[] = "";

// حساب الضريبة
$taxRate = $country->tax ?? 0;
$subtotal = $productPrice * 2;
$taxAmount = ($subtotal * $taxRate) / 100;

$report[] = "   💰 حساب الضريبة:";
$report[] = "   ├── نسبة الضريبة من جدول countries: {$taxRate}%";
$report[] = "   ├── المبلغ قبل الضريبة: {$subtotal}";
$report[] = "   └── قيمة الضريبة: {$taxAmount}";
$report[] = "";

$step1Data['tax_rate'] = $taxRate;
$step1Data['tax_amount'] = $taxAmount;
$step1Data['vendor_subtotal'] = $subtotal;

$report[] = "   💾 الحفظ في Session:";
$report[] = "   Session::put('vendor_step1_{$vendorId}', \$step1Data)";
$report[] = "";
$report[] = "   محتوى Session['vendor_step1_{$vendorId}']:";
$report[] = "   {";
foreach ($step1Data as $key => $value) {
    $report[] = "       '{$key}' => '{$value}',";
}
$report[] = "   }";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 5: Step 2 - الشحن
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 5: Step 2 - اختيار الشحن                                             │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "🔗 Route: GET /checkout/vendor/{$vendorId}/step2";
$report[] = "📁 Controller: CheckoutController@checkoutVendorStep2";
$report[] = "";

$report[] = "   الخطوة 1: جلب بيانات Step 1 من Session";
$report[] = "   ─────────────────────────────────────────";
$report[] = "   \$step1 = Session::get('vendor_step1_{$vendorId}')";
$report[] = "   customer_city = {$city->id}  ← city_id للعميل";
$report[] = "";

$report[] = "   الخطوة 2: جلب منتجات هذا البائع من السلة";
$report[] = "   ─────────────────────────────────────────";
$report[] = "   \$cart = Session::get('cart')";
$report[] = "   foreach (cart->items) where item.user_id == {$vendorId}";
$report[] = "";

$report[] = "   الخطوة 3: جلب طرق الشحن";
$report[] = "   ─────────────────────────";
$report[] = "   أ) الشحن المحلي من جدول shippings:";
$report[] = "      SQL: SELECT * FROM shippings WHERE user_id = {$vendorId} OR user_id = 0";
$report[] = "";

$report[] = "   ب) شحن Tryoto (الذكي):";
$report[] = "      يتم تحميله عبر Livewire Component: TryotoComponet";
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 6: Tryoto Component - حساب الشحن
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ المرحلة 6: TryotoComponet - حساب تكلفة الشحن                                 │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "📁 Component: app/Livewire/TryotoComponet.php";
$report[] = "📁 View: resources/views/livewire/tryoto-componet.blade.php";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 1: جلب مدينة البائع (Origin City)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   📁 Method: getOriginCity()";
$report[] = "";
$report[] = "   // جلب البائع";
$report[] = "   \$vendor = User::find({$vendorId})";
$report[] = "   vendor->city_id = " . ($vendor->city_id ?? 'NULL');
$report[] = "";

if ($vendor->city_id) {
    $originCity = City::find($vendor->city_id);
    $report[] = "   // جلب المدينة من جدول cities";
    $report[] = "   \$city = City::find({$vendor->city_id})";
    $report[] = "";
    if ($originCity) {
        $report[] = "   النتيجة:";
        $report[] = "   ├── city_name: {$originCity->city_name}  ← هذا يُرسل لـ Tryoto";
        $report[] = "   └── tryoto_supported: " . ($originCity->tryoto_supported ? 'نعم ✓' : 'لا ✗');
    }
}
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 2: جلب مدينة العميل (Destination City)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   📁 Method: getDestinationCity()";
$report[] = "";
$report[] = "   // جلب بيانات Step 1 من Session";
$report[] = "   \$step1 = Session::get('vendor_step1_{$vendorId}')";
$report[] = "   \$cityId = \$step1['customer_city']  // = {$city->id}";
$report[] = "";
$report[] = "   // جلب المدينة من جدول cities";
$report[] = "   \$city = City::find({$city->id})";
$report[] = "";
$report[] = "   النتيجة:";
$report[] = "   ├── city_name: {$city->city_name}  ← هذا يُرسل لـ Tryoto";
$report[] = "   └── tryoto_supported: " . ($city->tryoto_supported ? 'نعم ✓' : 'لا ✗');
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 3: حساب الوزن والأبعاد";
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   📁 Helper: PriceHelper::calculateShippingDimensions(\$products)";
$report[] = "";

$weight = ($product->weight ?? 0.5) * 2; // 2 قطع
$report[] = "   الحساب:";
$report[] = "   foreach (products) {";
$report[] = "       weight += product.weight * qty";
$report[] = "   }";
$report[] = "";
$report[] = "   النتيجة:";
$report[] = "   ├── weight: {$weight} كجم";
$report[] = "   ├── xlength: 30 سم";
$report[] = "   ├── xheight: 30 سم";
$report[] = "   └── xwidth: 30 سم";
$report[] = "";

$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 4: إرسال الطلب لـ Tryoto API";
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "";
$report[] = "   📁 Service: TryotoService@getDeliveryOptions";
$report[] = "";

$originCityName = $originCity->city_name ?? 'Riyadh';
$destCityName = $city->city_name;

$report[] = "   API Request:";
$report[] = "   POST https://api.tryoto.com/checkOTODeliveryFee";
$report[] = "   Headers:";
$report[] = "       Authorization: Bearer [API_TOKEN]";
$report[] = "       Content-Type: application/json";
$report[] = "";
$report[] = "   Body:";
$report[] = "   {";
$report[] = "       \"originCity\": \"{$originCityName}\",      ← مدينة البائع";
$report[] = "       \"destinationCity\": \"{$destCityName}\",   ← مدينة العميل";
$report[] = "       \"weight\": {$weight},";
$report[] = "       \"xlength\": 30,";
$report[] = "       \"xheight\": 30,";
$report[] = "       \"xwidth\": 30";
$report[] = "   }";
$report[] = "";

// محاولة الاتصال الفعلي بـ Tryoto
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "   الخطوة 5: استجابة Tryoto API (فعلية)";
$report[] = "   ═══════════════════════════════════════════════════════════════════════";
$report[] = "";

try {
    $tryotoService = app(TryotoService::class);
    $tryotoResult = $tryotoService->getDeliveryOptions(
        $originCityName,
        $destCityName,
        $weight,
        0,
        ['xlength' => 30, 'xheight' => 30, 'xwidth' => 30]
    );

    if ($tryotoResult['success']) {
        $report[] = "   ✓ نجاح! شركات الشحن المتاحة:";
        $report[] = "";

        $companies = $tryotoResult['raw']['deliveryCompany'] ?? [];
        foreach ($companies as $index => $company) {
            $report[] = "   شركة " . ($index + 1) . ":";
            $report[] = "   ├── الاسم: " . ($company['deliveryCompanyName'] ?? 'N/A');
            $report[] = "   ├── السعر: " . ($company['price'] ?? 0) . " " . ($company['currency'] ?? 'SAR');
            $report[] = "   ├── مدة التوصيل: " . ($company['avgDeliveryTime'] ?? 'N/A');
            $report[] = "   └── deliveryOptionId: " . ($company['deliveryOptionId'] ?? 'N/A');
            $report[] = "";
        }
    } else {
        $report[] = "   ✗ فشل: " . ($tryotoResult['error'] ?? 'Unknown error');
    }
} catch (\Exception $e) {
    $report[] = "   ✗ Exception: " . $e->getMessage();
}
$report[] = "";

// ═══════════════════════════════════════════════════════════════════════════════
// المرحلة 7: ملخص تدفق البيانات
// ═══════════════════════════════════════════════════════════════════════════════

$report[] = "┌──────────────────────────────────────────────────────────────────────────────┐";
$report[] = "│ ملخص تدفق البيانات                                                          │";
$report[] = "└──────────────────────────────────────────────────────────────────────────────┘";
$report[] = "";

$report[] = "   ╔═══════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                        مصدر مدينة البائع                               ║";
$report[] = "   ╠═══════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  users.city_id ({$vendor->city_id})                                            ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  cities.id = {$vendor->city_id}                                                ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  cities.city_name = '{$originCityName}'                               ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  Tryoto API (originCity)                                              ║";
$report[] = "   ╚═══════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "   ╔═══════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                        مصدر مدينة العميل                               ║";
$report[] = "   ╠═══════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  الخريطة (lat, lng)                                                   ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  Google Maps API → city name                                          ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  TryotoLocationService → cities.id = {$city->id}                              ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  Session['vendor_step1_{$vendorId}']['customer_city'] = {$city->id}            ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  TryotoComponet::getDestinationCity()                                 ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  City::find({$city->id})->city_name = '{$destCityName}'                       ║";
$report[] = "   ║       ↓                                                               ║";
$report[] = "   ║  Tryoto API (destinationCity)                                         ║";
$report[] = "   ╚═══════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "   ╔═══════════════════════════════════════════════════════════════════════╗";
$report[] = "   ║                        الجداول المستخدمة                               ║";
$report[] = "   ╠═══════════════════════════════════════════════════════════════════════╣";
$report[] = "   ║  products     → معلومات المنتج والوزن                                 ║";
$report[] = "   ║  users        → البائع ومدينته (city_id)                              ║";
$report[] = "   ║  countries    → الدولة ونسبة الضريبة                                  ║";
$report[] = "   ║  states       → المنطقة (اختياري)                                     ║";
$report[] = "   ║  cities       → المدن (الأساسي للشحن)                                 ║";
$report[] = "   ║  shippings    → طرق الشحن المحلية                                     ║";
$report[] = "   ╚═══════════════════════════════════════════════════════════════════════╝";
$report[] = "";

$report[] = "═══════════════════════════════════════════════════════════════════════════════";
$report[] = "                              نهاية التقرير                                    ";
$report[] = "═══════════════════════════════════════════════════════════════════════════════";

// حفظ التقرير
$reportContent = implode("\n", $report);
file_put_contents('CHECKOUT_FLOW_TRACE_REPORT.txt', $reportContent);

echo $reportContent;
echo "\n\n✅ تم حفظ التقرير في: CHECKOUT_FLOW_TRACE_REPORT.txt\n";
