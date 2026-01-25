<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\NewCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CategoryDescendantsQuery - Recursive category tree queries
 *
 * Uses CTEs (Common Table Expressions) for efficient tree traversal.
 */
class CategoryDescendantsQuery
{
    /**
     * Get all descendant category IDs using recursive CTE
     */
    public static function getDescendantIds(int $categoryId, int $catalogId): array
    {
        $sql = "
            WITH RECURSIVE category_tree AS (
                SELECT id FROM newcategories
                WHERE id = ? AND catalog_id = ?

                UNION ALL

                SELECT nc.id FROM newcategories nc
                INNER JOIN category_tree ct ON nc.parent_id = ct.id
                WHERE nc.catalog_id = ?
            )
            SELECT id FROM category_tree
        ";

        $results = DB::select($sql, [$categoryId, $catalogId, $catalogId]);
        return array_map(fn($row) => $row->id, $results);
    }

    /**
     * Get all ancestor category IDs (for breadcrumb)
     */
    public static function getAncestorIds(int $categoryId, int $catalogId): array
    {
        $sql = "
            WITH RECURSIVE category_ancestors AS (
                SELECT id, parent_id FROM newcategories
                WHERE id = ? AND catalog_id = ?

                UNION ALL

                SELECT nc.id, nc.parent_id FROM newcategories nc
                INNER JOIN category_ancestors ca ON nc.id = ca.parent_id
                WHERE nc.catalog_id = ?
            )
            SELECT id FROM category_ancestors
        ";

        $results = DB::select($sql, [$categoryId, $catalogId, $catalogId]);
        return array_map(fn($row) => $row->id, $results);
    }

    /**
     * Get category with all descendants as collection
     */
    public static function getDescendants(int $categoryId, int $catalogId): Collection
    {
        $ids = self::getDescendantIds($categoryId, $catalogId);

        if (empty($ids)) {
            return collect();
        }

        return NewCategory::whereIn('id', $ids)
            ->where('catalog_id', $catalogId)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get category with all ancestors as collection (for breadcrumb)
     */
    public static function getAncestors(int $categoryId, int $catalogId): Collection
    {
        $ids = self::getAncestorIds($categoryId, $catalogId);

        if (empty($ids)) {
            return collect();
        }

        return NewCategory::whereIn('id', $ids)
            ->where('catalog_id', $catalogId)
            ->orderBy('level')
            ->get();
    }

    /**
     * Get direct children of a category
     */
    public static function getChildren(int $categoryId, int $catalogId): Collection
    {
        return NewCategory::where('parent_id', $categoryId)
            ->where('catalog_id', $catalogId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get sibling categories (same parent)
     */
    public static function getSiblings(int $categoryId, int $catalogId): Collection
    {
        $category = NewCategory::find($categoryId);

        if (!$category) {
            return collect();
        }

        return NewCategory::where('parent_id', $category->parent_id)
            ->where('catalog_id', $catalogId)
            ->where('id', '!=', $categoryId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get tree depth (max level) for a catalog
     */
    public static function getMaxDepth(int $catalogId): int
    {
        return NewCategory::where('catalog_id', $catalogId)
            ->max('level') ?? 0;
    }

    /**
     * Get categories at specific level
     */
    public static function atLevel(int $catalogId, int $level): Collection
    {
        return NewCategory::where('catalog_id', $catalogId)
            ->where('level', $level)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get root categories (level 1)
     */
    public static function roots(int $catalogId): Collection
    {
        return self::atLevel($catalogId, 1);
    }

    /**
     * Count items in category and descendants
     */
    public static function countItems(int $categoryId, int $catalogId, string $catalogCode): int
    {
        $categoryIds = self::getDescendantIds($categoryId, $catalogId);

        if (empty($categoryIds)) {
            return 0;
        }

        $partsTable = strtolower("parts_{$catalogCode}");
        $sectionPartsTable = strtolower("section_parts_{$catalogCode}");

        $sql = "
            SELECT COUNT(DISTINCT ci.id) as count
            FROM catalog_items ci
            INNER JOIN {$partsTable} p ON p.part_number = ci.part_number
            INNER JOIN {$sectionPartsTable} sp ON sp.part_id = p.id
            WHERE sp.category_id IN (" . implode(',', $categoryIds) . ")
        ";

        $result = DB::selectOne($sql);
        return (int) ($result->count ?? 0);
    }
}
