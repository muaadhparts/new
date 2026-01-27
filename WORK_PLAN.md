# خطة العمل - Multi-Merchant E-commerce Platform

> آخر تحديث: 2026-01-28
> نقطة البداية: c4b352d8 "To API-Ready"

---

## الهدف
تحسين جودة الكود وتطبيق Data Flow Policy بشكل كامل، مع تجهيز المشروع للتوسع المستقبلي.

---

## التوسع المستقبلي (يجب مراعاته في كل التعديلات)

| المنصة | الوصف |
|--------|-------|
| Mobile App | تطبيق جوال يستخدم API |
| WhatsApp | تكامل مع واتس اب |
| Mobile Browser | عرض مختلف للشاشات الصغيرة (مسار منفصل) |
| Desktop Web | الموقع الحالي |

**قاعدة ذهبية:** كل الـ formatting يجب أن يكون في Services ليُعاد استخدامه في API.

---

## الحالة الحالية (c4b352d8)

```
Total Violations: 1046
├── Controllers: 506 violations
└── Views: 540 violations
```

---

## المراحل المطلوبة

### المرحلة 1: Controllers Cleanup
**الهدف:** نقل الـ queries من Controllers إلى Services/Repositories

| المجموعة | الملفات | الأولوية |
|----------|---------|----------|
| Api Controllers | ~15 files | HIGH |
| Operator Controllers | ~45 files | HIGH |
| Merchant Controllers | ~20 files | MEDIUM |
| User Controllers | ~10 files | MEDIUM |

### المرحلة 2: Views Cleanup
**الهدف:** إزالة كل PriceHelper, date(), @php, deep access من Views

| المجموعة | التقدير | الأولوية |
|----------|---------|----------|
| Operator Views | ~300 violations | HIGH |
| Merchant Views | ~150 violations | HIGH |
| User Views | ~50 violations | MEDIUM |
| Frontend Views | ~40 violations | LOW |

### المرحلة 3: API-Ready Architecture
**الهدف:** نقل الـ formatting من Controllers إلى Services لإعادة الاستخدام.

| # | المهمة | الملفات | الأولوية |
|---|--------|---------|----------|
| 3.1 | إنشاء PurchaseDisplayService | جديد | HIGH |
| 3.2 | إنشاء MerchantDisplayService | جديد | HIGH |
| 3.3 | إنشاء UserDisplayService | جديد | MEDIUM |
| 3.4 | إنشاء Display DTOs موحدة | جديد | HIGH |

**المخرج المتوقع:**
```php
// Service يُستخدم من Web و API
$displayService->formatPurchase($purchase) → PurchaseDisplayDTO

// Web Controller
return view('...', ['data' => $dto]);

// API Controller (مستقبلاً)
return response()->json($dto);
```

---

## قواعد العمل

1. **Data Flow Policy**: Model → Service → DTO → View/API
2. **لا queries في Views**: كل البيانات تأتي محسوبة
3. **لا compact()**: استخدم explicit arrays
4. **لا formatting في Controllers**: استخدم Services (للـ API)
5. **API-Ready**: كل تعديل يجب أن يكون قابل لإعادة الاستخدام

---

## أوامر الفحص

```bash
# فحص كل الطبقات
php artisan lint:dataflow --ci

# فحص طبقة معينة
php artisan lint:dataflow --layer=view
php artisan lint:dataflow --layer=controller

# مع اقتراحات الإصلاح
php artisan lint:dataflow --fix
```

---

## الوثائق المرجعية

| الموضوع | الملف |
|---------|-------|
| Data Flow Policy | `docs/rules/DATA_FLOW_POLICY.md` |
| CSS Design System | `docs/rules/css-design-system.md` |
| Project Overview | `docs/architecture/project-overview.md` |
| Catalog System | `docs/architecture/catalog-system.md` |

---
