<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Category;

/**
 * CategoryService - Centralized category management service
 *
 * Single source of truth for all category-related operations.
 *
 * Domain: Catalog
 * Responsibility: Manage categories, queries, and business logic
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class CategoryService
{
    /**
     * Find category by slug and catalog
     *
     * @param string $slug Category slug
     * @param int $catalogId Catalog ID
     * @return Category|null
     */
    public function findBySlugAndCatalog(string $slug, int $catalogId): ?Category
    {
        return Category::where('slug', $slug)
            ->where('catalog_id', $catalogId)
            ->first();
    }

    /**
     * Find category by full code and catalog
     *
     * @param string $fullCode Category full code
     * @param int $catalogId Catalog ID
     * @return Category|null
     */
    public function findByFullCodeAndCatalog(string $fullCode, int $catalogId): ?Category
    {
        return Category::where('full_code', $fullCode)
            ->where('catalog_id', $catalogId)
            ->first();
    }

    /**
     * Get categories by level for a catalog
     *
     * @param int $catalogId Catalog ID
     * @param int $level Category level
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesByLevel(int $catalogId, int $level)
    {
        return Category::where('catalog_id', $catalogId)
            ->where('level', $level)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get child categories for a parent
     *
     * @param int $catalogId Catalog ID
     * @param int $parentId Parent category ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildCategories(int $catalogId, int $parentId)
    {
        return Category::where('catalog_id', $catalogId)
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get root categories for a catalog
     *
     * @param int $catalogId Catalog ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRootCategories(int $catalogId)
    {
        return $this->getCategoriesByLevel($catalogId, 1);
    }

    /**
     * Get category hierarchy (breadcrumb)
     *
     * @param Category $category
     * @return array Array of categories from root to current
     */
    public function getCategoryHierarchy(Category $category): array
    {
        $hierarchy = [$category];
        $current = $category;

        while ($current->parent_id) {
            $parent = Category::find($current->parent_id);
            if (!$parent) {
                break;
            }
            array_unshift($hierarchy, $parent);
            $current = $parent;
        }

        return $hierarchy;
    }

    /**
     * Check if category has children
     *
     * @param int $categoryId Category ID
     * @return bool
     */
    public function hasChildren(int $categoryId): bool
    {
        return Category::where('parent_id', $categoryId)->exists();
    }

    /**
     * Get all categories for a catalog with hierarchy
     *
     * @param int $catalogId Catalog ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithHierarchy(int $catalogId)
    {
        return Category::where('catalog_id', $catalogId)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }
}
