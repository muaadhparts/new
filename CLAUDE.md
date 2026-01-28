# CLAUDE.md

> **Full Documentation:** See `docs/` folder for detailed guides and references.

---

## ⚠️ CRITICAL: MULTI-CHANNEL PLATFORM

**هذا المشروع ليس موقع ويب. هذا منصة متعددة القنوات.**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    MULTI-CHANNEL PLATFORM                           │
│                                                                     │
│    ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│    │ Desktop  │  │  Mobile  │  │  Mobile  │  │ WhatsApp │         │
│    │   Web    │  │   App    │  │ Browser  │  │   Bot    │         │
│    └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘         │
│         │             │             │             │                 │
│         │         ┌───┴───┐         │             │                 │
│         │         │  API  │         │             │                 │
│         │         └───┬───┘         │             │                 │
│         │             │             │             │                 │
│         └─────────────┴─────────────┴─────────────┘                 │
│                           │                                         │
│                   ┌───────▼───────┐                                 │
│                   │ DisplayService │  ← كل FORMATTING هنا           │
│                   └───────┬───────┘                                 │
│                   ┌───────▼───────┐                                 │
│                   │    Services   │  ← كل LOGIC هنا                 │
│                   └───────┬───────┘                                 │
│                   ┌───────▼───────┐                                 │
│                   │     DTOs      │  ← كل DATA هنا                  │
│                   └───────────────┘                                 │
└─────────────────────────────────────────────────────────────────────┘
```

### المبادئ الملزمة (MANDATORY)

#### 1. كل منطق يجب أن يكون قابل لإعادة الاستخدام
```php
// ❌ FORBIDDEN - كود لا يمكن استخدامه في API
$purchase->date_formatted = $purchase->created_at->format('d-m-Y');

// ✅ REQUIRED - كود يعمل في Web و API و WhatsApp معاً
$dto = $displayService->formatPurchase($purchase);
```

#### 2. Controllers = Orchestration فقط
```php
// ❌ FORBIDDEN في Controller
$data['total'] = number_format($purchase->total, 2);     // formatting
$data['tax'] = $purchase->total * 0.15;                  // calculation
$data['status_label'] = __("status.{$purchase->status}"); // display logic

// ✅ REQUIRED - Controller يستدعي Service فقط
public function show($id) {
    $purchase = $this->purchaseService->find($id);
    $dto = $this->displayService->format($purchase);
    return view('purchase.show', ['data' => $dto]);
}
```

#### 3. Formatting/Display Logic = DisplayService أو DTO فقط
```php
// ❌ FORBIDDEN - formatting في Controller أو View
{{ number_format($price, 2) }}
{{ $date->format('Y-m-d') }}
{{ PriceHelper::show($amount) }}

// ✅ REQUIRED - DisplayService
class PurchaseDisplayService {
    public function format(Purchase $p): array {
        return [
            'total_formatted' => monetaryUnit()->format($p->total),
            'date_formatted' => $p->created_at->format('d-m-Y'),
            'status_label' => __("status.{$p->status}"),
        ];
    }
}
```

#### 3.1 تنسيق العملات = monetaryUnit()->format() دائماً
```php
// ❌ FORBIDDEN - تنسيق يدوي غير مركزي
$currencySign . number_format($amount, 2)       // غير مركزي
'SAR ' . number_format($price, 2)               // غير مركزي
$currency->sign . number_format($total, 2)      // غير مركزي

// ✅ REQUIRED - مركزي عبر كل القنوات
monetaryUnit()->format($amount)                 // مركزي ✓

// ❌ FORBIDDEN - تمرير العملة للـ DisplayService
$this->displayService->format($data, $currencySign);  // لا

// ✅ REQUIRED - DisplayService يستخدم monetaryUnit() داخلياً
$this->displayService->format($data);  // نعم
```
**السبب:** إذا غيرت `monetaryUnit()->format()` ستتغير كل القنوات تلقائياً.

#### 4. Views = Consumers فقط (عرض قيم جاهزة)
```blade
{{-- ❌ FORBIDDEN --}}
{{ number_format($amount, 2) }}
{{ $model->relationship->name }}
{{ $date->format('Y-m-d') }}
@php $total = $items->sum('price'); @endphp

{{-- ✅ REQUIRED --}}
{{ $dto->totalFormatted }}
{{ $dto->relationshipName }}
{{ $dto->dateFormatted }}
{{ $dto->itemsTotal }}
```

#### 5. تغيير UI = Blade/CSS/JS فقط
```
عند تغيير شكل الصفحات:
✅ المسموح تغييره:
   - Blade templates
   - CSS styles
   - JavaScript

❌ ممنوع لمس:
   - Services
   - DTOs
   - Business Logic
