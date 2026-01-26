<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\City;
use Illuminate\Support\Collection;

/**
 * LocationDataService - Single Source for Country/City Data
 *
 * This service handles all location data queries.
 * Helpers and Controllers must use this instead of direct Model queries.
 */
class LocationDataService
{
    /**
     * Get all active countries
     */
    public function getActiveCountries(): Collection
    {
        return cache()->remember('active_countries', 3600, function () {
            return Country::where('status', 1)
                ->with('cities')
                ->orderBy('country_name')
                ->get();
        });
    }

    /**
     * Get cities by country ID
     */
    public function getCitiesByCountry(int $countryId): Collection
    {
        return cache()->remember("cities_country_{$countryId}", 3600, function () use ($countryId) {
            return City::where('country_id', $countryId)
                ->orderBy('city_name')
                ->get();
        });
    }

    /**
     * Find country by name
     */
    public function findCountryByName(string $countryName): ?Country
    {
        return Country::where('country_name', $countryName)->first();
    }

    /**
     * Find city by name
     */
    public function findCityByName(string $cityName): ?City
    {
        return City::where('city_name', $cityName)->first();
    }

    /**
     * Get localized country name
     */
    public function getCountryDisplayName(Country $country): string
    {
        if (app()->getLocale() === 'ar') {
            return $country->country_name_ar ?: $country->country_name;
        }
        return $country->country_name;
    }

    /**
     * Get city display name (English only - no Arabic column exists)
     */
    public function getCityDisplayName(City $city): string
    {
        return $city->city_name;
    }

    /**
     * Build countries dropdown options
     *
     * @return array Array of ['value' => ..., 'label' => ..., 'id' => ..., 'has_cities' => ...]
     */
    public function getCountriesDropdownData(?int $selectedCountryId = null): array
    {
        $locale = app()->getLocale();
        $countries = $this->getActiveCountries();

        return $countries->map(function ($country) use ($locale, $selectedCountryId) {
            $displayName = ($locale === 'ar')
                ? ($country->country_name_ar ?: $country->country_name)
                : $country->country_name;

            return [
                'value' => $country->country_name,
                'label' => $displayName,
                'id' => $country->id,
                'has_cities' => $country->cities->count() > 0,
                'cities_url' => route('country.wise.city', $country->id),
                'selected' => $selectedCountryId && $country->id === $selectedCountryId,
            ];
        })->toArray();
    }

    /**
     * Build cities dropdown options
     *
     * @return array Array of ['value' => ..., 'label' => ..., 'selected' => ...]
     */
    public function getCitiesDropdownData(int $countryId, ?string $selectedCity = null, bool $useIdAsValue = false): array
    {
        $cities = $this->getCitiesByCountry($countryId);

        return $cities->map(function ($city) use ($selectedCity, $useIdAsValue) {
            $value = $useIdAsValue ? $city->id : $city->city_name;

            return [
                'value' => $value,
                'label' => $city->city_name,
                'selected' => $selectedCity && $value == $selectedCity,
            ];
        })->toArray();
    }

    /**
     * Clear location cache
     */
    public function clearCache(): void
    {
        cache()->forget('active_countries');
        // Clear all city caches
        Country::where('status', 1)->pluck('id')->each(function ($id) {
            cache()->forget("cities_country_{$id}");
        });
    }
}
