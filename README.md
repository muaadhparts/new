# MUAADH EPC

Multi-Merchant Auto Parts Catalog Platform with AI-assisted OEM/Aftermarket parts lookup.

## Overview

MUAADH EPC is a true **Multi-Merchant** e-commerce platform for automotive parts. It features callout-first search, VIN decoding, and a comprehensive catalog system linking parts to specific vehicle fitments.

### Key Features

- **Multi-Merchant Architecture**: Each merchant manages their own inventory, pricing, and fulfillment
- **Catalog System**: Centralized catalog (`catalog_items`) with merchant-specific listings (`merchant_items`)
- **Dynamic Parts Trees**: Per-catalog parts tables with category hierarchy
- **VIN Decoding**: Vehicle identification and parts lookup
- **Callout Search**: Diagram-based parts identification
- **Multiple Payment Gateways**: Stripe, PayPal, MyFatoorah, and more
- **Shipping Integration**: Tryoto and custom shipping providers
- **Bilingual Support**: Arabic (RTL) and English

## Tech Stack

- **Backend**: Laravel 10, PHP 8.1+
- **Frontend**: Livewire 3, Alpine.js, Vite
- **Admin Panel**: Filament 3
- **Database**: MySQL 8.0+
- **Cache**: Redis (optional)

## Installation

```bash
# Clone repository
git clone [repository-url]
cd oldnew

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development server
php artisan serve
npm run dev
```

## Architecture

### Data Model

```
brands
  └── catalogs [brand_id]
        ├── categories [catalog_id, level, parent_id]
        │     └── Level 1 → Level 2 → Level 3
        ├── sections [category_id]
        └── Dynamic Tables:
              ├── parts_{catalog_code}
              └── section_parts_{catalog_code}

catalog_items (catalog data only - NO prices)
      ↓
merchant_items (prices, stock, merchant-specific data)
      ↓
purchases → merchant_purchases (per-merchant breakdown)
```

### Key Principles

1. **Merchant Context Required**: Every transaction requires a merchant_id
2. **Separation of Concerns**: `catalog_items` = catalog data, `merchant_items` = commercial data
3. **Platform as Operator**: Platform provides default payment/shipping when merchant doesn't have their own
4. **Accounting Ledger**: Full financial tracking per merchant with commission calculation

### Directory Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # Platform administration
│   ├── Front/          # Customer storefront
│   ├── Merchant/       # Merchant dashboard
│   ├── User/           # Customer account
│   └── Api/            # REST API endpoints
├── Models/             # Eloquent models
├── Services/           # Business logic
│   ├── MerchantCartService.php
│   ├── CheckoutPriceService.php
│   ├── CategoryTreeService.php
│   └── ...
└── Helpers/            # Global helper functions

resources/views/
├── frontend/           # Customer-facing views
├── merchant/           # Merchant dashboard views
├── admin/              # Admin panel views
└── layouts/            # Layout templates
```

## Development

### Commands

```bash
# Development
npm run dev              # Vite dev server
php artisan serve        # Laravel server

# Build
npm run build            # Production build
npm run build:prod       # Build with PurgeCSS

# Cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Stock Management
php artisan stock:full-refresh
php artisan products:update-price

# Testing
php artisan test
```

### CSS Guidelines

- Use Design System classes (`.m-btn`, `.m-card`, etc.)
- Add new styles to `muaadh-system.css` only
- Never use hardcoded colors - use CSS variables
- See `CLAUDE.md` for full CSS rules

## Documentation

- `CLAUDE.md` - Development guidelines and architecture reference
- `DESIGN_SYSTEM_POLICY.md` - CSS and theming rules
- `docs/standards/` - Coding standards and methodologies

## Terminology

| Term | Description |
|------|-------------|
| `merchant` | Seller on the platform |
| `catalog_item` | Item in the catalog (no pricing) |
| `merchant_item` | Merchant's listing of a catalog item (with pricing) |
| `purchase` | Customer order |
| `courier` | Delivery personnel |

## Quick Reference - Database Tables

### Core Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `catalog_items` | Centralized catalog (NO prices) | `part_number`, `name`, `slug`, `photo` |
| `merchant_items` | Merchant listings (ALL commercial data) | `catalog_item_id`, `user_id`, `price`, `stock`, `status` |
| `purchases` | Customer orders | `user_id`, `cart` (JSON), `total`, `status` |
| `merchant_purchases` | Per-merchant order breakdown | `purchase_id`, `merchant_id`, `subtotal` |

### Catalog Tree

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `brands` | Vehicle brands (Nissan, Toyota) | `name`, `slug`, `status` |
| `catalogs` | Vehicle catalogs per brand | `brand_id`, `code`, `name`, `slug` |
| `categories` | 3-level category hierarchy | `catalog_id`, `parent_id`, `level`, `slug` |
| `sections` | Links categories to parts | `category_id`, `full_code` |

### Dynamic Tables (per catalog)

| Table Pattern | Purpose |
|---------------|---------|
| `parts_{catalog_code}` | Parts data for specific catalog |
| `section_parts_{catalog_code}` | Links parts to sections |

### Users & Merchants

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | All users (customers, merchants, admins) | `is_merchant`, `is_admin` |
| `favorite_sellers` | User favorites | `user_id`, `merchant_item_id` |
| `catalog_reviews` | CatalogItem reviews | `catalog_item_id`, `user_id`, `rating` |

### Payments & Shipping

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `payment_gateways` | Available payment methods | `user_id` (0 = platform) |
| `shipping_services` | Shipping providers | `user_id` (0 = platform) |
| `shipment_trackings` | Unified shipment tracking | `purchase_id`, `merchant_id`, `status` |

### Accounting

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `merchant_balances` | Merchant wallet/balance | `user_id`, `balance` |
| `merchant_transactions` | Financial movements | `user_id`, `amount`, `type` |
| `platform_commissions` | Commission settings per merchant | `merchant_id`, `rate` |

## License

Proprietary - All rights reserved.
