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

### ✅ Phase 6: Views Alignment (COMPLETED)

**الهدف الأصلي:** تحويل Views لاستخدام Display DTOs.

**✅ إصلاح معماري (2026-01-28):**
تم نقل كل formatting من Controllers إلى DisplayServices:
- ✅ CatalogDisplayService - للكتالوج
- ✅ CourierDisplayService - للمندوبين
- ✅ CheckoutDisplayService - للدفع
- ✅ MerchantDisplayService - للتاجر والفروع والأرباح

**الديون التقنية (Technical Debt) - تم الإصلاح ✅:**
| Controller | Method | يجب نقله إلى | الحالة |
|------------|--------|--------------|--------|
| CourierController | index() | CourierDisplayService | ✅ |
| CourierController | serviceArea() | CourierDisplayService | ✅ |
| CourierController | orders() | CourierDisplayService | ✅ |
| CourierController | orderDetails() | CourierDisplayService | ✅ |
| CourierController | transactions() | CourierDisplayService | ✅ |
| CourierController | settlements() | CourierDisplayService | ✅ |
| CourierController | financialReport() | CourierDisplayService | ✅ |
| CheckoutMerchantController | showPayment() | CheckoutDisplayService | ✅ |
| CheckoutMerchantController | showShipping() | CheckoutDisplayService | ✅ |
| CheckoutMerchantController | showAddress() | CheckoutDisplayService | ✅ |
| IncomeController | index() | MerchantDisplayService | ✅ |
| IncomeController | taxReport() | MerchantDisplayService | ✅ |
| IncomeController | statement() | MerchantDisplayService | ✅ |
| IncomeController | monthlyLedger() | MerchantDisplayService | ✅ |
| IncomeController | payouts() | MerchantDisplayService | ✅ |
| MerchantBranchController | index() | MerchantDisplayService | ✅ |
| PartResultController | show() | CatalogDisplayService | ✅ |

**تقدم الهجرة:**
- ✅ Created `CatalogDisplayService` for catalog display formatting
- ✅ Migrated `PartResultController::show()` to use `CatalogDisplayService`
- ✅ Extended `MerchantDisplayService` with earnings/financial formatting methods
- ✅ Migrated all `IncomeController` methods to use `MerchantDisplayService`
- ✅ Created `CourierDisplayService` for courier display formatting
- ✅ Migrated all `CourierController` methods to use `CourierDisplayService`
- ✅ Created `CheckoutDisplayService` for checkout display formatting
- ✅ Migrated all `CheckoutMerchantController` methods to use `CheckoutDisplayService`
- ✅ Extended `MerchantDisplayService` with branch formatting method
- ✅ Migrated `MerchantBranchController::index()` to use `MerchantDisplayService`

**المنهج الصحيح:**
```php
// ❌ WRONG - Formatting in Controller (ما تم سابقاً)
public function index() {
    $purchases = Purchase::where(...)->get();
    $purchases->each(function($p) {
        $p->total_formatted = monetaryUnit()->format($p->total);  // ❌
    });
    return view('...', compact('purchases'));
}

// ✅ CORRECT - Formatting in DisplayService (المطلوب)
public function index() {
    $purchases = $this->purchaseService->getForMerchant($merchantId);
    $displayData = $this->displayService->formatCollection($purchases);
    return view('...', ['purchases' => $displayData]);
}
```

**القاعدة:**
```blade
{{-- ❌ FORBIDDEN --}}
{{ PriceHelper::format($purchase->pay_amount) }}
{{ $purchase->created_at->format('Y-m-d') }}

{{-- ✅ REQUIRED --}}
{{ $purchase->total_formatted }}  {{-- من DisplayService --}}
{{ $purchase->date_formatted }}   {{-- من DisplayService --}}
```

---

## الإحصائيات الحالية

```
php artisan lint:dataflow --ci

View Violations: ~458 (need DisplayService migration)
Controller Violations: ~506 (formatting should move to DisplayService)
```

**ملاحظة:** الـ violations في Views انخفضت لكن بطريقة خاطئة (نقل formatting إلى Controller).
المطلوب: نقل كل formatting من Controller إلى DisplayService.

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
