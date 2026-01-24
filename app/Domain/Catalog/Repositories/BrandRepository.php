<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Brand Repository
 *
 * Repository for brand data access.
 */
class BrandRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Brand::class;
    }

    /**
     * Get all active brands.
     */
    public function getActive(): Collection
    {
        return Cache::remember('brands:active', 3600, function () {
            return $this->query()
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $slug): ?Brand
    {
        return $this->findFirstBy('slug', $slug);
    }

    /**
     * Get brands for dropdown.
     */
    public function getForDropdown(): array
    {
        return $this->getActive()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get brands with product count.
     */
    public function getWithProductCount(): Collection
    {
        return $this->query()
            ->where('status', 1)
            ->withCount('catalogItems')
            ->orderBy('name')
            ->get();
    }

    /**
     * Clear brand cache.
     */
    public function clearCache(): void
    {
        Cache::forget('brands:active');
    }
}
