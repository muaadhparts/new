# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## CRITICAL: Theme & CSS Rules (READ FIRST)

**Before modifying ANY UI/CSS/Blade file, you MUST follow these rules:**

### FORBIDDEN - Will fail build:
- Hardcoded HEX colors: #fff, #333, #006c35
- Hardcoded RGB/RGBA: rgb(255,255,255), rgba(0,0,0,0.5)
- Inline style colors: style="color: #333; background: #fff"
- Adding CSS to style.css (FROZEN - legacy only)

### REQUIRED - Always use:
- CSS Variables: var(--text-primary), var(--action-primary)
- Design System classes: .m-btn, .m-card, .m-alert, .m-input
- Utility classes: .m-bg-*, .m-text-*, .m-border-*
- Add new CSS ONLY to: muaadh-system.css

### CSS Files:
- muaadh-system.css  -> Design System (ALL new styles here)
- theme-colors.css   -> Theme variables (auto-generated)
- rtl.css            -> RTL support
- style.css          -> FROZEN legacy (do not modify)

### Build Commands:
- `npm run lint:theme`  -> Check for color violations
- `npm run build`       -> Lint + Build (fails on violations)
- `npm run build:prod`  -> Lint + PurgeCSS + Build

**Quick Reference:**
```css
/* WRONG */
color: #333;
background: #006c35;
border: 1px solid #ddd;

/* CORRECT */
color: var(--text-primary);
background: var(--action-primary);
border: 1px solid var(--border-default);
```

**See:** `DESIGN_TOKENS_REFERENCE.md` for full token/class list.

---

## Project Overview

MUAADH EPC is an AI-assisted OEM/Aftermarket auto parts catalog with callout-first search. Built with Laravel 10, Livewire 3, and Filament 3 admin panel.

## Common Commands

