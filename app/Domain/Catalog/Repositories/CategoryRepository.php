<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Catalog\Models\NewCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Category Repository
 *
 * Repository for category data access.
 */
class CategoryRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return NewCategory::class;
    }

    /**
     * Get root categories.
     */
    public function getRoots(): Collection
    {
        return Cache::remember('categories:roots', 3600, function () {
            return $this->query()
                ->whereNull('parent_id')
                ->where('status', 1)
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * Get category children.
     */
    public function getChildren(int $parentId): Collection
    {
        return Cache::remember("categories:children:{$parentId}", 3600, function () use ($parentId) {
            return $this->query()
                ->where('parent_id', $parentId)
                ->where('status', 1)
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * Get category tree.
     */
    public function getTree(): Collection
    {
        return Cache::remember('categories:tree', 3600, function () {
            return $this->query()
                ->whereNull('parent_id')
                ->where('status', 1)
                ->with(['children' => fn($q) => $q->where('status', 1)->orderBy('position')])
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $slug): ?NewCategory
    {
        return $this->findFirstBy('slug', $slug);
    }

    /**
     * Get category with ancestors.
     */
    public function getWithAncestors(int $id): ?NewCategory
    {
        $category = $this->find($id);

        if ($category) {
            $category->load('parent.parent.parent');
        }

        return $category;
    }

    /**
     * Get categories for dropdown.
     */
    public function getForDropdown(): array
    {
        return $this->query()
            ->where('status', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Clear category cache.
     */
    public function clearCache(): void
    {
        Cache::forget('categories:roots');
        Cache::forget('categories:tree');
    }
}
