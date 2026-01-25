<?php

namespace App\Domain\Catalog\Observers;

use App\Domain\Catalog\Models\NewCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Category Observer
 *
 * Handles NewCategory model lifecycle events.
 */
class CategoryObserver
{
    /**
     * Handle the Category "creating" event.
     */
    public function creating(NewCategory $category): void
    {
        // Generate slug if not set
        if (empty($category->slug)) {
            $category->slug = $this->generateSlug($category->name);
        }

        // Set level based on parent
        if ($category->parent_id) {
            $parent = NewCategory::find($category->parent_id);
            $category->level = $parent ? $parent->level + 1 : 1;
        } else {
            $category->level = 1;
        }
    }

    /**
     * Handle the Category "created" event.
     */
    public function created(NewCategory $category): void
    {
        $this->clearCategoryCache();
    }

    /**
     * Handle the Category "updating" event.
     */
    public function updating(NewCategory $category): void
    {
        // Update slug if name changed
        if ($category->isDirty('name')) {
            $category->slug = $this->generateSlug($category->name);
        }

        // Update level if parent changed
        if ($category->isDirty('parent_id')) {
            if ($category->parent_id) {
                $parent = NewCategory::find($category->parent_id);
                $category->level = $parent ? $parent->level + 1 : 1;
            } else {
                $category->level = 1;
            }
        }
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(NewCategory $category): void
    {
        $this->clearCategoryCache();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(NewCategory $category): void
    {
        $this->clearCategoryCache();

        // Update children to have no parent or cascade
        NewCategory::where('parent_id', $category->id)
            ->update(['parent_id' => $category->parent_id]);
    }

    /**
     * Generate unique slug.
     */
    protected function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (NewCategory::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Clear category-related cache.
     */
    protected function clearCategoryCache(): void
    {
        Cache::forget('categories_tree');
        Cache::forget('categories_menu');
        Cache::forget('categories_list');
    }
}
