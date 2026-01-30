# 📊 تقرير المراجعة الشاملة للديون التقنية

**التاريخ:** 30 يناير 2026  
**المشروع:** Muaadh Parts  
**Branch:** withoutLegacy

---

## 🎯 ملخص تنفيذي

تم إجراء مراجعة شاملة للكود المصدري لتحديد الديون التقنية المتبقية بعد إعادة الهيكلة المعمارية الأخيرة. تم فحص **74 Model** و **136 Controller** وجميع الـ Views.

### 📈 النتائج الإجمالية

| الفئة | العدد المكتشف | الأولوية |
|:---|:---:|:---:|
| Business Logic في Models | 3 | 🔴 عالية |
| Queries مباشرة في Controllers | 50+ | 🟡 متوسطة |
| Service calls في Views | 1 | 🟡 متوسطة |
| Static methods في Models | 99 | 🟢 منخفضة |

---

## 1️⃣ Business Logic في Models

### 🔴 **المشكلة الرئيسية: MerchantItem Model**

**الملف:** `app/Domain/Merchant/Models/MerchantItem.php`

**الدوال التي تحتوي على Business Logic:**

#### 1. `merchantSizePrice()` (السطور 100-119)
```php
public function merchantSizePrice(): float
{
    $base = (float) ($this->price ?? 0);
    if ($base <= 0) {
        return 0.0;
    }
    $final = $base;
    $commission = $this->getMerchantCommission();
    if ($commission && $commission->is_active) {
        $fixed = (float) ($commission->fixed_commission ?? 0);
        $percent = (float) ($commission->percentage_commission ?? 0);
        if ($fixed > 0) {
            $final += $fixed;
        }
        if ($percent > 0) {
            $final += $base * ($percent / 100);
        }
    }
    return round($final, 2);
}
```

**المشكلة:**
- حساب السعر مع العمولة موجود في Model
- هذا منطق عمل (Business Logic) يجب أن يكون في Service

**الحل المقترح:**
- نقل هذا المنطق إلى `PriceFormatterService::calculateFinalPriceWithCommission()`

---

#### 2. `offPercentage()` (السطور 124-132)
```php
public function offPercentage(): float
{
    $current = (float) ($this->price ?? 0);
    $previous = (float) ($this->previous_price ?? 0);
    if ($previous <= 0 || $current >= $previous) {
        return 0.0;
    }
    return round((($previous - $current) / $previous) * 100, 2);
}
```

**المشكلة:**
- حساب نسبة الخصم موجود في Model
- هذا منطق عمل يجب أن يكون في Service

**الحل المقترح:**
- نقل هذا المنطق إلى `PriceFormatterService::calculateDiscountPercentage()`

---

#### 3. `getMerchantCommission()` (السطور 137-140)
```php
public function getMerchantCommission()
{
    return $this->user?->merchantCommission;
}
```

**المشكلة:**
- هذا accessor بسيط، لكن يُفضل استخدام relationship مباشرة

**الحل المقترح:**
- حذف هذه الدالة واستخدام `$merchantItem->user->merchantCommission` مباشرة

---

## 2️⃣ Queries مباشرة في Controllers

### 🟡 **المشكلة: Controllers تحتوي على Database Queries**

تم اكتشاف **50+ استخدام** لـ queries مباشرة في Controllers، مما ينتهك مبدأ **Separation of Concerns**.

#### أمثلة:

**1. FrontendController (السطور 68-69, 126-127, 232-233, 265-292)**
```php
$affilate_user = DB::table('users')
    ->where('affilate_code', '=', $request->reff)
    ->first();

$catalogItems = CatalogItem::where(function($query) use ($search, $slug) {
    $query->where('name', 'like', '%' . $search . '%')
          ->orWhere('slug', 'like', '%' . $slug . '%');
})->get();

$subs = MailingList::where('email', '=', $request->email)->first();

foreach (DB::table('users')->where('is_merchant', '=', 2)->get() as $user) {
    // Business logic here
}
```

**المشكلة:**
- Controller يحتوي على queries و business logic معاً
- صعوبة الاختبار
- تكرار الكود

**الحل المقترح:**
- إنشاء `UserService` لإدارة المستخدمين
- إنشاء `MailingListService` لإدارة القوائم البريدية
- نقل منطق subscription renewal إلى Service منفصل

---

**2. SearchApiController (السطور 89-90, 98-99, 132-133, 198-199, 210-211, 218-376)**
```php
$vinData = DB::table('vin_decoded_cache')->where('vin', $vin)->first();
$brandName = DB::table('brands')->where('id', $vinData->brand_id)->value('name');
$results = CatalogItem::where('part_number', 'like', "{$part_number}%")->get();
$cached = DB::table('vin_decoded_cache')->where('vin', $vin)->first();
$attributes = DB::table('vin_spec_mapped as vsm')
    ->join('specifications as s', 's.id', '=', 'vsm.specification_id')
    ->get();
```

