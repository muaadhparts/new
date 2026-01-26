<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Models\NewCategory;
use Illuminate\Support\Collection;

/**
 * CategoryTreeDTO - Pre-computed data for category navigation/tree
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class CategoryTreeDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $photoUrl,
        public readonly string $url,
        public readonly int $level,
        public readonly ?int $parentId,
        public readonly int $itemsCount,
        public readonly bool $hasChildren,
        public readonly array $children,
    ) {}

    /**
     * Build DTO from NewCategory model
     */
    public static function fromModel(NewCategory $category, bool $includeChildren = true): self
    {
        return new self(
            id: $category->id,
            name: $category->localized_name ?? $category->name,
            slug: $category->slug ?? '',
            photoUrl: $category->photo ? asset('assets/images/' . $category->photo) : null,
            url: route('front.category', $category->slug ?? $category->id),
            level: $category->level ?? 1,
            parentId: $category->parent_id,
            itemsCount: $category->catalog_items_count ?? 0,
            hasChildren: $category->children_count > 0 || $category->children->isNotEmpty(),
            children: $includeChildren ? self::buildChildren($category) : [],
        );
    }

    /**
     * Build children array recursively
     */
    private static function buildChildren(NewCategory $category): array
    {
        if (!$category->relationLoaded('children') || $category->children->isEmpty()) {
            return [];
        }

        return $category->children->map(function ($child) {
            return self::fromModel($child, true);
        })->toArray();
    }

    /**
     * Build tree from root categories collection
     */
    public static function buildTree(Collection $rootCategories): array
    {
        return $rootCategories->map(fn($cat) => self::fromModel($cat, true))->toArray();
    }

    /**
     * Flatten tree for breadcrumb/select purposes
     */
    public static function flattenForSelect(Collection $categories, int $level = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            $prefix = str_repeat('— ', $level);
            $result[] = [
                'id' => $category->id,
                'name' => $prefix . ($category->localized_name ?? $category->name),
                'level' => $level,
            ];

            if ($category->relationLoaded('children') && $category->children->isNotEmpty()) {
                $result = array_merge($result, self::flattenForSelect($category->children, $level + 1));
            }
        }

        return $result;
    }
}
