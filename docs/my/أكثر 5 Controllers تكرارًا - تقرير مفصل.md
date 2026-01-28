# أكثر 5 Controllers تكرارًا - تقرير مفصل

تاريخ التقرير: 29 يناير 2026

---

## 1️⃣ LoginController.php

**عدد النسخ:** 6 نسخ  
**إجمالي الأسطر:** 568 سطر (موزعة على 6 ملفات)

### النسخ المكتشفة:

#### النسخة #1
- **المسار:** `app/Http/Controllers/Auth/Courier/LoginController.php`
- **Namespace:** `App\Http\Controllers\Auth\Courier`
- **عدد Methods:** 3
- **عدد الأسطر:** 52
- **الغرض:** تسجيل دخول المندوبين (Couriers) - نسخة Auth

#### النسخة #2
- **المسار:** `app/Http/Controllers/Auth/Operator/LoginController.php`
- **Namespace:** `App\Http\Controllers\Auth\Operator`
- **عدد Methods:** 4
- **عدد الأسطر:** 60
- **الغرض:** تسجيل دخول المشغلين (Operators/Admins) - نسخة Auth

#### النسخة #3
- **المسار:** `app/Http/Controllers/Auth/User/LoginController.php`
- **Namespace:** `App\Http\Controllers\Auth\User`
- **عدد Methods:** 3
- **عدد الأسطر:** 85
- **الغرض:** تسجيل دخول المستخدمين (Users) - نسخة Auth

#### النسخة #4
- **المسار:** `app/Http/Controllers/Courier/LoginController.php`
- **Namespace:** `App\Http\Controllers\Courier`
- **عدد Methods:** 6
- **عدد الأسطر:** 84
- **الغرض:** تسجيل دخول المندوبين - نسخة مباشرة

#### النسخة #5
- **المسار:** `app/Http/Controllers/Operator/LoginController.php`
- **Namespace:** `App\Http\Controllers\Operator`
- **عدد Methods:** 8
- **عدد الأسطر:** 134
- **الغرض:** تسجيل دخول المشغلين - نسخة مباشرة

#### النسخة #6
- **المسار:** `app/Http/Controllers/User/LoginController.php`
- **Namespace:** `App\Http\Controllers\User`
- **عدد Methods:** 10
- **عدد الأسطر:** 153
- **الغرض:** تسجيل دخول المستخدمين - نسخة مباشرة

### 💡 توصيات التوحيد:
- إنشاء `AuthController` موحد في `app/Http/Controllers/Auth/`
- استخدام `AuthService` في `app/Domain/Identity/Services/`
- تمرير `user_type` كـ parameter للتمييز بين الأنواع
- الاحتفاظ بـ routes منفصلة ولكن توجيهها لنفس الـ Controller

---

## 2️⃣ WithdrawController.php

**عدد النسخ:** 4 نسخ  
**إجمالي الأسطر:** 415 سطر

### النسخ المكتشفة:

#### النسخة #1
- **المسار:** `app/Http/Controllers/Api/User/WithdrawController.php`
- **Namespace:** `App\Http\Controllers\Api\User`
- **عدد Methods:** 3
- **عدد الأسطر:** 192
- **الغرض:** API للمستخدمين - طلبات السحب

#### النسخة #2
- **المسار:** `app/Http/Controllers/Courier/WithdrawController.php`
- **Namespace:** `App\Http\Controllers\Courier`
- **عدد Methods:** 3
- **عدد الأسطر:** 92
- **الغرض:** Web للمندوبين - طلبات السحب

#### النسخة #3
- **المسار:** `app/Http/Controllers/Merchant/WithdrawController.php`
- **Namespace:** `App\Http\Controllers\Merchant`
- **عدد Methods:** 3
- **عدد الأسطر:** 91
- **الغرض:** Web للتجار - طلبات السحب

#### النسخة #4
- **المسار:** `app/Http/Controllers/User/WithdrawController.php`
- **Namespace:** `App\Http\Controllers\User`
- **عدد Methods:** 3
- **عدد الأسطر:** 40
- **الغرض:** Web للمستخدمين - طلبات السحب

