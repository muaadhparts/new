<?php

namespace App\Helpers;

use App\Models\Country;
use App\Models\City;

/**
 * LocationHelper - مساعد موحد لعرض أسماء الدول والمدن حسب اللغة النشطة
 *
 * يوفر دوال موحدة لجلب الأسماء من قاعدة البيانات مباشرة بناءً على اللغة النشطة
 * - إذا كانت اللغة ar: يستخدم country_name_ar, city_name_ar
 * - إذا كانت اللغة en: يستخدم country_name, city_name
 * - Fallback: إذا كان العمود العربي فارغ، يستخدم العمود الإنجليزي
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
            $hasCities = $country->cities->count() > 0 ? 1 : 0;

            $html .= sprintf(
                '<option value="%s" data="%d" rel="%d" data-href="%s" %s>%s</option>',
                $country->country_name,
                $country->id,
                $hasCities,
                route('country.wise.city', $country->id),
                $selected,
                htmlspecialchars($displayName)
            );
        }

        return $html;
    }

    /**
     * إنشاء HTML options للمدن حسب اللغة النشطة
     *
     * @param int $countryId
     * @param string|null $selectedCity
     * @param bool $includeEmptyOption
     * @param bool $useIdAsValue - استخدام ID بدلاً من الاسم
     * @return string
     */
    public static function getCitiesOptionsHtml(int $countryId, ?string $selectedCity = null, bool $includeEmptyOption = true, bool $useIdAsValue = false): string
    {
        $locale = app()->getLocale();
        $cities = City::where('country_id', $countryId)->get();

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
