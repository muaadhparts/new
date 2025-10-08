# Product Fitments Implementation Summary

## Overview
This document outlines the changes made to use `product_fitments` as the source of truth for the vehicle tree, without modifying any routes.

## Database Structure
The `product_fitments` table structure:
- `product_id` (FK to products)
- `category_id` (FK to categories)
- `subcategory_id` (FK to subcategories)
- `childcategory_id` (FK to childcategories)
- `rol` (role/level indicator)
- `beginYear` (year the product fits, used for sorting)

## Changes Made

### 1. Models Created/Updated

#### New Model: `ProductFitment.php`
- Location: `app/Models/ProductFitment.php`
- Relationships:
  - `belongsTo(Product::class)`
  - `belongsTo(Category::class)`
  - `belongsTo(Subcategory::class)`
  - `belongsTo(Childcategory::class)`

#### Updated Model: `Product.php`
- Added `fitments()` relationship: `hasMany(ProductFitment::class)`

### 2. Controller Updates

#### `CatalogController.php` (app/Http/Controllers/Front/CatalogController.php)

**Tree Data Source (Lines 111-144):**
- Changed from filtering by `products.category_id/subcategory_id/childcategory_id` to using `EXISTS` subqueries against `product_fitments`
- For category filtering:
  ```php
  whereExists(function ($exists) use ($cat) {
      $exists->selectRaw(1)
          ->from('product_fitments')
          ->whereColumn('product_fitments.product_id', 'products.id')
          ->where('product_fitments.category_id', $cat->id);
  })
  ```
- Same pattern applied for subcategory and childcategory filtering

**Store Filter (Lines 187-190):**
- Added new "Store by:" filter that accepts vendor ID (integer)
- Filter: `?store={vendor_id}`
- Implementation: `where('merchant_products.user_id', (int) $request->store)`

**Sort by beginYear (Lines 192-217):**
- Added two new sort options: `latest_product` and `oldest_product`
- Implementation uses LEFT JOIN with subquery:
  ```php
  leftJoin(\DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
      'pf_max.product_id', '=', 'merchant_products.product_id')
      ->orderBy('pf_max.max_year', 'desc') // or 'asc' for oldest
  ```
- `latest_product`: Orders by MAX(beginYear) DESC
- `oldest_product`: Orders by MAX(beginYear) ASC
- Price sorting remains unchanged

**Added Import:**
- `use Illuminate\Support\Facades\DB;` for raw queries

### 3. Routing/Slugs
- No changes to `products.category_id`, `products.subcategory_id`, `products.childcategory_id`
- These columns remain for routing and slug generation only
- All product listing now uses `product_fitments` as source of truth

## Usage Examples

### Filter by Category Tree
```
GET /catalog/vehicle-category/sub-category/child-category
```
Now shows only products with matching records in `product_fitments`.

### Store by Vendor ID
```
GET /catalog/some-category?store=123
```
Filters to show only products from vendor with ID 123.

### Sort by Product Year
```
GET /catalog/some-category?sort=latest_product
GET /catalog/some-category?sort=oldest_product
```
- `latest_product`: Shows products with highest beginYear first
- `oldest_product`: Shows products with lowest beginYear first

## Testing

### Test File Created
Location: `tests/Feature/ProductFitmentFilterTest.php`

Tests verify:
1. **Product Fitments Filter**: Only products with matching `product_fitments` records appear in listings
2. **Store by Vendor Filter**: Filtering by `store` parameter correctly filters by vendor ID
3. **Sort by beginYear**: Latest/Oldest sorting works correctly based on `beginYear` values

### Manual Testing Checklist
- [ ] Navigate to category pages - verify only products with `product_fitments` records show
- [ ] Test subcategory pages - verify filtering works
- [ ] Test childcategory pages - verify filtering works
- [ ] Use `?store={vendor_id}` parameter - verify vendor filtering
- [ ] Use `?sort=latest_product` - verify products ordered by newest year
- [ ] Use `?sort=oldest_product` - verify products ordered by oldest year
- [ ] Verify price sorting still works (`?sort=price_asc` and `?sort=price_desc`)

## Database Queries
The implementation uses efficient EXISTS subqueries and LEFT JOINs:
- EXISTS queries are fast and use indexes on `product_fitments`
- MAX(beginYear) subquery is computed once and joined
- All filters can be combined (category + store + sort)

## Notes
- `products.category_id/subcategory_id/childcategory_id` columns are kept for routing only
- Do not modify their values or fill logic
- `beginYear` from `product_fitments` is exposed for display/sorting
- All existing filters (price, vendor, quality, search) continue to work
- Routes remain unchanged
