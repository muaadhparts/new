# 📋 قائمة TODO للإصلاحات المتبقية

**التاريخ:** 30 يناير 2026  
**المشروع:** Muaadh Parts  
**Branch:** withoutLegacy

---

## 🎯 ملخص

هذا الملف يحتوي على قائمة بجميع الـ Controllers التي لا تزال تحتوي على queries مباشرة وتحتاج إلى إصلاح معماري. يجب نقل جميع الـ queries إلى Services متخصصة.

---

## 1️⃣ FrontendController

**الملف:** `app/Http/Controllers/Front/FrontendController.php`

### 🔴 **الديون التقنية:**

- **السطور 68-69:** `DB::table(\'users\')` - يجب نقلها إلى `UserService`.
- **السطور 126-127:** `CatalogItem::where()` - يجب نقلها إلى `CatalogItemService`.
- **السطور 232-233:** `MailingList::where()` - يجب نقلها إلى `MailingListService`.
- **السطور 265-292:** `DB::table(\'users\')` - منطق تجديد الاشتراكات، يجب نقله إلى `SubscriptionService`.

### ✅ **الحل المقترح:**

1. إنشاء `UserService`.
2. إنشاء `MailingListService`.
3. إنشاء `SubscriptionService`.
4. تعديل `FrontendController` لاستخدام Services.

---

## 2️⃣ SearchApiController

**الملف:** `app/Http/Controllers/Api/Front/SearchApiController.php`

### 🔴 **الديون التقنية:**

- **السطور 89-90:** `DB::table(\'vin_decoded_cache\')` - يجب نقلها إلى `VinDecodingService`.
- **السطور 98-99:** `DB::table(\'brands\')` - يجب نقلها إلى `BrandService`.
- **السطور 132-133:** `CatalogItem::where()` - يجب نقلها إلى `CatalogItemService`.
- **السطور 198-199:** `DB::table(\'vin_decoded_cache\')` - يجب نقلها إلى `VinDecodingService`.
- **السطور 210-211:** `DB::table(\'vin_spec_mapped\')` - يجب نقلها إلى `VinDecodingService`.
- **السطور 218-376:** منطق معقد لـ VIN decoding - يجب نقله بالكامل إلى `VinDecodingService`.

### ✅ **الحل المقترح:**

1. إنشاء `VinDecodingService`.
2. إنشاء Models لـ `vin_decoded_cache`, `vin_spec_mapped`.
3. تعديل `SearchApiController` لاستخدام `VinDecodingService`.

---

## 3️⃣ VehicleCatalogController

**الملف:** `app/Http/Controllers/Front/VehicleCatalogController.php`

### 🔴 **الديون التقنية:**

- **السطور 178-179:** `Catalog::where()` - يجب نقلها إلى `CatalogService`.
- **السطور 239-240:** `Category::where()` - يجب نقلها إلى `CategoryService`.
- **السطور 315-316:** `Category::where()` - يجب نقلها إلى `CategoryService`.
- **السطور 319-334:** `Section::where()` - يجب نقلها إلى `SectionService`.

### ✅ **الحل المقترح:**

1. إنشاء `SectionService`.
2. تعديل `VehicleCatalogController` لاستخدام Services.

---

## 4️⃣ CatalogItemDetailsController

**الملف:** `app/Http/Controllers/Front/CatalogItemDetailsController.php`

### 🔴 **الديون التقنية:**

- **السطور 60-61:** `CatalogItem::where()` - يجب نقلها إلى `CatalogItemService`.
- **السطور 140-141:** `CatalogItem::where()` - يجب نقلها إلى `CatalogItemService`.

### ✅ **الحل المقترح:**

1. إنشاء `CatalogItemService::findByPartNumberOrSlug()`.
2. تعديل `CatalogItemDetailsController` لاستخدام Service.

---

## 📋 قائمة Controllers أخرى تحتاج مراجعة

- `Admin/BrandController.php`
- `Admin/CatalogController.php`
- `Admin/CategoryController.php`
- `Admin/DashboardController.php`
- `Admin/FaqController.php`
- `Admin/LanguageController.php`
- `Admin/LoginController.php`
- `Admin/OrderController.php`
- `Admin/PageController.php`
- `Admin/PaymentGatewayController.php`
- `Admin/RoleController.php`
- `Admin/StaffController.php`
- `Admin/UserController.php`
- `Api/Front/FrontendController.php`
- `Api/User/LoginController.php`
- `Api/User/RegisterController.php`
- `User/LoginController.php`
- `User/RegisterController.php`
- `User/UserController.php`

---

**تم إعداد القائمة بواسطة:** Manus AI  
**التاريخ:** 30 يناير 2026
