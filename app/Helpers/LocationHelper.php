<?php

namespace App\Helpers;

use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Services\LocationDataService;

/**
 * LocationHelper - Unified Location Display Helper
 *
 * DATA FLOW POLICY: This helper is a SERVICE ACCESSOR only.
 * All queries are delegated to LocationDataService.
 *
 * Architecture:
 * - Cities: city_name only (English) - no city_name_ar (dropped from database)
 * - Countries: country_name, country_name_ar
 */
class LocationHelper
{
    /**
     * Get the LocationDataService instance
     */
    private static function service(): LocationDataService
    {
        return app(LocationDataService::class);
    }

    /**
     * Get country name based on active locale
     */
    public static function getCountryName(Country $country): string
    {
        return self::service()->getCountryDisplayName($country);
    }

    /**
     * Get city name
     * Note: Cities have English name only - no city_name_ar
     */
    public static function getCityName(City $city): string
    {
        return self::service()->getCityDisplayName($city);
    }

    /**
     * Build HTML options for countries dropdown based on active locale
     *
     * @param int|string|null $selected Country ID or name
     * @param bool $includeEmptyOption
     * @return string
     */
    public static function getCountriesOptionsHtml(int|string|null $selected = null, bool $includeEmptyOption = true): string
    {
        $countries = self::service()->getActiveCountries();

        $html = '';

        if ($includeEmptyOption) {
            $html .= '<option value="">' . __('Select Country') . '</option>';
        }

        foreach ($countries as $country) {
            $label = self::service()->getCountryDisplayName($country);
            $hasCities = $country->cities->count() > 0 ? 1 : 0;
            $isSelected = false;

            if ($selected !== null) {
                if (is_numeric($selected)) {
                    $isSelected = $country->id == $selected;
                } else {
                    $isSelected = $country->country_name == $selected;
                }
            }

            $selectedAttr = $isSelected ? 'selected' : '';
            $isLoggedIn = \Illuminate\Support\Facades\Auth::check() ? 1 : 0;

            $html .= sprintf(
                '<option value="%s" data="%d" rel="%d" rel1="%d" rel5="%d" data-href="%s" %s>%s</option>',
                htmlspecialchars($country->country_name),
                $country->id,
                $hasCities,
                $isLoggedIn,
                $isLoggedIn,
                route('country.wise.city', $country->id),
                $selectedAttr,
                htmlspecialchars($label)
            );
        }

        return $html;
    }

    /**
     * Build HTML options for cities dropdown
     * Note: Cities have English name only - no city_name_ar
     *
     * @deprecated Use LocationDataService::getCitiesDropdownData() with Blade component
     */
    public static function getCitiesOptionsHtml(int $countryId, ?string $selectedCity = null, bool $includeEmptyOption = true, bool $useIdAsValue = false): string
    {
        $dropdownData = self::service()->getCitiesDropdownData($countryId, $selectedCity, $useIdAsValue);

        $html = '';

        if ($includeEmptyOption) {
            $html .= '<option value="">Select City</option>';
        }

        foreach ($dropdownData as $item) {
            $selected = $item['selected'] ? 'selected' : '';

            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars((string)$item['value']),
                $selected,
                htmlspecialchars($item['label'])
            );
        }

        return $html;
    }

    /**
     * Get column name for country based on active locale
     */
    public static function getCountryColumnName(): string
    {
        return app()->getLocale() === 'ar' ? 'country_name_ar' : 'country_name';
    }

    /**
     * Get column name for city
     * Note: Cities have English name only - no city_name_ar
     */
    public static function getCityColumnName(): string
    {
        return 'city_name';
    }

    /**
     * Get country display name from database by name
     */
    public static function getCountryDisplayName(string $countryName): ?string
    {
        $country = self::service()->findCountryByName($countryName);

        if (!$country) {
            return $countryName;
        }

        return self::getCountryName($country);
    }

    /**
     * Get city display name from database by name
     */
    public static function getCityDisplayName(string $cityName): ?string
    {
        $city = self::service()->findCityByName($cityName);

        if (!$city) {
            return $cityName;
        }

        return self::getCityName($city);
    }
}