### 💡 توصيات التوحيد:
- إنشاء `WithdrawController` موحد
- استخدام `WithdrawService` موحد في `app/Domain/Accounting/Services/`
- تمييز نوع المستخدم من خلال `auth()->user()->type` أو middleware
- استخدام policies للتحكم في الصلاحيات

---

## 3️⃣ RegisterController.php

**عدد النسخ:** 4 نسخ  
**إجمالي الأسطر:** 463 سطر

### النسخ المكتشفة:

#### النسخة #1
- **المسار:** `app/Http/Controllers/Auth/Courier/RegisterController.php`
- **Namespace:** `App\Http\Controllers\Auth\Courier`
- **عدد Methods:** 2
- **عدد الأسطر:** 114
- **الغرض:** تسجيل المندوبين الجدد - Auth

#### النسخة #2
- **المسار:** `app/Http/Controllers/Auth/User/RegisterController.php`
- **Namespace:** `App\Http\Controllers\Auth\User`
- **عدد Methods:** 2
- **عدد الأسطر:** 180
- **الغرض:** تسجيل المستخدمين الجدد - Auth

#### النسخة #3
- **المسار:** `app/Http/Controllers/Courier/RegisterController.php`
- **Namespace:** `App\Http\Controllers\Courier`
- **عدد Methods:** 2
- **عدد الأسطر:** 43
- **الغرض:** تسجيل المندوبين - مباشر

#### النسخة #4
- **المسار:** `app/Http/Controllers/User/RegisterController.php`
- **Namespace:** `App\Http\Controllers\User`
- **عدد Methods:** 4
- **عدد الأسطر:** 126
- **الغرض:** تسجيل المستخدمين - مباشر

### 💡 توصيات التوحيد:
- إنشاء `RegistrationController` موحد
- استخدام `RegistrationService` في `app/Domain/Identity/Services/`
- استخدام Form Requests منفصلة لكل نوع مستخدم
- تطبيق Strategy Pattern لاختلافات التسجيل

---

## 4️⃣ PurchaseController.php

**عدد النسخ:** 4 نسخ  
**إجمالي الأسطر:** 1,235 سطر

### النسخ المكتشفة:

#### النسخة #1
- **المسار:** `app/Http/Controllers/Api/User/PurchaseController.php`
- **Namespace:** `App\Http\Controllers\Api\User`
- **عدد Methods:** 3
- **عدد الأسطر:** 65
- **الغرض:** API للمستخدمين - عرض الطلبات

#### النسخة #2
- **المسار:** `app/Http/Controllers/Merchant/PurchaseController.php`
- **Namespace:** `App\Http\Controllers\Merchant`
- **عدد Methods:** 7
- **عدد الأسطر:** 521
- **الغرض:** Web للتجار - إدارة الطلبات

#### النسخة #3
- **المسار:** `app/Http/Controllers/Operator/PurchaseController.php`
- **Namespace:** `App\Http\Controllers\Operator`
- **عدد Methods:** 15
- **عدد الأسطر:** 379
- **الغرض:** Web للمشغلين - إدارة كاملة للطلبات

#### النسخة #4
- **المسار:** `app/Http/Controllers/User/PurchaseController.php`
- **Namespace:** `App\Http\Controllers\User`
- **عدد Methods:** 8
- **عدد الأسطر:** 270
- **الغرض:** Web للمستخدمين - عرض وإدارة طلباتهم

### 💡 توصيات التوحيد:
- **تحذير:** هذا الـ Controller الأكثر تعقيدًا (1,235 سطر)
- يحتاج إلى تقسيم أولاً قبل التوحيد
- إنشاء عدة Services متخصصة:
  - `PurchaseQueryService` - للاستعلامات
  - `PurchaseManagementService` - للإدارة
  - `PurchaseDisplayService` - للعرض (موجود بالفعل)
- بعد ذلك، إنشاء Controllers منفصلة حسب الدور ولكن تستخدم نفس الـ Services

---

## 5️⃣ MerchantController.php

**عدد النسخ:** 4 نسخ  
**إجمالي الأسطر:** 892 سطر

### النسخ المكتشفة:

#### النسخة #1
- **المسار:** `app/Http/Controllers/Api/Front/MerchantController.php`
- **Namespace:** `App\Http\Controllers\Api\Front`
- **عدد Methods:** 3
- **عدد الأسطر:** 133
- **الغرض:** API للواجهة الأمامية - عرض معلومات التاجر

