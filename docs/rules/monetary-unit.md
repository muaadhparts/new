# MonetaryUnit Architecture

**SINGLE SOURCE OF TRUTH: `MonetaryUnitService`**

All monetary unit operations MUST go through the centralized service. NO exceptions.

## Who Can Access MonetaryUnit Model Directly?

| Layer | Direct Access | Must Use Service |
|-------|--------------|------------------|
| `MonetaryUnitService` | Yes | - |
| Other Services | No | `monetaryUnit()` |
| Controllers | No | `monetaryUnit()` |
| Views (Blade) | No | `$curr` (shared by BaseController) |
| Helpers | No | `monetaryUnit()` |
| Models | No | `monetaryUnit()` |

## Correct Usage

```php
// Get service instance
$service = monetaryUnit();  // Global helper
// OR
$service = app(MonetaryUnitService::class);

// Get current/default monetary unit
$curr = monetaryUnit()->getCurrent();
$default = monetaryUnit()->getDefault();
$byCode = monetaryUnit()->getByCode('SAR');

// Format prices
monetaryUnit()->format($amount);           // Format with sign
monetaryUnit()->convert($amount);          // Convert from base
monetaryUnit()->convertAndFormat($amount); // Convert + format
monetaryUnit()->formatBase($amount);       // Format in base monetary unit

// Get monetary unit info
monetaryUnit()->getSign();    // Current monetary unit sign
monetaryUnit()->getValue();   // Current exchange rate
monetaryUnit()->getCode();    // Current monetary unit code (SAR, USD, etc.)

// For constants (in Services/Models only)
MonetaryUnitService::BASE_MONETARY_UNIT;  // 'SAR'
MonetaryUnitService::SESSION_KEY;         // 'monetary_unit'
```

## Base Controllers Share `$curr`

All base controllers (Front, Merchant, User, Courier, TopUp) now share:
```php
$this->curr = monetaryUnit()->getCurrent();
view()->share('curr', $this->curr);
```

Blade views should use `$curr` directly, NOT Session.