```bash
# Development server
npm run dev              # Start Vite dev server
php artisan serve        # Start Laravel dev server

# Build
npm run build            # Production build

# Cache management
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Stock management (vendor-specific inventory)
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

## Architecture

### Catalog System (Multi-Merchant)
- **CatalogItem**: Catalog-level item data (SKU, category, attributes, fitments)
- **MerchantItem**: Merchant-specific listing (price, stock, user_id) - each row is one seller
- **Stock**: Raw inventory data from DBF files per branch/merchant
- CatalogItems have fitments linking them to vehicle trees via `CatalogItemFitment`

### Key Models
- `Purchase` - stores cart as JSON array, supports multiple merchants per purchase
- `MerchantPurchase` - per-merchant breakdown of purchases
- `FavoriteSeller` - user favorites/wishlist
- `CatalogReview` - product reviews
- `ShipmentStatusLog` - Tryoto shipping integration tracking
- `Callout` - diagram callout data for parts lookup
- `VinDecodedCache` - cached VIN decode results

### Services (`app/Services/`)
- `TryotoService` / `TryotoLocationService` - Shipping API integration
- `ShippingCalculatorService` - Shipping cost calculations
- `CheckoutPriceService` - Checkout pricing logic
- `MerchantCartService` - Multi-merchant cart management
- `CompatibilityService` / `AlternativeService` - CatalogItem alternatives and fitment data
- `NissanTokenService` - Nissan parts API authentication
- `GoogleMapsService` - Geocoding for address validation

### Controllers Structure
- `Admin/` - Admin panel controllers (purchases, catalog items, merchants, shipping)
- `Front/` - Customer-facing controllers (catalog, cart, checkout, search)
- `User/` - Authenticated user area (profile, purchases, favorites)
- `Merchant/` - Merchant dashboard controllers
- `Api/` - REST API endpoints (auth, catalog items, shipping)

### Helpers (`app/Helpers/helper.php`)
Global helper functions loaded via composer autoload:
- `getLocalizedCatalogItemName()` - Returns AR/EN catalog item name based on locale
- `favoriteCheck()` / `merchantFavoriteCheck()` - Favorite status helpers
- `getMerchantDisplayName()` - Merchant display name with quality brand

### Payment Gateways
Multiple payment integrations: Stripe, PayPal, Razorpay, Authorize.net, Instamojo, Mercadopago, Mollie, MyFatoorah

### Stock Import System
DBF file import for inventory sync:
- Config: `config/stock.php` - field mappings, encoding (CP1256)
- Unique by: `fitem` + `fbranch`
- Commands in `app/Console/Commands/` for download, import, aggregation
- Stock updates stored in `merchant_stock_updates` table

### Scheduled Tasks (Kernel.php)
- Nissan token refresh: every 5 minutes
- Stock full refresh: daily at 02:00
- Shipment updates: every 30 minutes + twice daily comprehensive
- Performance reports: weekly on Sunday
- Telescope pruning: daily

## Frontend

### Views Structure
- `resources/views/frontend/` - Customer storefront
- `resources/views/admin/` - Admin panel views
- `resources/views/vendor/` - Vendor dashboard
- `resources/views/catalog/` - Catalog/callout views
- Layout: `layouts.front3` (Livewire default)

### Asset Build
Using Vite with laravel-vite-plugin. Entry points:
- `resources/css/app.css`
- `resources/js/app.js`

## API Routes

### Web API endpoints (`routes/web.php`)
- `/api/search/part` - Part number search
- `/api/search/vin` - VIN decode/search
- `/api/vehicle/suggestions` - Vehicle search autocomplete
- `/modal/catalog-item/{key}` - Catalog item quick view modal

### REST API (`routes/api.php`)
- `/api/specs/*` - Specification filtering
- `/api/catalog-item/alternatives/{sku}` - Catalog item alternatives
- `/api/catalog-item/compatibility/{sku}` - Fitment data
- `/api/shipping/tryoto/*` - Shipping options
- `/api/user/*` - User authentication and profile
- `/api/front/purchasetrack` - Purchase tracking

## Database

MySQL database with the following structure:

### Key Tables (New Naming Convention)
- `catalog_items` - Product catalog (SKU, name, attributes)
- `merchant_items` - Merchant-specific listings (price, stock per merchant)
- `purchases` - Customer orders/purchases
- `merchant_purchases` - Per-merchant breakdown of purchases
- `favorite_sellers` - User favorites/wishlist
- `catalog_reviews` - Product reviews
- `catalog_events` - Notifications/events

### Folder Structure
- `database/migrations/` - Laravel migrations (ALL changes here)
- `database/schema/` - SQL exports for reference only (READ-ONLY)

### Terminology (IMPORTANT)
| Old Term | New Term |
|----------|----------|
| `order` | `purchase` |
| `vendor` | `merchant` |
| `product` | `catalog_item` / `item` |
| `wishlist` | `favorite` |

See "Database Schema & Migrations Rules" section for detailed guidelines.

## Testing

PHPUnit configured with separate Unit and Feature test suites. Test database uses array drivers for cache/session/mail during testing.

## Design System & CSS Architecture (CRITICAL)

The project uses a **Design System** with strict CSS organization. Read `DESIGN_SYSTEM_POLICY.md` for full details.

### CSS File Structure & Load Order

Files MUST be loaded in this exact order:

```html
1. bootstrap.min.css     <!-- Framework base -->
2. External libraries    <!-- slick, nice-select, etc. -->
3. style.css             <!-- Legacy styles (FROZEN - no new code) -->
4. muaadh-system.css     <!-- Design System (ALL NEW STYLES HERE) -->
5. rtl.css               <!-- RTL support (if Arabic) -->
6. theme-colors.css      <!-- Admin Panel overrides (ALWAYS LAST) -->
```

Location: `public/assets/front/css/`

### NEW Components: Use `m-` Prefix

For ALL new CSS, use the Design System in `muaadh-system.css`:

```html
<!-- CORRECT - Design System -->
<button class="m-btn m-btn--primary">Save</button>
<button class="m-btn m-btn--danger">Delete</button>
<button class="m-btn m-btn--success m-btn--lg">Approve</button>

<span class="m-badge m-badge--paid">Paid</span>
<span class="m-badge m-badge--pending">Pending</span>

<div class="m-card">
    <div class="m-card__header">Title</div>
    <div class="m-card__body">Content</div>
</div>
```

### Legacy Classes (Still Work, But Don't Add New)

```html
<!-- LEGACY - Still works, don't use for new code -->
<button class="template-btn">Primary</button>
<button class="btn btn-primary">Primary</button>
<button class="muaadh-btn">Primary</button>
```

### Rules for CSS Changes

**DO:**
- Use `m-` prefix classes from `muaadh-system.css` for new components
- Use semantic tokens: `var(--action-primary)`, `var(--action-danger)`
- Add new components ONLY to `muaadh-system.css`
- Clear cache after CSS changes: `php artisan cache:clear && php artisan view:clear`

**DO NOT:**
- NEVER add CSS to `style.css` (frozen legacy)
- NEVER use hardcoded colors like `#c3002f`
- NEVER use inline `style=""` for colors
- NEVER load CSS after `theme-colors.css`
- NEVER duplicate selectors

### Variable Hierarchy

```css
/* Level 1: Theme (Admin Panel) */
--theme-primary: #7c3aed;

