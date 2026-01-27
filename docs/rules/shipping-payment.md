# Shipping & Payment Ownership Logic

## The Rule (MANDATORY)

| `user_id` | `operator` | المعنى | English |
|-----------|------------|--------|---------|
| `0` | `0` | **موقف/معطّل** - لا يظهر لأحد | Disabled - not shown to anyone |
| `0` | `merchant_id` | شحنة/بوابة المنصة مُفعّلة لتاجر معين | Platform-provided, enabled for specific merchant |
| `merchant_id` | `0` | شحنة/بوابة خاصة بالتاجر (أضافها بنفسه) | Merchant's own (added by merchant) |

## How `forMerchant()` Scope Works

```php
public function scopeForMerchant(Builder $query, int $merchantId): Builder
{
    return $query
        ->where('status', 1)
        ->where(function ($q) use ($merchantId) {
            // 1. Merchant's own (user_id = merchantId)
            $q->where('user_id', $merchantId)
            // 2. OR Platform-provided for this merchant (user_id = 0 AND operator = merchantId)
            ->orWhere(function ($q2) use ($merchantId) {
                $q2->where('user_id', 0)
                   ->where('operator', $merchantId);
            });
        })
        ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
}
```

## COD (Cash on Delivery) Special Rule

**COD ownership follows SHIPPING ownership, NOT payment gateway table:**

| Shipping Type | COD Owner |
|---------------|-----------|
| Merchant's own | Merchant (payment_owner_id = merchant) |
| Platform-provided | Platform (payment_owner_id = 0) |
| Courier (مندوب) | Platform (payment_owner_id = 0) |

**Code location:** `MerchantPurchaseCreator.php` lines 275-290

```php
if ($isCod) {
    if ($isCourier) {
        $paymentOwnerId = 0;  // Courier = Platform
    } elseif ($isPlatformShipping || $shippingOwnerId === 0) {
        $paymentOwnerId = 0;  // Platform shipping = Platform
    } else {
        $paymentOwnerId = $shippingOwnerId;  // Merchant shipping = Merchant
    }
}
```

## Correct Usage Patterns

```php
// Getting shipping/payments for a merchant:
$shipping = Shipping::forMerchant($merchantId)->get();
$payments = MerchantPayment::forMerchant($merchantId)->get();

// Operator managing platform resources:
$platformShipping = Shipping::where('user_id', 0)->get();
$platformPayments = MerchantPayment::where('user_id', 0)->get();

// Merchant managing their own resources:
$myShipping = Shipping::where('user_id', $merchantId)->get();
$myPayments = MerchantPayment::where('user_id', $merchantId)->get();
```

## Applies To

- `shippings` table
- `merchant_payments` table
