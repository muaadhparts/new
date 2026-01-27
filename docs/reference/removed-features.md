# Removed Features & Dead Code (2026-01-19/20)

**DO NOT recreate, reference, or add fallbacks for any of these removed features!**

## 1. Package/Packaging System - COMPLETELY REMOVED

The packaging feature has been **permanently deleted**. The system was built as if packaging never existed.

**What was removed:**
- `packages` table (dropped via migration)
- `Package` model (`app/Models/Package.php`)
- `PackageController` (Operator, Merchant, API)
- `PackageResource`, `PackageDetailsResource`
- All package views (`operator/package/`, `merchant/package/`)
- All package routes
- Sidebar navigation links for packages

**Services cleaned (packing_cost = 0 always):**
- `CheckoutPriceService`
- `CheckoutDataService`
- `MerchantPriceCalculator`
- `MerchantCheckoutService`
- `MerchantPurchaseCreator`

## 2. Bulk Import Feature - COMPLETELY REMOVED

Catalog item bulk import has been **permanently deleted** from both Operator and Merchant panels.

**What was removed:**
- `app/Http/Controllers/Operator/ImportController.php`
- `app/Http/Controllers/Merchant/ImportController.php`
- Import methods from `CatalogItemController` (both panels)
- `resources/views/operator/catalog-item-import/` (entire folder)
- `resources/views/merchant/catalog-item-import/` (entire folder)
- All import routes from `web.php` and `api.php`
- Sidebar navigation links for import

## 3. Digital Products - REMOVED

The system only supports physical items. Digital product logic has been removed.

**What was removed:**
- `digital.blade.php` files
- Digital product type logic
- `item_type = 'digital'` checks

## 4. Removed Columns from `catalog_items`

These columns were removed because they belong ONLY to `merchant_items`:

| Removed Column | Reason |
|----------------|--------|
| `color` | Merchant-specific, use `merchant_items.color` |
| `size` | Merchant-specific, use `merchant_items.size` |
| `length` | Not needed, was never used |
| `width` | Not needed, was never used |
| `height` | Not needed, was never used |
| `status` | Never existed in schema |
| `policy` | Never existed in schema |
| `features` | Never existed in schema |
| `featured` | Never existed in schema |
| `best` | Never existed in schema |
| `top` | Never existed in schema |
| `big` | Never existed in schema |
| `trending` | Never existed in schema |
| `whole_sell_qty` | Never existed in schema |
| `whole_sell_discount` | Never existed in schema |

**CatalogItem $fillable (correct):**
```php
protected $fillable = [
    'brand_id', 'part_number', 'label_en', 'label_ar', 'attributes', 'name', 'slug',
    'photo', 'thumbnail', 'weight', 'views', 'tags', 'is_meta', 'meta_tag',
    'meta_description', 'youtube', 'measure', 'hot', 'latest', 'sale',
    'is_catalog', 'catalog_id', 'cross_items',
];
```

## 5. Magic `__get` Fallback - REMOVED from CatalogItem

The `__get` magic method that caused confusion between tables was removed. Code must now explicitly access the correct model.

## 6. Naming Changes

| Old Name | New Name | Context |
|----------|----------|---------|
| `physical` | `items` | File names, CSS classes, routes |
| `physical.blade.php` | `items.blade.php` | View files |
| `physical-catalogItem-inputes-wrapper` | `items-catalogItem-inputes-wrapper` | CSS class |
| `/physical` route | `/items` route | URL slugs |

## 7. Homepage Classification System - COMPLETELY REMOVED (2026-01-20)

The entire homepage classification system has been **permanently deleted**.

**Removed Database Columns from `merchant_items`:**

| Column | Purpose (was) |
|--------|---------------|
| `featured` | Featured items section |
| `top` | Top rated items section |
| `big` | Big save items section |
| `trending` | Trending items section |
| `best` | Best sellers section |
| `is_discount` | Deal of the day flag |
| `discount_date` | Deal expiry date |
| `popular` | Legacy popularity flag |
| `is_popular` | Legacy popularity flag (duplicate) |

**Removed Database Columns from `home_page_themes`:**
- All `show_featured_items`, `show_deal_of_day`, `show_top_rated`, `show_big_save`, `show_trending`, `show_best_sellers` columns
- All related `order_*`, `name_*`, `count_*` columns

**Removed Controllers & Methods:**
- `FrontendSettingController@deal`, `@bestSellers`, `@topRated`, `@bigSave`, `@trending`, `@featured`
- All toggle, search, and getMerchants methods for these features
- `CatalogItemController@feature`, `@featuresubmit`

**Removed Views:**
- `operator/frontend-setting/deal.blade.php`
- `operator/frontend-setting/best_sellers_manage.blade.php`
- `operator/frontend-setting/trending_manage.blade.php`
- `operator/frontend-setting/featured_manage.blade.php`
- `operator/frontend-setting/top_rated_manage.blade.php`
- `operator/frontend-setting/big_save_manage.blade.php`
- `operator/catalog-item/highlight.blade.php`
- `frontend/sections/deal-of-day.blade.php`
- `frontend/sections/catalog-item-carousel.blade.php`
- `partials/catalog-item/flash-catalog-item.blade.php`

**Homepage Now Shows Only:**
- Hero Search
- Brands
- Categories
- Blogs
- Newsletter

## Migrations for Removed Features

```
database/migrations/2026_01_18_xxxxxx_remove_color_size_from_catalog_items.php
database/migrations/2026_01_18_203457_remove_dimensions_from_catalog_items.php
database/migrations/2026_01_19_100000_drop_packages_table.php
database/migrations/2026_01_20_200000_remove_homepage_classification_columns_from_merchant_items.php
database/migrations/2026_01_20_200001_remove_classification_columns_from_home_page_themes.php
```

## When You Encounter Dead Code References

If you find code referencing removed features:
1. **DELETE** the dead code completely
2. **DO NOT** add fallbacks or compatibility layers
3. **DO NOT** recreate the removed functionality
4. Update related code to work without the removed feature
