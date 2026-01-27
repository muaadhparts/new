# Project Overview

MUAADH EPC is an AI-assisted OEM/Aftermarket auto parts catalog with callout-first search. Built with Laravel 10, Livewire 3, and Filament 3 admin panel.

## Architecture

The project follows **Domain-Driven Design (DDD)** with all business logic organized in `app/Domain/`:

```
app/Domain/
├── Accounting/     # Financial ledger, settlements, reports
├── Catalog/        # Products, categories, brands, fitments
├── Commerce/       # Cart, checkout, purchases
├── Identity/       # Users, merchants, couriers, auth
├── Merchant/       # Merchant items, branches, settings
├── Platform/       # Settings, languages, currencies
└── Shipping/       # Shipping providers, tracking, locations
```

## Common Commands

```bash
# Development server
npm run dev              # Start Vite dev server
php artisan serve        # Start Laravel dev server

# Build
npm run build            # Production build
npm run build:prod       # Lint + PurgeCSS + Build

# Cache management
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Stock management (merchant-specific inventory)
php artisan stock:manage full-refresh --user_id=59 --margin=1.3 --branch=ATWJRY
php artisan stock:full-refresh
php artisan products:update-price

# Nissan API token refresh
php artisan nissan:refresh-token

# Shipment status updates (Tryoto integration)
php artisan shipments:update --limit=50

# Tests
php artisan test                    # Run all tests
php artisan test --filter=TestName  # Run specific test
./vendor/bin/phpunit tests/Unit     # Run unit tests only
./vendor/bin/phpunit tests/Feature  # Run feature tests only
```

## Key Models (in `app/Domain/`)

| Domain | Model | Description |
|--------|-------|-------------|
| Commerce | `Purchase` | Stores cart as JSON, supports multiple merchants |
| Commerce | `MerchantPurchase` | Per-merchant breakdown of purchases |
| Merchant | `MerchantBranch` | Warehouse/branch with location and shipping origin |
| Merchant | `MerchantItem` | Merchant-specific listing (always belongs to branch) |
| Identity | `FavoriteSeller` | User favorites/wishlist |
| Catalog | `CatalogReview` | Product reviews |
| Shipping | `ShipmentTracking` | Unified shipment tracking (API + Manual) |
| Catalog | `Callout` | Diagram callout data for parts lookup |
| Catalog | `VinDecodedCache` | Cached VIN decode results |

## Services (in `app/Domain/*/Services/`)

| Domain | Service | Description |
|--------|---------|-------------|
| Shipping | `TryotoService` | Shipping API integration |
| Shipping | `TryotoLocationService` | Location resolution |
| Shipping | `ShippingCalculatorService` | Shipping cost calculations |
| Commerce | `CheckoutPriceService` | Checkout pricing logic |
| Commerce | `MerchantCartManager` | Multi-merchant cart management |
| Catalog | `CompatibilityService` | CatalogItem alternatives and fitment |
| Catalog | `NewCategoryTreeService` | Category tree navigation |
| Platform | `MonetaryUnitService` | Currency conversion and formatting |

## Controllers Structure

- `Operator/` - Platform operator controllers (purchases, catalog items, merchants, shipping)
- `Front/` - Customer-facing controllers (catalog, cart, checkout, search)
- `User/` - Authenticated user area (profile, purchases, favorites)
- `Merchant/` - Merchant dashboard controllers
- `Api/` - REST API endpoints (auth, catalog items, shipping)

## Helpers (`app/Helpers/helper.php`)

Global helper functions loaded via composer autoload:
- `getLocalizedCatalogItemName()` - Returns AR/EN catalog item name based on locale
- `favoriteCheck()` / `merchantFavoriteCheck()` - Favorite status helpers
- `getMerchantDisplayName()` - Merchant display name with quality brand
- `monetaryUnit()` - Currency service accessor

## Payment Gateways

Multiple payment integrations: Stripe, PayPal, Razorpay, Authorize.net, Instamojo, Mercadopago, Mollie, MyFatoorah

## Stock Import System

DBF file import for inventory sync:
- Config: `config/stock.php` - field mappings, encoding (CP1256)
- Unique by: `fitem` + `fbranch`
- Commands in `app/Console/Commands/` for download, import, aggregation
- Stock updates stored in `merchant_stock_updates` table

## Scheduled Tasks (Kernel.php)

- Nissan token refresh: every 5 minutes
- Stock full refresh: daily at 02:00
- Shipment updates: every 30 minutes + twice daily comprehensive
- Performance reports: weekly on Sunday
- Telescope pruning: daily

## Frontend

### Views Structure
- `resources/views/frontend/` - Customer storefront
- `resources/views/operator/` - Operator dashboard
- `resources/views/merchant/` - Merchant dashboard
- `resources/views/catalog/` - Catalog/callout views
- Layout: `layouts.front3` (Livewire default)

### Asset Build
Using Vite with laravel-vite-plugin. Entry points:
- `resources/css/app.css`
- `resources/js/app.js`

## Testing

PHPUnit configured with separate Unit and Feature test suites. Test database uses array drivers for cache/session/mail during testing.

Regression tests in `tests/Regression/` verify Domain services are properly structured.