#### النسخة #2
- **المسار:** `app/Http/Controllers/Front/MerchantController.php`
- **Namespace:** `App\Http\Controllers\Front`
- **عدد Methods:** 3
- **عدد الأسطر:** 123
- **الغرض:** Web للواجهة الأمامية - عرض صفحة التاجر

#### النسخة #3
- **المسار:** `app/Http/Controllers/Merchant/MerchantController.php`
- **Namespace:** `App\Http\Controllers\Merchant`
- **عدد Methods:** 9
- **عدد الأسطر:** 313
- **الغرض:** Web للتاجر - إدارة حسابه وإعداداته

#### النسخة #4
- **المسار:** `app/Http/Controllers/Operator/MerchantController.php`
- **Namespace:** `App\Http\Controllers\Operator`
- **عدد Methods:** 16
- **عدد الأسطر:** 323
- **الغرض:** Web للمشغل - إدارة التجار

### 💡 توصيات التوحيد:
- فصل الاهتمامات:
  - `MerchantProfileController` - للعرض العام
  - `MerchantDashboardController` - للتاجر نفسه
  - `MerchantManagementController` - للمشغلين
- استخدام `MerchantService` موحد
- استخدام `MerchantDisplayService` (موجود بالفعل)

---

## 📊 ملخص إحصائي

| Controller | النسخ | إجمالي الأسطر | الأولوية |
|:---|:---:|:---:|:---:|
| LoginController | 6 | 568 | 🔴 عالية جدًا |
| WithdrawController | 4 | 415 | 🟠 عالية |
| RegisterController | 4 | 463 | 🟠 عالية |
| PurchaseController | 4 | 1,235 | 🔴 عالية جدًا (معقد) |
| MerchantController | 4 | 892 | 🟡 متوسطة |
| **المجموع** | **22** | **3,573** | - |

---

## 🎯 خطة التنفيذ المقترحة

### المرحلة 1: Controllers البسيطة (أسبوع 1)
1. ✅ توحيد `LoginController` (الأسهل والأكثر تكرارًا)
2. ✅ توحيد `RegisterController`

### المرحلة 2: Controllers المتوسطة (أسبوع 2)
3. ✅ توحيد `WithdrawController`
4. ✅ توحيد `MerchantController` (مع الفصل)

### المرحلة 3: Controllers المعقدة (أسبوع 3-4)
5. ✅ إعادة هيكلة `PurchaseController` (تقسيم أولاً)
6. ✅ توحيد بعد التقسيم

---

## 🛠️ نمط التوحيد الموصى به

```php
// ❌ الوضع الحالي - 6 ملفات منفصلة
Auth/User/LoginController.php
Auth/Courier/LoginController.php
Auth/Operator/LoginController.php
User/LoginController.php
Courier/LoginController.php
Operator/LoginController.php

// ✅ الوضع المطلوب - ملف واحد موحد
Auth/LoginController.php

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private LoginDisplayService $displayService
    ) {}
    
    public function login(LoginRequest $request, string $userType)
    {
        // $userType: 'user', 'courier', 'operator'
        $credentials = $request->validated();
        
        $result = $this->authService->login($credentials, $userType);
        
        if ($result->success) {
            return redirect()->route("{$userType}.dashboard");
        }
        
        return back()->withErrors($result->errors);
    }
}
```

---

## 📝 ملاحظات هامة

1. **لا تحذف الملفات القديمة مباشرة:** احتفظ بها كـ backup حتى تتأكد من عمل النسخة الموحدة
2. **اختبر بعد كل توحيد:** تأكد من عمل جميع المسارات (routes) بشكل صحيح
3. **حدّث الـ Routes:** بعد التوحيد، حدّث ملفات الـ routes لتشير للـ Controller الموحد
4. **استخدم Middleware:** للتمييز بين أنواع المستخدمين بدلاً من تكرار الكود
5. **وثّق التغييرات:** احتفظ بسجل للتغييرات في `CHANGELOG.md`

---

**تم إعداد التقرير بواسطة:** Manus AI  
**التاريخ:** 29 يناير 2026