/* Level 2: Semantic (Design System) */
--action-primary: var(--theme-primary);
--action-danger: var(--theme-danger);

/* Level 3: Component */
--btn-primary-bg: var(--action-primary);
```

### Component Inventory

| Class | Purpose | Color |
|-------|---------|-------|
| `.m-btn--primary` | Main action (Save) | `--action-primary` |
| `.m-btn--danger` | Destructive (Delete) | `--action-danger` |
| `.m-btn--success` | Positive (Approve) | `--action-success` |
| `.m-btn--warning` | Caution (Edit) | `--action-warning` |
| `.m-badge--paid` | Payment confirmed | green |
| `.m-badge--pending` | Awaiting action | yellow |
| `.m-badge--cancelled` | Cancelled | red |

### Theme Builder

Admin can change all colors from: **Admin Panel -> Settings -> Theme Builder**

### Page Background Convention (FINAL)

All frontend pages MUST follow this background system:

```
Level 1: PAGE WRAPPER (.m-page or .muaadh-page-wrapper)
  - Full page background color

  Level 2: SECTIONS (.m-page__section or .muaadh-section)
    - Transparent by default (inherits from page)

    Level 3: CARDS/CONTENT (.m-card, .m-content-box)
      - White/elevated backgrounds for content areas
```

**New Pages (Preferred):**
```html
<div class="m-page m-page--gray">
    <section class="m-page__section">
        <div class="container">
            <div class="m-card">Content</div>
        </div>
    </section>
</div>
```

**Legacy Pages (Still Works):**
```html
<div class="gs-*-wrapper muaadh-section-gray">
    <div class="container">Content</div>
</div>
```

**Background Variants:**

| Class | Description |
|-------|-------------|
| `.m-page--gray` / `.muaadh-section-gray` | Gray background (default for most pages) |
| `.m-page--white` | White background (special pages) |
| `.m-page--gradient` | Gradient background (landing pages) |

**Rules:**
- ALL content sections after breadcrumb MUST have gray background
- Inner sections are transparent (inherit from parent wrapper)
- Cards/content boxes use white background
- Never add background directly to `<section>` - use wrapper class

### New/Modified Page Checklist

When creating a NEW page or modifying an EXISTING page, follow this checklist:

1. Layout: @extends('layouts.front')
2. Breadcrumb section with bg-class
3. Main content wrapped with muaadh-section-gray
4. No inline style="" for colors
5. Use m-* classes for new components
6. Cards use white background (m-card or bg-white)
7. Clear cache after changes

**Standard Page Template:**
```blade
@extends('layouts.front')

