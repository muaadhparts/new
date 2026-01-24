<?php

namespace App\Domain\Catalog\ViewComposers;

use Illuminate\View\View;
use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Facades\Cache;

/**
 * Featured Items Composer
 *
 * Provides featured/popular items to views.
 */
class FeaturedItemsComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $featuredItems = Cache::remember('featured_items', 1800, function () {
            return CatalogItem::where('status', 1)
                ->whereHas('merchantItems', function ($query) {
                    $query->where('status', 1)->where('stock', '>', 0);
                })
                ->with(['brand:id,name,name_ar,slug', 'merchantItems' => function ($query) {
                    $query->where('status', 1)
                        ->where('stock', '>', 0)
                        ->orderBy('price')
                        ->limit(1);
                }])
                ->orderByDesc('view_count')
                ->limit(12)
                ->get();
        });

        $view->with('featuredItems', $featuredItems);
    }
}