**المشكلة:**
- Controller يحتوي على منطق معقد لـ VIN decoding
- استخدام `DB::table()` مباشرة بدلاً من Models
- منطق عمل معقد موجود في Controller

**الحل المقترح:**
- إنشاء `VinDecodingService` لإدارة جميع عمليات VIN
- إنشاء Models لـ `vin_decoded_cache`, `vin_spec_mapped`, إلخ
- نقل كل منطق VIN إلى Service

---

**3. CatalogController (السطور 288-289, 293-294, 317-318, 322-323, 333-334)**
```php
$brand = \App\Domain\Catalog\Models\Brand::where('slug', $brandSlug)->where('status', 1)->first();
$catalogs = \App\Domain\Catalog\Models\Catalog::where('brand_id', $brand->id)->where('status', 1)->get();
$catalog = \App\Domain\Catalog\Models\Catalog::where('slug', $catalogSlug)->first();
$query = \App\Domain\Catalog\Models\Category::where('catalog_id', $catalog->id)->where('level', $level)->get();
$parent = \App\Domain\Catalog\Models\Category::where('catalog_id', $catalog->id)->where('slug', $parentSlug)->first();
```

**المشكلة:**
- Controller يحتوي على queries للبحث عن Brands, Catalogs, Categories
- تكرار نفس الـ queries في أماكن مختلفة

**الحل المقترح:**
- إنشاء `BrandService::findBySlug()`
- إنشاء `CatalogService::findBySlug()`, `getCatalogsForBrand()`
- إنشاء `CategoryService::getCategoriesByLevel()`, `findBySlug()`

---

**4. VehicleCatalogController (السطور 178-179, 239-240, 315-316, 319-334, 349-350)**
```php
$catalog = Catalog::where('code', $catalogCode)->where('brand_id', $brand->id)->first();
$level1Category = Category::where('catalog_id', $catalog->id)->where('full_code', $key1)->first();
$level2Category = Category::where('catalog_id', $catalog->id)->where('full_code', $key2)->first();
$level3Category = Category::where('catalog_id', $catalog->id)->where('full_code', $key3)->first();
$section = Section::where('full_code', $key3)->where('catalog_id', $catalog->id)->first();
```

**المشكلة:**
- Controller يحتوي على queries معقدة لتحميل Category hierarchy
- تكرار نفس المنطق في methods مختلفة

**الحل المقترح:**
- إنشاء `CategoryHierarchyService` لإدارة Category trees
- إنشاء `SectionService::findByCode()`

---

**5. CatalogItemDetailsController (السطور 60-61, 140-141)**
```php
$catalogItem = CatalogItem::where('part_number', $key)->first()
        ?: CatalogItem::where('slug', $key)->firstOrFail();

$catalogItem = \App\Domain\Catalog\Models\CatalogItem::where('part_number', $part_number)->first();
```

**المشكلة:**
- Controller يحتوي على منطق البحث عن CatalogItem
- تكرار نفس المنطق

**الحل المقترح:**
- إنشاء `CatalogItemService::findByPartNumberOrSlug()`

---

## 3️⃣ Service Calls في Views

### 🟡 **المشكلة: View يستدعي Service مباشرة**

**الملف:** `resources/views/components/location-trigger.blade.php` (السطور 22-24)

```php
@php
    $placeholder = $placeholder ?? __('حدد موقعك');
    $locationService = app(\App\Domain\Shipping\Services\CustomerLocationService::class);
    $hasLocation = $locationService->hasLocation();
    $displayText = $locationService->getDisplayText() ?? $placeholder;
@endphp
```

**المشكلة:**
- View يستدعي Service مباشرة
- هذا ينتهك **Separation of Concerns**
- يجعل الاختبار صعباً

**الحل المقترح:**
- تمرير `$hasLocation` و `$displayText` من Controller أو من View Composer
- إنشاء View Composer لـ location data

---

## 4️⃣ Static Methods في Models

### 🟢 **ملاحظة: معظمها مقبول معمارياً**

تم اكتشاف **99 static method** في Models، لكن معظمها مقبول لأنها:
- Factory methods (`getOrCreate`, `firstOrCreate`)
- Query helpers (`findBySlug`, `where()->first()`)
- Constants getters (`getStatusOptions`, `getAllStatuses`)
- Cache helpers (`clearCache`)

#### أمثلة على Static Methods المقبولة:

```php
// Factory methods - مقبول
public static function getOrCreate(int $partyId, int $counterpartyId): self

// Query helpers - مقبول
public static function findBySlug(string $slug): ?self

// Constants - مقبول
public static function getStatusOptions(): array

// Cache helpers - مقبول
public static function clearCache(): void
```

#### ⚠️ Static Methods تحتاج مراجعة:

**1. CatalogReview Model (السطور 166-239)**
```php
public static function averageScore(int $catalogItemId): string
public static function scorePercentage(int $catalogItemId): float
public static function reviewCount(int $catalogItemId): string
public static function customScorePercentage(int $catalogItemId, int $score): float
public static function customReviewPercentage(int $catalogItemId, int $score): string
public static function merchantScorePercentage(int $userId): float
public static function merchantReviewCount(int $userId): int
```

