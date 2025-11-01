<?php

namespace App\Helpers;

use App\Models\Country;
use App\Models\State;
use App\Models\City;

/**
 * LocationHelper - مساعد موحد لعرض أسماء الدول والمناطق والمدن حسب اللغة النشطة
 *
 * يوفر دوال موحدة لجلب الأسماء من قاعدة البيانات مباشرة بناءً على اللغة النشطة
 * - إذا كانت اللغة ar: يستخدم country_name_ar, state_ar, city_name_ar
 * - إذا كانت اللغة en: يستخدم country_name, state, city_name
 * - Fallback: إذا كان العمود العربي فارغ، يستخدم العمود الإنجليزي
 *
 * لا يستخدم دوال الترجمة __() أو ملفات الترجمة
 */
class LocationHelper
{
    /**
     * الحصول على اسم الدولة حسب اللغة النشطة
     *
     * @param Country $country
     * @return string
     */
    public static function getCountryName(Country $country): string
    {
        $locale = app()->getLocale();

        if ($locale == 'ar') {
            return $country->country_name_ar ?: $country->country_name;
        }

        return $country->country_name;
    }

    /**
     * الحصول على اسم الولاية/المنطقة حسب اللغة النشطة
     *
     * @param State $state
     * @return string
     */
    public static function getStateName(State $state): string
    {
        $locale = app()->getLocale();

        if ($locale == 'ar') {
            return $state->state_ar ?: $state->state;
        }

        return $state->state;
    }

    /**
     * الحصول على اسم المدينة حسب اللغة النشطة
     *
     * @param City $city
     * @return string
     */
    public static function getCityName(City $city): string
    {
        $locale = app()->getLocale();

        if ($locale == 'ar') {
            return $city->city_name_ar ?: $city->city_name;
        }

        return $city->city_name;
    }

    /**
     * إنشاء HTML options للدول حسب اللغة النشطة
     *
     * @param int|null $selectedCountryId
     * @param bool $includeEmptyOption
     * @return string
     */
    public static function getCountriesOptionsHtml(?int $selectedCountryId = null, bool $includeEmptyOption = true): string
    {
        $locale = app()->getLocale();
        $countries = Country::where('status', 1)->get();

        $html = '';

        if ($includeEmptyOption) {
            $html .= '<option value="">Select Country</option>';
        }

        foreach ($countries as $country) {
            $displayName = ($locale == 'ar')
                ? ($country->country_name_ar ?: $country->country_name)
                : $country->country_name;

            $selected = ($selectedCountryId && $country->id == $selectedCountryId) ? 'selected' : '';
            $hasStates = $country->states->count() > 0 ? 1 : 0;

            $html .= sprintf(
                '<option value="%s" data="%d" rel="%d" data-href="%s" %s>%s</option>',
                $country->country_name,
                $country->id,
                $hasStates,
                route('country.wise.state', $country->id),
                $selected,
                htmlspecialchars($displayName)
            );
        }

        return $html;
    }

    /**
     * إنشاء HTML options للولايات حسب اللغة النشطة
     *
     * @param int $countryId
     * @param int|null $selectedStateId
     * @param bool $includeEmptyOption
     * @return string
     */
    public static function getStatesOptionsHtml(int $countryId, ?int $selectedStateId = null, bool $includeEmptyOption = true): string
    {
        $locale = app()->getLocale();
        $states = State::where('country_id', $countryId)->get();

        $html = '';

        if ($includeEmptyOption) {
            $html .= '<option value="">Select State</option>';
        }

        foreach ($states as $state) {
            $displayName = ($locale == 'ar')
                ? ($state->state_ar ?: $state->state)
                : $state->state;

            $selected = ($selectedStateId && $state->id == $selectedStateId) ? 'selected' : '';

            $html .= sprintf(
                '<option value="%d" rel="%d" %s>%s</option>',
                $state->id,
                $state->country_id,
                $selected,
                htmlspecialchars($displayName)
            );
        }

        return $html;
    }

    /**
     * إنشاء HTML options للمدن حسب اللغة النشطة
     *
     * @param int $stateId
     * @param string|null $selectedCity
     * @param bool $includeEmptyOption
     * @param bool $useIdAsValue - استخدام ID بدلاً من الاسم
     * @return string
     */
    public static function getCitiesOptionsHtml(int $stateId, ?string $selectedCity = null, bool $includeEmptyOption = true, bool $useIdAsValue = false): string
    {
        $locale = app()->getLocale();
        $cities = City::where('state_id', $stateId)->get();

        $html = '';

        if ($includeEmptyOption) {
            $html .= '<option value="">Select City</option>';
        }

        foreach ($cities as $city) {
            $displayName = ($locale == 'ar')
                ? ($city->city_name_ar ?: $city->city_name)
                : $city->city_name;

            $value = $useIdAsValue ? $city->id : $city->city_name;
            $selected = ($selectedCity && $value == $selectedCity) ? 'selected' : '';

            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($value),
                $selected,
                htmlspecialchars($displayName)
            );
        }

        return $html;
    }

    /**
     * الحصول على اسم العمود للدولة حسب اللغة النشطة
     *
     * @return string
     */
    public static function getCountryColumnName(): string
    {
        return app()->getLocale() == 'ar' ? 'country_name_ar' : 'country_name';
    }

    /**
     * الحصول على اسم العمود للولاية حسب اللغة النشطة
     *
     * @return string
     */
    public static function getStateColumnName(): string
    {
        return app()->getLocale() == 'ar' ? 'state_ar' : 'state';
    }

    /**
     * الحصول على اسم العمود للمدينة حسب اللغة النشطة
     *
     * @return string
     */
    public static function getCityColumnName(): string
    {
        return app()->getLocale() == 'ar' ? 'city_name_ar' : 'city_name';
    }

    /**
     * جلب اسم الدولة من قاعدة البيانات بالاسم
     *
     * @param string $countryName
     * @return string|null
     */
    public static function getCountryDisplayName(string $countryName): ?string
    {
        $country = Country::where('country_name', $countryName)->first();

        if (!$country) {
            return $countryName;
        }

        return self::getCountryName($country);
    }

    /**
     * جلب اسم الولاية من قاعدة البيانات بالاسم
     *
     * @param string $stateName
     * @return string|null
     */
    public static function getStateDisplayName(string $stateName): ?string
    {
        $state = State::where('state', $stateName)->first();

        if (!$state) {
            return $stateName;
        }

        return self::getStateName($state);
    }

    /**
     * جلب اسم المدينة من قاعدة البيانات بالاسم
     *
     * @param string $cityName
     * @return string|null
     */
    public static function getCityDisplayName(string $cityName): ?string
    {
        $city = City::where('city_name', $cityName)->first();

        if (!$city) {
            return $cityName;
        }

        return self::getCityName($city);
    }
}
