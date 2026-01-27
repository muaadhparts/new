# Cart Data Handling Architecture

## The Principle

**Storage layer alone is responsible for any data transformation.**

All other layers (business logic, controllers, views) work with cart as a structured logical entity only, without any knowledge about how it's stored or encoded.

## Layer Responsibilities

| Layer | Responsibility | Example |
|-------|----------------|---------|
| **Model (Storage Layer)** | JSON encoding/decoding | `'cart' => 'array'` cast |
| **Controllers** | Pass/read arrays directly | `$purchase->cart = $cartArray;` |
| **Services** | Work with structured arrays | `$items = $purchase->getCartItems();` |
| **Views** | Display data from model methods | `@foreach ($purchase->getCartItems() as $item)` |

## Cart Data Structure

```php
[
    'totalQty' => int,
    'totalPrice' => float,
    'items' => [
        'cart_key_1' => [
            'user_id' => int,          // merchant_id
            'merchant_item_id' => int,
            'qty' => int,
            'price' => float,
            'item' => [                 // catalog item snapshot
                'id' => int,
                'name' => string,
                'photo' => string,
                ...
            ],
            'size' => string|null,
            'color' => string|null,
            'keys' => string|null,
            'values' => string|null,
        ],
        ...
    ]
]
```

## Model Methods (Purchase & MerchantPurchase)

| Method | Description |
|--------|-------------|
| `getCartItems()` | Get all cart items as array |
| `getCartTotalQty()` | Get total quantity |
| `getCartTotalPrice()` | Get total price |
| `getCartItemsByMerchant()` | Group items by merchant_id |
| `getCartItemsForMerchant($id)` | Get items for specific merchant |
| `getMerchantIdsFromCart()` | Get array of merchant IDs |
| `hasItemsForMerchant($id)` | Check if merchant has items |

## Correct Approach

```php
// Controllers: Pass arrays directly
$purchase->cart = $cartArray;  // Model cast encodes automatically
$purchase->save();

// Reading cart data: Use model accessor methods
$items = $purchase->getCartItems();
$merchantItems = $purchase->getCartItemsForMerchant($merchantId);
$grouped = $purchase->getCartItemsByMerchant();
$merchantIds = $purchase->getMerchantIdsFromCart();

// Views: Use model methods
@foreach ($purchase->getCartItems() as $item)
    {{ $item['item']['name'] }}
@endforeach
```

## Why This Matters

1. **Single Source of Truth**: Only models know about JSON storage format
2. **Maintainability**: Change storage format in one place
3. **Testability**: Controllers/services work with arrays only
4. **No Double-Encoding**: Previous bug caused by manual `json_encode()` + model cast
