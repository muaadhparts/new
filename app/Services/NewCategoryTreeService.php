<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\MerchantItem;
use App\Models\NewCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service for unified catalog tree navigation
 * Uses recursive CTE queries to get all descendant categories
 * and fetches parts with merchant_items (available for sale only)
 */
class NewCategoryTreeService
{
    /**
     * Validate catalog code to prevent SQL injection
     */
    protected function validateCatalogCode(string $code): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $code)) {
            throw new \InvalidArgumentException("Invalid catalog code: {$code}");
        }
    }

    /**
     * Build dynamic table name
     */
    protected function dyn(string $base, string $catalogCode): string
    {
        $this->validateCatalogCode($catalogCode);
        return strtolower("{$base}_{$catalogCode}");
    }

    /**
     * Get all descendant category IDs using recursive CTE
     * Includes the selected category itself
     *
     * @param int $categoryId Selected category ID
     * @param int $catalogId Catalog ID for scoping
     * @return array Array of category IDs
     */
    public function getDescendantIds(int $categoryId, int $catalogId): array
    {
        $sql = "
            WITH RECURSIVE category_tree AS (
                SELECT id
                FROM newcategories
                WHERE id = ? AND catalog_id = ?

                UNION ALL

                SELECT nc.id
                FROM newcategories nc
                INNER JOIN category_tree ct ON nc.parent_id = ct.id
                WHERE nc.catalog_id = ?
            )
            SELECT id FROM category_tree
        ";

        $results = DB::select($sql, [$categoryId, $catalogId, $catalogId]);

        return array_map(fn($row) => $row->id, $results);
    }

    /**
     * Get all descendant category IDs for multiple root categories
     *
     * @param array $categoryIds Array of category IDs
     * @param int $catalogId Catalog ID for scoping
     * @return array Array of unique category IDs
     */
    public function getDescendantIdsForMultiple(array $categoryIds, int $catalogId): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        $sql = "
            WITH RECURSIVE category_tree AS (
                SELECT id
                FROM newcategories
                WHERE id IN ({$placeholders}) AND catalog_id = ?

                UNION ALL

                SELECT nc.id
                FROM newcategories nc
                INNER JOIN category_tree ct ON nc.parent_id = ct.id
                WHERE nc.catalog_id = ?
            )
            SELECT DISTINCT id FROM category_tree
        ";

        $params = array_merge($categoryIds, [$catalogId, $catalogId]);
        $results = DB::select($sql, $params);

        return array_map(fn($row) => $row->id, $results);
    }

    /**
     * Get parts from dynamic tables that have merchant_items (available for sale)
     *
     * @param array $categoryIds Array of category IDs to search
     * @param string $catalogCode Catalog code for dynamic table names
     * @param int $perPage Items per page
     * @param int $page Current page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPartsWithMerchantItems(
        array $categoryIds,
        string $catalogCode,
        int $perPage = 12,
        int $page = 1
    ) {
        if (empty($categoryIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page);
        }

        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        // Check if dynamic tables exist
        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page);
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        // Query to get merchant_items that match parts in selected categories
        // Note: catalog_items table uses 'name' for EN and 'label_ar' for AR names
        $query = MerchantItem::query()
            ->select([
                'merchant_items.*',
                'catalog_items.part_number',
                'catalog_items.name as catalog_item_name',
                'catalog_items.label_ar as catalog_item_name_ar', // catalog_items uses label_ar not name_ar
                'catalog_items.slug as catalog_item_slug',
                'catalog_items.photo',
                'catalog_items.thumbnail',
                DB::raw("(SELECT label_en FROM {$partsTable} WHERE part_number = catalog_items.part_number LIMIT 1) as part_label_en"),
                DB::raw("(SELECT label_ar FROM {$partsTable} WHERE part_number = catalog_items.part_number LIMIT 1) as part_label_ar"),
            ])
            ->with(['catalogItem', 'user', 'qualityBrand']) // Eager load relationships for view
            ->join('catalog_items', 'catalog_items.id', '=', 'merchant_items.catalog_item_id')
            ->whereExists(function ($query) use ($partsTable, $sectionPartsTable, $categoryIds) {
                $query->select(DB::raw(1))
                    ->from("{$partsTable} as p")
                    ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                    ->join('sections as s', 's.id', '=', 'sp.section_id')
                    ->whereColumn('p.part_number', 'catalog_items.part_number')
                    ->whereIn('s.category_id', $categoryIds);
            })
            ->where('merchant_items.status', 1)
            ->where('merchant_items.stock', '>=', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->distinct('catalog_items.part_number')
            ->orderBy('catalog_items.part_number');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get raw parts data from dynamic tables for given categories
     * Used for building parts list without merchant items
     *
     * @param array $categoryIds Category IDs
     * @param string $catalogCode Catalog code
     * @param int $limit Maximum results
     * @return Collection
     */
    public function getRawParts(array $categoryIds, string $catalogCode, int $limit = 1000): Collection
    {
        if (empty($categoryIds)) {
            return collect();
        }

        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            return collect();
        }

        return DB::table("{$partsTable} as p")
            ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.category_id', $categoryIds)
            ->select([
                'p.id',
                'p.part_number',
                'p.label_en',
                'p.label_ar',
                'p.qty',
                'p.callout',
                's.category_id',
                's.full_code as section_code'
            ])
            ->distinct()
            ->limit($limit)
            ->get();
    }

    /**
     * Build category tree for sidebar navigation
     *
     * @param int $catalogId Catalog ID
     * @param int|null $brandId Brand ID (optional filter)
     * @return Collection Hierarchical category tree
     */
    public function buildCategoryTree(int $catalogId, ?int $brandId = null): Collection
    {
        $query = NewCategory::query()
            ->where('catalog_id', $catalogId)
            ->orderBy('level')
            ->orderBy('label_en');

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $allCategories = $query->get();

        // Build tree structure
        $tree = collect();
        $categoryMap = [];

        // First pass: index all categories
        foreach ($allCategories as $category) {
            $categoryMap[$category->id] = $category;
            $category->children_items = collect();
        }

        // Second pass: build hierarchy
        foreach ($allCategories as $category) {
            if ($category->parent_id && isset($categoryMap[$category->parent_id])) {
                $categoryMap[$category->parent_id]->children_items->push($category);
            } elseif (!$category->parent_id || $category->level === 1) {
                $tree->push($category);
            }
        }

        return $tree;
    }

    /**
     * Resolve category from slug within a catalog
     *
     * @param string $slug Category slug
     * @param int $catalogId Catalog ID
     * @param int|null $parentId Parent category ID (for hierarchy validation)
     * @return NewCategory|null
     */
    public function resolveCategoryBySlug(string $slug, int $catalogId, ?int $parentId = null): ?NewCategory
    {
        $query = NewCategory::where('slug', $slug)
            ->where('catalog_id', $catalogId);

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        return $query->first();
    }

    /**
     * Resolve full category hierarchy from slugs
     *
     * @param int $catalogId Catalog ID
     * @param string|null $slug1 Level 1 category slug
     * @param string|null $slug2 Level 2 category slug
     * @param string|null $slug3 Level 3 category slug
     * @return array [cat1, cat2, cat3, deepest] where deepest is the most specific category
     */
    public function resolveCategoryHierarchy(
        int $catalogId,
        ?string $slug1 = null,
        ?string $slug2 = null,
        ?string $slug3 = null
    ): array {
        $cat1 = null;
        $cat2 = null;
        $cat3 = null;
        $deepest = null;

        if ($slug1) {
            $cat1 = NewCategory::where('slug', $slug1)
                ->where('catalog_id', $catalogId)
                ->where('level', 1)
                ->first();

            if ($cat1) {
                $deepest = $cat1;

                if ($slug2) {
                    $cat2 = NewCategory::where('slug', $slug2)
                        ->where('catalog_id', $catalogId)
                        ->where('parent_id', $cat1->id)
                        ->where('level', 2)
                        ->first();

                    if ($cat2) {
                        $deepest = $cat2;

                        if ($slug3) {
                            $cat3 = NewCategory::where('slug', $slug3)
                                ->where('catalog_id', $catalogId)
                                ->where('parent_id', $cat2->id)
                                ->where('level', 3)
                                ->first();

                            if ($cat3) {
                                $deepest = $cat3;
                            }
                        }
                    }
                }
            }
        }

        return [
            'cat1' => $cat1,
            'cat2' => $cat2,
            'cat3' => $cat3,
            'deepest' => $deepest,
        ];
    }

    /**
     * Get breadcrumb trail for a category
     *
     * @param NewCategory $category
     * @return Collection Ordered from root to current
     */
    public function getBreadcrumb(NewCategory $category): Collection
    {
        $breadcrumb = collect([$category]);
        $current = $category;

        while ($current->parent_id) {
            $parent = NewCategory::find($current->parent_id);
            if ($parent) {
                $breadcrumb->prepend($parent);
                $current = $parent;
            } else {
                break;
            }
        }

        return $breadcrumb;
    }

    /**
     * Resolve brand and catalog from slugs
     *
     * @param string $brandSlug Brand slug
     * @param string $catalogSlug Catalog slug (code)
     * @return array [brand, catalog]
     */
    public function resolveBrandAndCatalog(string $brandSlug, string $catalogSlug): array
    {
        $brand = Brand::where('name', $brandSlug)
            ->orWhere('name_ar', $brandSlug)
            ->first();

        $catalog = null;
        if ($brand) {
            $catalog = Catalog::where('code', $catalogSlug)
                ->where('brand_id', $brand->id)
                ->first();
        }

        return [
            'brand' => $brand,
            'catalog' => $catalog,
        ];
    }

    /**
     * Count available parts in category and descendants
     *
     * @param int $categoryId Category ID
     * @param int $catalogId Catalog ID
     * @param string $catalogCode Catalog code for dynamic tables
     * @return int Count of distinct SKUs with merchant_items
     */
    public function countAvailableParts(int $categoryId, int $catalogId, string $catalogCode): int
    {
        $descendantIds = $this->getDescendantIds($categoryId, $catalogId);

        if (empty($descendantIds)) {
            return 0;
        }

        $partsTable = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);

        if (!Schema::hasTable($partsTable) || !Schema::hasTable($sectionPartsTable)) {
            return 0;
        }

        return DB::table('merchant_items')
            ->join('catalog_items', 'catalog_items.id', '=', 'merchant_items.catalog_item_id')
            ->whereExists(function ($query) use ($partsTable, $sectionPartsTable, $descendantIds) {
                $query->select(DB::raw(1))
                    ->from("{$partsTable} as p")
                    ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
                    ->join('sections as s', 's.id', '=', 'sp.section_id')
                    ->whereColumn('p.part_number', 'catalog_items.part_number')
                    ->whereIn('s.category_id', $descendantIds);
            })
            ->where('merchant_items.status', 1)
            ->where('merchant_items.stock', '>=', 1)
            ->distinct('catalog_items.part_number')
            ->count('catalog_items.part_number');
    }
}
