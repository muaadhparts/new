<?php

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Collection;

/**
 * CategoryTreeInterface - Contract for category tree operations
 *
 * All category hierarchy operations MUST go through this interface.
 */
interface CategoryTreeInterface
{
    /**
     * Get descendant IDs for a category
     */
    public function getDescendantIds(int $categoryId): array;

    /**
     * Get descendant IDs for multiple categories
     */
    public function getDescendantIdsForMultiple(array $categoryIds): array;

    /**
     * Build category tree structure
     */
    public function buildCategoryTree(?int $parentId = null, int $depth = 3): Collection;

    /**
     * Get breadcrumb for a category
     */
    public function getBreadcrumb(int $categoryId): Collection;

    /**
     * Resolve category by slug
     */
    public function resolveCategoryBySlug(string $slug): ?Category;

    /**
     * Resolve full category hierarchy
     */
    public function resolveCategoryHierarchy(?string $level1, ?string $level2, ?string $level3): array;

    /**
     * Count available parts in category
     */
    public function countAvailableParts(int $categoryId, ?int $brandId = null): int;
}
