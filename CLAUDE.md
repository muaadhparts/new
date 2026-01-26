# CLAUDE.md

> **Full Documentation:** See `docs/` folder for detailed guides and references.

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

### 6. Blade Display Only
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

### 7. Data Flow Policy (NEW)
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
