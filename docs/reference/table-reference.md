# Table Reference

## What Lives Where

| Data Type | Table | Notes |
|-----------|-------|-------|
| Item catalog info | `catalog_items` | SKU, name, photos, specs |
| Item pricing/stock | `merchant_items` | Per-branch, all commercial data |
| Merchant branches | `merchant_branches` | Warehouses, shipping origins |
| Customer orders | `purchases` | Main order record |
| Per-merchant breakdown | `merchant_purchases` | Split by merchant |
| Categories | `categories` | 3-level hierarchy per catalog |
| Parts data | `parts_{code}` | Dynamic per catalog |
| User favorites | `favorite_sellers` | Wishlist |
| Reviews | `catalog_reviews` | Product reviews |

## Key Tables (New Naming Convention)

- `catalog_items` - Product catalog (SKU, name, attributes)
- `merchant_items` - Merchant-specific listings (price, stock per branch) - FK to `merchant_branches`
- `merchant_branches` - Merchant warehouses/branches (location, coordinates, shipping origin)
- `purchases` - Customer orders/purchases
- `merchant_purchases` - Per-merchant breakdown of purchases
- `favorite_sellers` - User favorites/wishlist
- `catalog_reviews` - Product reviews
- `catalog_events` - Notifications/events

## Folder Structure

- `database/migrations/` - Laravel migrations (ALL changes here)
- `database/schema/` - SQL exports for reference only (READ-ONLY)

## Terminology

| Old Term | New Term |
|----------|----------|
| `order` | `purchase` |
| `vendor` | `merchant` |
| `product` | `catalog_item` / `item` |
| `wishlist` | `favorite` |
