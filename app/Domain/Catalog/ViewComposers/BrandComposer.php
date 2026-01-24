<?php

namespace App\Domain\Catalog\ViewComposers;

use Illuminate\View\View;
use App\Domain\Catalog\Models\Brand;
use Illuminate\Support\Facades\Cache;

/**
 * Brand Composer
 *
 * Provides brand list data to views.
 */
class BrandComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $brands = Cache::remember('active_brands', 3600, function () {
            return Brand::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar', 'slug', 'logo']);
        });

        $view->with('brands', $brands);
    }
}