**المشكلة:**
- هذه دوال حسابية يجب أن تكون في Service
- تحتوي على queries و business logic

**الحل المقترح:**
- إنشاء `ReviewStatisticsService` ونقل كل هذه الدوال إليه

---

**2. Purchase Model (السطر 380)**
```php
public static function getShipData($cart): array
{
    $merchant_shipping_id = 0;
    $users = [];
    
    foreach ($cart->items as $cartItem) {
        // Complex logic here
    }
}
```

**المشكلة:**
- منطق معقد لحساب Shipping data موجود في Model
- يجب أن يكون في Service

**الحل المقترح:**
- نقل هذا المنطق إلى `ShippingCalculationService`

---

## 📋 خطة الإصلاح المقترحة

### المرحلة 1: إصلاح Models (أولوية عالية 🔴)

1. ✅ **نقل Business Logic من MerchantItem إلى PriceFormatterService**
   - `merchantSizePrice()` → `PriceFormatterService::calculateFinalPriceWithCommission()`
   - `offPercentage()` → `PriceFormatterService::calculateDiscountPercentage()`
   - حذف `getMerchantCommission()`

2. ✅ **نقل Review Statistics من CatalogReview إلى ReviewStatisticsService**
   - إنشاء `ReviewStatisticsService`
   - نقل جميع الدوال الحسابية

3. ✅ **نقل Shipping Logic من Purchase إلى ShippingCalculationService**
   - نقل `getShipData()` إلى Service جديد

### المرحلة 2: إصلاح Controllers (أولوية متوسطة 🟡)

1. **إنشاء Services جديدة:**
   - `VinDecodingService` - لإدارة VIN operations
   - `BrandService` - لإدارة Brands
   - `CatalogService` - لإدارة Catalogs
   - `CategoryService` - لإدارة Categories
   - `CategoryHierarchyService` - لإدارة Category trees
   - `SectionService` - لإدارة Sections
   - `UserService` - لإدارة Users
   - `MailingListService` - لإدارة Mailing lists

2. **تعديل Controllers لاستخدام Services:**
   - `FrontendController` → استخدام Services بدلاً من queries
   - `SearchApiController` → استخدام `VinDecodingService`
   - `CatalogController` → استخدام `BrandService`, `CatalogService`, `CategoryService`
   - `VehicleCatalogController` → استخدام `CategoryHierarchyService`
   - `CatalogItemDetailsController` → استخدام `CatalogItemService`

### المرحلة 3: إصلاح Views (أولوية متوسطة 🟡)

1. **إنشاء View Composer لـ location data**
   - `LocationViewComposer` - يمرر `$hasLocation` و `$displayText` لجميع Views
   
2. **تعديل `location-trigger.blade.php`**
   - حذف استدعاء Service
   - استخدام المتغيرات الممررة من View Composer

---

## 📊 التأثير المتوقع

### ✅ الفوائد:

1. **Separation of Concerns:**
   - Models تحتوي فقط على Data & Relationships
   - Controllers تحتوي فقط على HTTP logic
   - Services تحتوي على Business Logic
   - Views تحتوي فقط على Presentation Logic

2. **سهولة الاختبار:**
   - يمكن اختبار Services بشكل مستقل
   - يمكن mock Services في Controller tests
   - يمكن اختبار Models بدون Business Logic

3. **إعادة الاستخدام:**
   - Services يمكن استخدامها من Controllers مختلفة
   - تقليل تكرار الكود

4. **سهولة الصيانة:**
   - كل Business Logic في مكان واحد
   - سهولة تعديل المنطق بدون التأثير على أجزاء أخرى

### ⚠️ التحديات:

1. **حجم العمل:**
   - 50+ query يجب نقلها
   - 10+ Service جديد يجب إنشاؤها
   - تعديل Controllers متعددة

2. **Testing:**
   - يجب كتابة اختبارات للـ Services الجديدة
   - يجب تحديث الاختبارات الموجودة

3. **Backward Compatibility:**
   - يجب التأكد من أن التعديلات لا تكسر الكود الموجود

---

## 🎯 الخلاصة

المشروع في حالة جيدة بعد إعادة الهيكلة الأخيرة، لكن لا يزال هناك ديون تقنية يجب معالجتها:

| الفئة | الحالة | التوصية |
|:---|:---:|:---|
| Models | 🟡 جيد مع استثناءات | إصلاح MerchantItem, CatalogReview, Purchase |
| Controllers | 🔴 يحتاج تحسين | نقل Queries إلى Services |
| Views | 🟢 ممتاز | إصلاح location-trigger فقط |
| Services | 🟢 ممتاز | إضافة Services جديدة حسب الحاجة |

**الأولوية:** البدء بإصلاح Models أولاً (المرحلة 1)، ثم Controllers (المرحلة 2)، ثم Views (المرحلة 3).

---

**تم إعداد التقرير بواسطة:** Manus AI  
**التاريخ:** 30 يناير 2026