```

#### 6. إضافة مسارات Mobile = نفس Service/DTO
```php
// Web Route
Route::get('/purchases/{id}', [WebPurchaseController::class, 'show']);

// Mobile Route - نفس الـ Service!
Route::get('/m/purchases/{id}', [MobilePurchaseController::class, 'show']);

// كلاهما يستخدم:
$dto = $this->displayService->format($purchase);
// الاختلاف فقط في View و UX
```

#### 7. API/WhatsApp = نفس DisplayService بدون Duplication
```php
// Web Controller
return view('purchase.show', ['data' => $displayService->format($purchase)]);

// API Controller - نفس الـ Service!
return response()->json($displayService->format($purchase));

// WhatsApp Handler - نفس الـ Service!
return $this->whatsapp->send($displayService->format($purchase));
```

#### 8. أي إصلاح = ينتقل لمكانه الصحيح
```php
// ❌ FORBIDDEN - ترقيع في Controller
$purchase->total_formatted = '$' . number_format($purchase->total, 2);

// ✅ REQUIRED - نقل للـ DisplayService
// في DisplayService:
'total_formatted' => monetaryUnit()->format($purchase->total),
```

---

## GOLDEN RULES

### 1. CatalogItem-First Display
**CatalogItem is the SINGLE SOURCE for product cards.** One card per SKU, NOT per merchant offer.

### 2. Multi-Merchant Context
Every operation MUST have `merchant_id`. Missing merchant = FAIL immediately.

### 3. MonetaryUnit via Service Only
All monetary operations MUST use `monetaryUnit()` helper or `MonetaryUnitService`.

### 4. CSS via Design System Only
All new CSS in `muaadh-system.css`. Use `m-*` prefix classes and CSS variables.

### 5. Migrations Only
All database changes via Laravel migrations. `database/schema/` is READ-ONLY reference.

### 6. Schema-Descriptor is Source of Truth
**`database/schema-descriptor/schema-descriptor.txt` defines the ACTUAL database schema.**

> **لا كود يعيش بدون عقد بيانات واضح**

```
WORKFLOW RULES:
┌─────────────────────────────────────────────────────────────┐
│  NEW FEATURE  → Start from schema-descriptor               │
│  BUG FIX      → Verify against schema-descriptor           │
│  REFACTOR     → Reject if violates schema-descriptor       │
└─────────────────────────────────────────────────────────────┘

CRITICAL RULES:
1. Any column NOT in schema-descriptor.txt MUST NOT be used in code
2. Any table NOT in schema-descriptor.txt MUST NOT be referenced
3. Any discovered mismatch MUST be fixed immediately
4. Before using a column, VERIFY it exists in schema-descriptor.txt
```

```php
// FORBIDDEN - Column doesn't exist in schema
$item->is_discount;      // ❌ Not in schema-descriptor
$item->discount_date;    // ❌ Not in schema-descriptor
$page->meta_tag;         // ❌ Not in schema-descriptor

// REQUIRED - Only use columns from schema-descriptor
$item->previous_price;   // ✅ Exists in schema-descriptor
$item->price;            // ✅ Exists in schema-descriptor
```

**Check schema:** `database/schema-descriptor/schema-descriptor.txt`

### 7. Blade Display Only
**Blade files are DISPLAY ONLY.** All data must arrive pre-computed from Controller/Service via DTO or ViewData.

```blade
{{-- FORBIDDEN in Blade --}}
$model->relationship()->count()     {{-- Query in view --}}
$var ?? 'default'                   {{-- Fallback hides bugs --}}
DB::table('x')->get()               {{-- Direct query --}}
@php complex logic @endphp          {{-- Business logic in view --}}
json_encode($data)                  {{-- Transform in view --}}

{{-- REQUIRED --}}
{{ $dto->offersCount }}             {{-- Pre-computed in Service --}}
{{ $dto->formattedPrice }}          {{-- Pre-formatted in Service --}}
@foreach($items as $item)           {{-- Collection passed from Controller --}}
```

**Lint Check:** `php artisan lint:blade --ci`

### 8. Data Flow Policy
**Strict one-way data flow: Model -> Service -> DTO -> View**

```
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│  Model  │ ──► │ Service │ ──► │   DTO   │ ──► │  View   │
│ (Query) │     │ (Logic) │     │ (Data)  │     │(Display)│
└─────────┘     └─────────┘     └─────────┘     └─────────┘
```

```php
// FORBIDDEN in Controller
return view('page', compact('product')); // Model to View!
return view('page', ['item' => $model]); // Model to View!

// REQUIRED
return view('page', ['card' => $dto]);   // DTO only!
return view('page', ['data' => $dto]);   // DTO only!
```

```php
// FORBIDDEN in Helpers
function getCount($id) { return Model::where(...)->count(); } // Query!