@section('content')
    {{-- 1. Breadcrumb Section --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Page Title')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="#">@lang('Current Page')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Main Content with Gray Background --}}
    <div class="gs-page-wrapper muaadh-section-gray">
        <div class="container py-4">
            {{-- Your content here --}}
            <div class="m-card">
                <div class="m-card__body">
                    Content goes here
                </div>
            </div>
        </div>
    </div>
@endsection
```

**Quick Fix for Missing Background:**
If a page is missing gray background, add `muaadh-section-gray` to the main wrapper:
```blade
{{-- Before --}}
<div class="gs-something-wrapper">

{{-- After --}}
<div class="gs-something-wrapper muaadh-section-gray">
```

**After Any Page Change:**
```bash
php artisan view:clear && php artisan cache:clear
```

### Reference Documentation

- Full policy: `DESIGN_SYSTEM_POLICY.md`
- Theme guide: `THEME_SYSTEM_COMPLETE_GUIDE.md`

---

## Table Rename Methodology

For complete table rename instructions, see: **docs/standards/TABLE_RENAME_METHODOLOGY.md**

**Usage**: When you need to rename a table, request:
> "Rename table from [OLD_NAME] to [NEW_NAME] using the methodology in /docs/standards/TABLE_RENAME_METHODOLOGY.md"

The methodology covers all 9 steps:
1. Database Migration
2. Model Updates
3. Controller Updates
4. Helper Function Updates
5. Route Updates
6. Session Key Updates
7. View Updates
8. JavaScript Updates
9. Permission & Translation Updates

---

## CRITICAL: Database Safety Rules

### ABSOLUTELY FORBIDDEN (even if user requests it):
- DROP DATABASE - delete database
- DROP TABLE (with data) - delete table with data
- TRUNCATE TABLE - empty table
- DELETE FROM table (without WHERE) - delete all data

**WARNING: Even if the user asks to delete the database or a table, DO NOT do it!**

### REQUIRED - When renaming/replacing tables:
1. Create the new table structure
2. Migrate data from old table to new table
3. Rename old table with `_old` suffix (e.g., coupons -> coupons_old)
4. NEVER delete the _old table - keep it for safety/rollback

**Example:**
```sql
-- Step 1: Create new table
CREATE TABLE discount_codes (...);

-- Step 2: Migrate data
INSERT INTO discount_codes SELECT * FROM coupons;

-- Step 3: Rename old table (don't delete!)
RENAME TABLE coupons TO coupons_old;

-- Step 4: Keep coupons_old forever for safety
```

**Why keep `_old` tables?**
- Data recovery if something goes wrong
- Reference for data migration verification
- Audit trail for changes
- Zero-risk approach to schema changes

---

## CRITICAL: Database Schema & Migrations Rules

### Folder Structure

```
database/
├── migrations/     # ← ALL database changes go here (Laravel migrations)
└── schema/         # ← REFERENCE ONLY (SQL exports for documentation)
```

### `database/schema/` - READ-ONLY REFERENCE

⚠️ **This folder is for REFERENCE ONLY!**

**DO NOT:**
- Use schema files to create tables
- Import SQL files into database
- Modify schema files manually

**DO:**
- Read schema files to understand table structure
- Reference column names, types, and indexes
- Use for documentation purposes

### `database/migrations/` - ALL CHANGES HERE

**ALL database modifications MUST use Laravel migrations:**

```bash
# Add new table
php artisan make:migration create_table_name_table

# Add column to existing table
php artisan make:migration add_column_to_table_name

# Modify column
php artisan make:migration modify_column_in_table_name

# Run migrations
php artisan migrate
```

### Migration Examples

**Adding a new column:**
```php
Schema::table('purchases', function (Blueprint $table) {
    $table->string('new_column')->nullable()->after('existing_column');
});
```

**Creating a new table:**
```php
Schema::create('new_table', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

### Updating Schema Reference

After significant database changes, re-export schemas for reference:

```bash
php artisan schema:dump
# Or use custom export script
```

---

## CRITICAL: Multi-Merchant Architecture Governance

### Core Principle
This project operates as a **TRUE Multi-Merchant** system, NOT a superficial marketplace. Merchant context is MANDATORY at every layer.

### Mandatory Rules

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

### Accounting System

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

### Couriers (Delivery)
Couriers are part of the **financial chain**, not just logistics:
- Commission tracking
- Settlement cycles
- Performance metrics tied to payments

---

## CRITICAL: Terminology Enforcement

### Forbidden Terms → Required Terms

| FORBIDDEN (Old) | REQUIRED (New) | Notes |
|-----------------|----------------|-------|
| `vendor` | `merchant` | All code, variables, routes |
| `product` | `catalog_item` | Or just `item` |
| `order` | `purchase` | Tables, models, routes |
| `riders` | `couriers` | Delivery personnel |
| `admin` | `operator` | Platform owner role |
| `category/subcategory/childcategory` | `NewCategory` (levels) | See tree structure below |

### Code Cleanup Rules
- Any file/function with old names → **rename immediately**
- No fallbacks to old names
- No compatibility shims
- Deep fixes only, no surface patches

---

## CRITICAL: Catalog Tree Structure (NEW)

### The Correct Structure

```
brands (العلامات التجارية)
  └── catalogs (الكتالوجات) [brand_id]
        │
        ├── newcategories (التصنيفات) [catalog_id, level, parent_id]
        │     ├── Level 1 (parent_id = NULL)
        │     │     └── Level 2 (parent_id = L1.id)
        │     │           └── Level 3 (parent_id = L2.id)
        │     │
        ├── sections (الأقسام) [category_id → newcategories.id]
        │     │
        │     └── Dynamic Tables (per catalog):
        │           ├── parts_{catalog_code}
        │           └── section_parts_{catalog_code}
        │                 │
        │                 ↓ (part_number)
        │
        catalog_items (الأصناف - كتالوج صرف)
              │
              ↓ (catalog_item_id)
        │
        merchant_items (عروض التجار - كل شيء تجاري هنا)
```

### Key Relationships

```php
// Brand → Catalogs
Brand::hasMany(Catalog::class);
Catalog::belongsTo(Brand::class);

// Catalog → Categories (3 levels)
NewCategory::belongsTo(Catalog::class);
NewCategory::belongsTo(NewCategory::class, 'parent_id'); // self-referencing

// Section → Category
Section::belongsTo(NewCategory::class, 'category_id');

// Dynamic Parts Tables
// parts_{code}.part_number ↔ catalog_items.part_number
// section_parts_{code} links parts to sections

// Catalog Item → Merchant Items
CatalogItem::hasMany(MerchantItem::class);
MerchantItem::belongsTo(CatalogItem::class);
```

### Service: NewCategoryTreeService

Location: `app/Services/NewCategoryTreeService.php`

Key methods:
- `getDescendantIds()` - Recursive CTE for all child categories
- `getPartsWithMerchantItems()` - Parts available for sale only
- `buildCategoryTree()` - Sidebar navigation tree
- `resolveCategoryHierarchy()` - URL slug resolution

### Routes

```php
// New category tree route
/catalog/{brand_slug}/{catalog_slug}/category/{cat1?}/{cat2?}/{cat3?}

// Example:
/catalog/nissan/Y62/category/engine/cooling/radiator
```

### FORBIDDEN Old Structure

```
❌ categories (old)
❌ subcategories (old)
❌ childcategories (old)
❌ products (old - use catalog_items)
❌ products.price (old - prices in merchant_items only)
```

---

## CRITICAL: Development Rules

### 1. No New Files Unless Necessary
- Edit existing files/functions
- Delete unused old files
- No duplicate functionality

### 2. No Logic in Views
- All processing via API/Controllers/Services
- Views are for display only
- No queries in Blade files

### 3. Centralized Processing
- Shared logic goes in Services
- No copy-paste across controllers
- DRY principle strictly enforced

### 4. Final Fixes Only
- No half-fixes or temporary solutions
- No surface patches
- Deep, complete resolution required

### 5. Clean Code
- Remove unused code immediately
- No commented-out legacy code
- No fallback compatibility layers

### 6. Legacy Cleanup
When encountering old table/column names:
```php
// WRONG - Don't add fallback
$product = Product::find($id); // ❌

// RIGHT - Fix completely
$catalogItem = CatalogItem::find($id); // ✓
```

---

## Quick Reference: What Lives Where

| Data Type | Table | Notes |
|-----------|-------|-------|
| Item catalog info | `catalog_items` | SKU, name, photos, specs |
| Item pricing/stock | `merchant_items` | Per-merchant, all commercial data |
| Customer orders | `purchases` | Main order record |
| Per-merchant breakdown | `merchant_purchases` | Split by merchant |
| Categories | `newcategories` | 3-level hierarchy per catalog |
| Parts data | `parts_{code}` | Dynamic per catalog |
| User favorites | `favorite_sellers` | Wishlist |
| Reviews | `catalog_reviews` | Product reviews |

---

## Column Renames (2026-01-08)

### Renamed Columns

| Table | Old Column | New Column |
|-------|------------|------------|
| `purchases` | `order_note` | `purchase_note` |
| `purchases` | `riders` | `couriers` |
| `delivery_couriers` | `order_amount` | `purchase_amount` |
| `rewards` | `order_amount` | `purchase_amount` |
| `users` | `admin_commission` | `operator_commission` |

### Renamed Indexes & Constraints

| Table | Old Name | New Name |
|-------|----------|----------|
| `catalog_item_clicks` | `product_clicks_merchant_product_id_index` | `catalog_item_clicks_merchant_item_id_index` |
| `merchant_items` | `mi_product_type` | `mi_item_type` |
| `merchant_credentials` | `vendor_service_key_env_unique` | `merchant_service_key_env_unique` |
| `merchant_credentials` | `vendor_credentials_user_id_index` | `merchant_credentials_user_id_index` |
| `merchant_credentials` | `vendor_credentials_service_name_index` | `merchant_credentials_service_name_index` |
| `merchant_credentials` | `vendor_credentials_user_id_foreign` | `merchant_credentials_user_id_foreign` |
| `merchant_stock_updates` | `vendor_stock_updates_user_id_index` | `merchant_stock_updates_user_id_index` |
| `merchant_stock_updates` | `vendor_stock_updates_status_index` | `merchant_stock_updates_status_index` |
| `merchant_stock_updates` | `vendor_stock_updates_update_type_index` | `merchant_stock_updates_update_type_index` |
| `merchant_stock_updates` | `vendor_stock_updates_user_id_foreign` | `merchant_stock_updates_user_id_foreign` |

### Migration Files

```
database/migrations/2026_01_08_100001_rename_legacy_columns_to_new_names.php
database/migrations/2026_01_08_100002_rename_legacy_indexes_to_new_names.php
```

Run migrations: `php artisan migrate`
