<?php

namespace App\Domain\Shipping\ViewComposers;

use Illuminate\View\View;
use App\Domain\Shipping\Models\Courier;
use Illuminate\Support\Facades\Cache;

/**
 * Courier Composer
 *
 * Provides courier/shipping providers data to views.
 */
class CourierComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $couriers = Cache::remember('active_couriers', 3600, function () {
            return Courier::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'logo', 'tracking_url']);
        });

        $view->with('couriers', $couriers);
    }
}
