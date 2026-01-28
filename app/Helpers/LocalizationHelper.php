<?php

if (!function_exists('localized')) {
    /**
     * Get localized field value based on current locale
     *
     * @param mixed $model Model instance or array
     * @param string $field Base field name (e.g., 'name')
     * @param string|null $fallback Fallback value if not found
     * @return string|null
     */
    function localized($model, string $field, ?string $fallback = null): ?string
    {
        $locale = app()->getLocale();
        $localizedField = $field . '_' . $locale;

        // Handle array
        if (is_array($model)) {
            return $model[$localizedField] ?? $model[$field] ?? $fallback;
        }

        // Handle object/model
        if (is_object($model)) {
            return $model->$localizedField ?? $model->$field ?? $fallback;
        }

        return $fallback;
    }
}

if (!function_exists('localized_or')) {
    /**
     * Get localized field with explicit fallback field
     *
     * @param mixed $model Model instance or array
     * @param string $field Base field name
     * @param string $fallbackField Fallback field name
     * @return string|null
     */
    function localized_or($model, string $field, string $fallbackField): ?string
    {
        $locale = app()->getLocale();
        $localizedField = $field . '_' . $locale;

        // Handle array
        if (is_array($model)) {
            return $model[$localizedField] ?? $model[$fallbackField] ?? null;
        }

        // Handle object/model
        if (is_object($model)) {
            return $model->$localizedField ?? $model->$fallbackField ?? null;
        }

        return null;
    }
}

if (!function_exists('ar_or_en')) {
    /**
     * Get Arabic field if current locale is Arabic, otherwise English
     *
     * @param mixed $model Model instance or array
     * @param string $arField Arabic field name
     * @param string $enField English field name
     * @return string|null
     */
    function ar_or_en($model, string $arField, string $enField): ?string
    {
        $locale = app()->getLocale();

        // Handle array
        if (is_array($model)) {
            if ($locale === 'ar' && !empty($model[$arField])) {
                return $model[$arField];
            }
            return $model[$enField] ?? $model[$arField] ?? null;
        }

        // Handle object/model
        if (is_object($model)) {
            if ($locale === 'ar' && !empty($model->$arField)) {
                return $model->$arField;
            }
            return $model->$enField ?? $model->$arField ?? null;
        }

        return null;
    }
}