// REQUIRED - Helpers are Service accessors only
function monetaryUnit() { return app(MonetaryUnitService::class); }
```

**Full Policy:** `docs/rules/DATA_FLOW_POLICY.md`
**Lint Check:** `php artisan lint:dataflow --ci`

### 9. Event-Driven Core
**All significant actions MUST dispatch Domain Events. Services dispatch events, Listeners handle side effects.**

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DOMAIN LAYER                                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐             │
│  │   Service   │───►│   Event     │───►│  Dispatcher │             │
│  │  (Action)   │    │  (Fact)     │    │  (Laravel)  │             │
│  └─────────────┘    └─────────────┘    └──────┬──────┘             │
└─────────────────────────────────────────────────┼───────────────────┘
                                                  │
           ┌──────────────────────────────────────┼──────────────────┐
           │                                      ▼                  │
           │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
           │  │  Listener   │  │  Listener   │  │  Listener   │     │
           │  │ (Notify)    │  │ (Stock)     │  │ (Ledger)    │     │
           │  └─────────────┘  └─────────────┘  └─────────────┘     │
           │                    LISTENERS LAYER                      │
           └─────────────────────────────────────────────────────────┘
```

```php
// FORBIDDEN - Direct side effects in services
$this->sendNotifications($purchase, $merchant);
Mail::send($email, $data);
$this->accountingService->createEntry(...);
$this->notificationService->notify(...);

// REQUIRED - Dispatch event, listeners handle side effects
PurchasePlacedEvent::dispatch(PurchasePlacedEvent::fromPurchase($purchase));
// Listeners automatically handle:
// - Customer email → NotifyCustomerOfPurchaseListener
// - Merchant email → NotifyMerchantOfPurchaseListener
// - Accounting → CreateAccountingEntriesListener
```

**Event Rules:**
1. Events are **immutable facts** (past tense: Placed, Confirmed, Completed)
2. Events contain **all data needed** by listeners
3. Services **dispatch events**, Listeners **handle side effects**
4. One event can have **many listeners**
5. Listeners are **single-responsibility** (one task each)

**Channel Independence:**
- Web, Mobile, API, WhatsApp → Same event, same behavior
- View changes → No domain/service changes needed
- New channel → Just consume existing events

**Full Plan:** `docs/plans/EVENT_DRIVEN_ARCHITECTURE_PLAN.md`

---

## FORBIDDEN PATTERNS

### CSS - Will Fail Build
```css
/* FORBIDDEN */
color: #333;
background: #006c35;
style="color: #fff"

/* REQUIRED */
color: var(--text-primary);
background: var(--action-primary);
```
- No hardcoded HEX/RGB colors
- No inline style colors
- No CSS in `style.css` (FROZEN)

### Card Display - Architecture Violation
```php
// FORBIDDEN - MerchantItem as primary source for cards
$data['featured'] = MerchantItem::where('featured', 1)->with('catalogItem')->get();
$listings = MerchantItem::whereHas('catalogItem', ...)->paginate();

// REQUIRED - CatalogItem first
$data['featured'] = CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))->get();
```

### Shipping/Payment - Wrong Ownership Logic
```php
// FORBIDDEN
whereIn('user_id', [0, $merchantId])  // Doesn't check operator!
user_id = merchant_id AND operator = merchant_id  // Redundant

// REQUIRED - Use scope
$shipping = Shipping::forMerchant($merchantId)->get();
$payments = MerchantPayment::forMerchant($merchantId)->get();
```

### MonetaryUnit - Direct Access
```php
// FORBIDDEN
MonetaryUnit::where('is_default', 1)->first();
Session::get('monetary_unit');
$sign = '$';
number_format($price, 2) . $curr->sign;

// REQUIRED
monetaryUnit()->getCurrent();
monetaryUnit()->format($amount);
monetaryUnit()->convertAndFormat($amount);
```

### Cart Data - Manual JSON
```php
// FORBIDDEN
$cart = json_encode($cartArray);
$items = json_decode($purchase->cart, true);

// REQUIRED - Model handles encoding
$purchase->cart = $cartArray;
$items = $purchase->getCartItems();
```

### Database - Destructive Operations
```sql
-- ABSOLUTELY FORBIDDEN (even if user requests)
DROP DATABASE                    -- NEVER
DROP TABLE (with data)           -- NEVER (only _backup/_old/_temp allowed)
TRUNCATE TABLE                   -- NEVER
DELETE FROM table (without WHERE) -- NEVER
php artisan migrate:fresh        -- NEVER in production
php artisan migrate:reset        -- NEVER in production
php artisan db:wipe              -- NEVER
```

