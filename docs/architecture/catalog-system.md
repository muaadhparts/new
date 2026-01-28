# Catalog System Architecture

## Multi-Merchant, Branch-First Architecture

- **CatalogItem**: Catalog-level item data (SKU, category, attributes, fitments)
- **MerchantBranch**: Merchant warehouse/branch (location, coordinates, shipping origin)
- **MerchantItem**: Merchant-specific listing (price, stock) - each row is one seller + branch
- **Stock**: Raw inventory data from DBF files per branch/merchant
- CatalogItems have fitments linking them to vehicle trees via `CatalogItemFitment`
- Every MerchantItem MUST belong to a MerchantBranch (enforced by NOT NULL FK)

## Catalog Tree Structure

```
brands (العلامات التجارية)
  └── catalogs (الكتالوجات) [brand_id]
        │
        ├── categories (التصنيفات) [catalog_id, level, parent_id]
        │     ├── Level 1 (parent_id = NULL)
        │     │     └── Level 2 (parent_id = L1.id)
        │     │           └── Level 3 (parent_id = L2.id)
        │     │
        ├── sections (الأقسام) [category_id → categories.id]
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

## Key Relationships

```php
// Brand → Catalogs
Brand::hasMany(Catalog::class);
Catalog::belongsTo(Brand::class);

// Catalog → Categories (3 levels)
Category::belongsTo(Catalog::class);
Category::belongsTo(Category::class, 'parent_id'); // self-referencing

// Section → Category
Section::belongsTo(Category::class, 'category_id');

// Dynamic Parts Tables
// parts_{code}.part_number ↔ catalog_items.part_number
// section_parts_{code} links parts to sections

// Catalog Item → Merchant Items
CatalogItem::hasMany(MerchantItem::class);
MerchantItem::belongsTo(CatalogItem::class);
```

## Catalog Domain Services

Location: `app/Domain/Catalog/Services/`

### CategoryTreeService

Key methods:
- `getDescendantIds()` - Recursive CTE for all child categories
- `getPartsWithMerchantItems()` - Parts available for sale only
- `buildCategoryTree()` - Sidebar navigation tree
- `resolveCategoryHierarchy()` - URL slug resolution

### CatalogItemFilterService

Key methods:
- `getFilterSidebarData()` - Build filter sidebar
- `applyCatalogItemFilters()` - Apply all filters to query
- `getCatalogItemsFromCategoryTree()` - Get items from category

### CatalogItemCardDataBuilder

Key methods:
- `buildCardsFromCatalogItems()` - Build display cards
- `buildCardsFromPaginator()` - Build paginated cards
- `initialize()` - Load favorites for current user

### CompatibilityService

Key methods:
- `getCompatibleCatalogs()` - Find compatible vehicles
- `isCompatibleWith()` - Check part compatibility

## Routes

```php
// New category tree route
/catalog/{brand_slug}/{catalog_slug}/category/{cat1?}/{cat2?}/{cat3?}

// Example:
/catalog/nissan/Y62/category/engine/cooling/radiator
```

## Branch-First Architecture (2026-01-18)

- **MerchantBranch**: Operational unit with location, contact, and shipping origin
- Every `merchant_item` MUST have a `merchant_branch_id` (NOT NULL, FK constraint)
- Same SKU can exist in multiple branches of the same merchant (different stock/price)
- Shipping quotes are calculated from the branch's coordinates
- Cart items include `branch_id` and `branch_name` for grouping

**Key Relationships:**
```
User (Merchant)
  └── MerchantBranch (has address, coordinates)
        └── MerchantItem (price, stock for this branch)
```

**Required Fields on MerchantItem:**
- `merchant_branch_id` - FK to `merchant_branches.id` (REQUIRED)

**Creating Merchant Items:**
- Branch selection is REQUIRED when creating offers
- Conflict check includes branch_id (same SKU + same merchant + same branch = conflict)
