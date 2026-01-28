# خطة العمل - Multi-Merchant E-commerce Platform

> آخر تحديث: 2026-01-28
> نقطة البداية: c4b352d8 "To API-Ready"

---

## الهدف
تجهيز المشروع للتوسع المستقبلي (Mobile App, WhatsApp, Mobile Browser) من خلال فصل طبقة العرض عن المنطق.

---

## المنصات المستهدفة

| المنصة | الوصف | الحالة |
|--------|-------|--------|
| Desktop Web | الموقع الحالي | ✅ يعمل |
| Mobile App | تطبيق جوال يستخدم API | ⬜ مخطط |
| Mobile Browser | عرض مختلف للشاشات الصغيرة | ⬜ مخطط |
| WhatsApp | تكامل مع واتس اب | ⬜ مخطط |

**قاعدة ذهبية:** كل الـ formatting يجب أن يكون في Services ليُعاد استخدامه في API.

---

## المراحل

### ✅ Phase 1-4: Data Flow Foundation (COMPLETED)
- ✅ Data Flow Policy established
- ✅ Schema-Descriptor as source of truth
- ✅ CLAUDE.md rules defined
- ✅ Linting tools configured

---

### ✅ Phase 5: API-Ready Presentation Layer (COMPLETED)

**الهدف:** نقل الـ formatting من Controllers إلى Services لإعادة الاستخدام.

```
┌─────────────┐     ┌─────────────────┐     ┌─────────┐
│  Controller │ ──► │ DisplayService  │ ──► │   DTO   │
│(orchestrate)│     │  (formatting)   │     │ (data)  │
└─────────────┘     └─────────────────┘     └─────────┘
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
         Web View                    API Response
```

| # | المهمة | الوصف | الأولوية |
|---|--------|-------|----------|
| ✅ 5.1 | PurchaseDisplayService | تنسيق بيانات الطلبات | DONE |
| ✅ 5.2 | MerchantDisplayService | تنسيق بيانات التاجر | DONE |
| ✅ 5.3 | OperatorDisplayService | تنسيق بيانات لوحة التحكم | DONE |
| ✅ 5.4 | UserDisplayService | تنسيق بيانات المستخدم | DONE |
| ✅ 5.5 | Display DTOs | DTOs موحدة للعرض (موجودة) | DONE |

**المخرج المتوقع:**
```php
// DisplayService - يُستخدم من Web و API
class PurchaseDisplayService {
    public function format(Purchase $purchase): PurchaseDisplayDTO {
        return new PurchaseDisplayDTO(
            id: $purchase->id,
            number: $purchase->purchase_number,
            total_formatted: PriceHelper::format($purchase->pay_amount),
            date_formatted: $purchase->created_at->format('Y-m-d'),
            status_label: __("status.{$purchase->status}"),
            status_color: $this->getStatusColor($purchase->status),
            // ... all pre-computed
        );
    }
}

// Web Controller - orchestration only
public function show($id) {
    $purchase = $this->purchaseService->find($id);
    $dto = $this->displayService->format($purchase);
    return view('purchase.show', ['purchase' => $dto]);
}

// API Controller - same service
public function show($id) {
    $purchase = $this->purchaseService->find($id);
    $dto = $this->displayService->format($purchase);
    return response()->json($dto);
}
```

---

### ⬜ Phase 6: Views Alignment

**الهدف:** تحويل Views لاستخدام Display DTOs - بدون منطق جديد.

| المجموعة | الوصف | الأولوية |
|----------|-------|----------|
| Operator Views | استهلاك OperatorDisplayService | HIGH |
| Merchant Views | استهلاك MerchantDisplayService | HIGH |
| User Views | استهلاك UserDisplayService | MEDIUM |

**القاعدة:**
```blade
{{-- ❌ FORBIDDEN --}}
{{ PriceHelper::format($purchase->pay_amount) }}
{{ $purchase->created_at->format('Y-m-d') }}

{{-- ✅ REQUIRED --}}
{{ $purchase->total_formatted }}
{{ $purchase->date_formatted }}
```

---

## الإحصائيات الحالية

```
php artisan lint:dataflow --ci

Total Violations: 1046
├── Controllers: 506 (queries + formatting)
└── Views: 540 (PriceHelper + date + @php)
```

---

## قواعد العمل

1. **Schema-Descriptor First**: أي feature يبدأ من schema-descriptor
2. **Data Flow Policy**: Model → Service → DTO → View/API
3. **Controllers = Orchestration**: لا formatting، لا queries مباشرة
4. **Services = Logic + Formatting**: كل المنطق هنا
5. **Views = Display Only**: {{ $dto->property }} فقط

---

## أوامر الفحص

```bash
php artisan lint:dataflow --ci              # فحص كامل
php artisan lint:dataflow --layer=view      # Views فقط
php artisan lint:dataflow --layer=controller # Controllers فقط
```

---
