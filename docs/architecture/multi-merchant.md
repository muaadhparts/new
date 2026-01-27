# Multi-Merchant Architecture

## Core Principle

This project operates as a **TRUE Multi-Merchant** system, NOT a superficial marketplace. Merchant context is MANDATORY at every layer.

## Mandatory Rules

1. **Merchant Context is Required Everywhere**
   - Every operation MUST have a merchant_id
   - Missing merchant = FAIL immediately (no fallback, no default)
   - Any code without merchant context is a design flaw

2. **Operator (Platform) Role**
   - Operator is **supervisory only** - NO products, NO pricing, NO item ownership
   - Operator provides DEFAULT payment/shipping/packaging when `user_id = 0`
   - Operator transactions are tracked separately in accounting

3. **Data Ownership**
   - `catalog_items` = Pure catalog entity (NO prices, NO stock)
   - `merchant_items` = ALL merchant-specific data (price, stock, status)
   - Never mix catalog data with merchant data

## Accounting System

This is a **ledger system**, not just display reports:

```
Price Source: merchant_items.price
+ Platform Commission (variable per merchant)
+ Tax (if applicable)
+ Shipping (merchant's or platform's)
= Total

Money Flow:
├── If merchant's payment gateway → funds to merchant balance
├── If platform's payment gateway → platform holds, settles later
└── Same logic for shipping companies
```

**Reports show:**
- Total sales per merchant
- Platform commission collected
- Tax collected
- Shipping revenue (whose gateway?)
- Net payable to merchant

**Invoice Rules:**
- Merchant's payment method → Merchant's logo/identity
- Platform's payment method → Platform's logo/identity
- Invoice is a LEGAL document, not decoration

## Couriers (Delivery)

Couriers are part of the **financial chain**, not just logistics:
- Commission tracking
- Settlement cycles
- Performance metrics tied to payments
