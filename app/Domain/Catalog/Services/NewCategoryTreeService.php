<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\NewCategory;
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

    /* =========================================================================
     |  Multi-Step Category Selector Methods
     | ========================================================================= */

    /**
     * Get catalogs for a specific brand (for category selector dropdown)
     *
     * @param int $brandId Brand ID
     * @return Collection
     */
    public function getCatalogsForBrand(int $brandId): Collection
    {
        return Catalog::where('brand_id', $brandId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'name_ar']);
    }

    /**
     * Get categories for a specific level (for category selector dropdown)
     *
     * @param int $catalogId Catalog ID
     * @param int $level Category level (1, 2, or 3)
     * @param int|null $parentId Parent category ID (required for levels 2+)
     * @return Collection
     */
    public function getCategoriesForLevel(int $catalogId, int $level, ?int $parentId = null): Collection
    {
        $query = NewCategory::where('catalog_id', $catalogId)
            ->where('level', $level)
            ->orderBy('label_en');

        if ($level > 1 && $parentId) {
            $query->where('parent_id', $parentId);
        } elseif ($level > 1) {
            // No parent ID for level > 1 means no results
            return collect();
        }

        return $query->get(['id', 'slug', 'label_en', 'label_ar']);
    }

    /**
     * Build complete multi-step selector data from URL segments
     *
     * This method centralizes all the queries needed for the category
     * multi-step selector, preventing database queries in blade files.
     *
     * @param Collection $brands All active brands (pre-loaded)
     * @param string|null $brandSlug URL segment for brand
     * @param string|null $catalogSlug URL segment for catalog
     * @param string|null $level1Slug URL segment for level 1 category
     * @param string|null $level2Slug URL segment for level 2 category
     * @param string|null $level3Slug URL segment for level 3 category
     * @return array Multi-step selector data
     */
    public function buildCategorySelectorData(
        Collection $brands,
        ?string $brandSlug = null,
        ?string $catalogSlug = null,
        ?string $level1Slug = null,
        ?string $level2Slug = null,
        ?string $level3Slug = null
    ): array {
        $result = [
            'brandSlug' => $brandSlug,
            'catalogSlug' => $catalogSlug,
            'level1Slug' => $level1Slug,
            'level2Slug' => $level2Slug,
            'level3Slug' => $level3Slug,
            'selectedBrand' => null,
            'selectedCatalog' => null,
            'selectedLevel1' => null,
            'selectedLevel2' => null,
            'selectedLevel3' => null,
            'brandCatalogs' => collect(),
            'catalogLevel1' => collect(),
            'level1Level2' => collect(),
            'level2Level3' => collect(),
        ];

        // Step 1: Resolve brand
        if ($brandSlug) {
            $result['selectedBrand'] = $brands->firstWhere('slug', $brandSlug);
        }

        // Step 2: Load catalogs for brand and resolve selected
        if ($result['selectedBrand']) {
            $result['brandCatalogs'] = $this->getCatalogsForBrand($result['selectedBrand']->id);

            if ($catalogSlug) {
                $result['selectedCatalog'] = $result['brandCatalogs']->firstWhere('slug', $catalogSlug);
            }
        }

        // Step 3: Load Level 1 categories and resolve selected
        if ($result['selectedCatalog']) {
            $result['catalogLevel1'] = $this->getCategoriesForLevel(
                $result['selectedCatalog']->id,
                1
            );

            if ($level1Slug) {
                $result['selectedLevel1'] = $result['catalogLevel1']->firstWhere('slug', $level1Slug);
            }
        }

        // Step 4: Load Level 2 categories and resolve selected
        if ($result['selectedLevel1']) {
            $result['level1Level2'] = $this->getCategoriesForLevel(
                $result['selectedCatalog']->id,
                2,
                $result['selectedLevel1']->id
            );

            if ($level2Slug) {
                $result['selectedLevel2'] = $result['level1Level2']->firstWhere('slug', $level2Slug);
            }
        }

        // Step 5: Load Level 3 categories and resolve selected
        if ($result['selectedLevel2']) {
            $result['level2Level3'] = $this->getCategoriesForLevel(
                $result['selectedCatalog']->id,
                3,
                $result['selectedLevel2']->id
            );

            if ($level3Slug) {
                $result['selectedLevel3'] = $result['level2Level3']->firstWhere('slug', $level3Slug);
            }
        }

        return $result;
    }
}
