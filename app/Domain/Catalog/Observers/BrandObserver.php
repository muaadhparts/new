<?php

namespace App\Domain\Catalog\Observers;

use App\Domain\Catalog\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Brand Observer
 *
 * Handles Brand model lifecycle events.
 */
class BrandObserver
{
    /**
     * Handle the Brand "creating" event.
     */
    public function creating(Brand $brand): void
    {
        // Generate slug if not set
        if (empty($brand->slug)) {
            $brand->slug = $this->generateSlug($brand->name);
        }
    }

    /**
     * Handle the Brand "created" event.
     */
    public function created(Brand $brand): void
    {
        $this->clearBrandCache();
    }

    /**
     * Handle the Brand "updating" event.
     */
    public function updating(Brand $brand): void
    {
        // Update slug if name changed
        if ($brand->isDirty('name')) {
            $brand->slug = $this->generateSlug($brand->name);
        }
    }

    /**
     * Handle the Brand "updated" event.
     */
    public function updated(Brand $brand): void
    {
        $this->clearBrandCache();
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        $this->clearBrandCache();
    }

    /**
     * Generate unique slug.
     */
    protected function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (Brand::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Clear brand-related cache.
     */
    protected function clearBrandCache(): void
    {
        Cache::forget('active_brands');
        Cache::forget('brands_list');
        Cache::forget('brands_with_count');
    }
}
