<?php

namespace App\Domain\Shipping\ViewComposers;

use Illuminate\View\View;
use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\City;
use Illuminate\Support\Facades\Cache;

/**
 * Location Composer
 *
 * Provides location data (countries, cities) to views.
 */
class LocationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $countries = Cache::remember('active_countries', 3600, function () {
            return Country::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar', 'code', 'phone_code']);
        });

        $cities = Cache::remember('cities_by_country', 3600, function () {
            return City::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar', 'country_id'])
                ->groupBy('country_id');
        });

        $view->with([
            'countries' => $countries,
            'citiesByCountry' => $cities,
        ]);
    }
}