**CRITICAL RULES:**
1. When renaming tables: Keep `_old` suffix forever, NEVER delete
2. All schema changes via migrations ONLY
3. Schema reference: `database/schema-descriptor/schema-descriptor.txt` (READ-ONLY)
4. To recreate tables: Use migrations with `CREATE TABLE IF NOT EXISTS`
5. NEVER run destructive commands even if user requests

---

## FORBIDDEN TERMS

| FORBIDDEN | REQUIRED |
|-----------|----------|
| `vendor` | `merchant` |
| `product` | `catalog_item` |
| `order` | `purchase` |
| `riders` | `couriers` |
| `admin` | `operator` |
| `category/subcategory/childcategory` | `NewCategory` (levels) |

---

## REMOVED FEATURES - DO NOT RECREATE

### Permanently Deleted (2026-01-19/20)
```php
// FORBIDDEN - These features no longer exist
Package::find($id);                    // Packaging system removed
$item->item_type === 'digital';        // Digital products removed
Route::get('catalog-items/import'...); // Bulk import removed

// Homepage classification removed
$merchantItem->featured;
$merchantItem->top;
$merchantItem->trending;
$merchantItem->best;
$merchantItem->is_discount;
MerchantItem::where('featured', 1)->get();
$theme->show_featured_items;

// CatalogItem wrong attributes
$catalogItem->price;    // Use $merchantItem->price
$catalogItem->stock;    // Use $merchantItem->stock
$catalogItem->color;    // Use $merchantItem->color
$catalogItem->features; // Doesn't exist

// CatalogItem DROPPED columns (2026-01-26) - NEVER recreate
$catalogItem->tags;             // Column dropped from database
$catalogItem->is_meta;          // Column dropped from database
$catalogItem->meta_tag;         // Column dropped from database
$catalogItem->meta_description; // Column dropped from database
$catalogItem->youtube;          // Column dropped from database
$catalogItem->measure;          // Column dropped from database
$catalogItem->hot;              // Column dropped from database
$catalogItem->latest;           // Column dropped from database
$catalogItem->sale;             // Column dropped from database
$catalogItem->cross_items;      // Column dropped from database
```

---

## DEVELOPMENT RULES

1. **No New Files Unless Necessary** - Edit existing, delete unused
2. **No Logic in Views** - Views for display only, no queries in Blade
3. **Centralized Processing** - Shared logic in Services, DRY enforced
4. **Final Fixes Only** - No half-fixes, no surface patches
5. **Clean Code** - Remove unused code immediately, no fallback layers

---

## QUICK REFERENCE

### Data Location
| Data | Table |
|------|-------|
| Catalog info | `catalog_items` |
| Pricing/stock | `merchant_items` |
| Branches | `merchant_branches` |
| Orders | `purchases` |
| Per-merchant | `merchant_purchases` |

### Common Commands
```bash
npm run build                    # Build with lint
php artisan migrate              # Run migrations
php artisan cache:clear          # Clear cache
php artisan lint:blade --ci      # Check Blade Display Only rule
php artisan lint:dataflow --ci   # Check Data Flow Policy (all layers)
php artisan lint:dataflow --layer=view     # Check views only
php artisan lint:dataflow --layer=controller  # Check controllers only
php artisan lint:dataflow --fix  # Show fix suggestions
```

### CSS Classes
| Class | Purpose |
|-------|---------|
| `.m-btn--primary` | Main action |
| `.m-btn--danger` | Destructive |
| `.m-card` | Content card |
| `.m-badge--*` | Status badges |

---

## DOCUMENTATION INDEX

| Topic | Location |
|-------|----------|
| Project Overview | `docs/architecture/project-overview.md` |
| Catalog System | `docs/architecture/catalog-system.md` |
| Multi-Merchant | `docs/architecture/multi-merchant.md` |
| Cart Architecture | `docs/architecture/cart-architecture.md` |
| CSS Design System | `docs/rules/css-design-system.md` |
| Database Migrations | `docs/rules/database-migrations.md` |
| Shipping/Payment | `docs/rules/shipping-payment.md` |
| MonetaryUnit | `docs/rules/monetary-unit.md` |
| API Routes | `docs/reference/api-routes.md` |
| Table Reference | `docs/reference/table-reference.md` |
| Column Renames | `docs/reference/column-renames.md` |
| Removed Features | `docs/reference/removed-features.md` |
| Table Rename Method | `docs/standards/TABLE_RENAME_METHODOLOGY.md` |
| Blade Display Only | `docs/plans/BLADE_DISPLAY_ONLY_PLAN.md` |
| **Data Flow Policy** | `docs/rules/DATA_FLOW_POLICY.md` |
| **Event-Driven Architecture** | `docs/plans/EVENT_DRIVEN_ARCHITECTURE_PLAN.md` |
