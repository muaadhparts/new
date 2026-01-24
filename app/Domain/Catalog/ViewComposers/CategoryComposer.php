<?php

namespace App\Domain\Catalog\ViewComposers;

use Illuminate\View\View;
use App\Domain\Catalog\Models\NewCategory;
use Illuminate\Support\Facades\Cache;

/**
 * Category Composer
 *
 * Provides category tree data to views.
 */
class CategoryComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $categories = Cache::remember('categories_tree', 3600, function () {
            return NewCategory::where('status', 1)
                ->whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->where('status', 1)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        });

        $view->with('categoryTree', $categories);
    }
}
