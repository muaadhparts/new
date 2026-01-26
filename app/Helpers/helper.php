<?php

function getMerchantDisplayName($merchantItem)
{
    if (!$merchantItem || !$merchantItem->user) {
        return '';
    }

    $merchant = $merchantItem->user;
    $displayName = $merchant->shop_name ?: $merchant->name;

    // Add quality brand  if available
    if ($merchantItem->qualityBrand) {
        $displayName .= ' (' . $merchantItem->qualityBrand->display_name . ')';
    }

    return $displayName;
}


/**
 * Get localized catalogItem name from cart item array or CatalogItem model
 * Supports both array format (cart) and object format (CatalogItem model)
 */
if (! function_exists('getLocalizedCatalogItemName')) {
    function getLocalizedCatalogItemName($item, $maxLength = null): string
    {
        $isAr = app()->getLocale() === 'ar';

        // Handle array format (cart item)
        if (is_array($item)) {
            $labelAr = trim($item['label_ar'] ?? '');
            $labelEn = trim($item['label_en'] ?? '');
            $name = trim($item['name'] ?? '');
        }
        // Handle object format (CatalogItem model)
        elseif (is_object($item)) {
            // If model has localized_name accessor, use it
            if (method_exists($item, 'getLocalizedNameAttribute') || property_exists($item, 'localized_name')) {
                $displayName = $item->localized_name ?? $item->name ?? '';
                if ($maxLength && mb_strlen($displayName, 'UTF-8') > $maxLength) {
                    return mb_substr($displayName, 0, $maxLength, 'UTF-8') . '...';
                }
                return $displayName;
            }
            $labelAr = trim($item->label_ar ?? '');
            $labelEn = trim($item->label_en ?? '');
            $name = trim($item->name ?? '');
        } else {
            return '';
        }

        // Determine display name based on locale
        if ($isAr) {
            $displayName = $labelAr !== '' ? $labelAr : ($labelEn !== '' ? $labelEn : $name);
        } else {
            $displayName = $labelEn !== '' ? $labelEn : ($labelAr !== '' ? $labelAr : $name);
        }

        // Truncate if maxLength specified
        if ($maxLength && mb_strlen($displayName, 'UTF-8') > $maxLength) {
            return mb_substr($displayName, 0, $maxLength, 'UTF-8') . '...';
        }

        return $displayName;
    }
}


/**
 * Get localized brand name from Brand model or array
 */
if (! function_exists('getLocalizedBrandName')) {
    function getLocalizedBrandName($brand): string
    {
        if (!$brand) return '';

        $isAr = app()->getLocale() === 'ar';

        if (is_array($brand)) {
            $nameAr = trim($brand['name_ar'] ?? '');
            $name = trim($brand['name'] ?? '');
        } elseif (is_object($brand)) {
            // If model has localized_name accessor, use it
            if (method_exists($brand, 'getLocalizedNameAttribute') || property_exists($brand, 'localized_name')) {
                return $brand->localized_name ?? $brand->name ?? '';
            }
            $nameAr = trim($brand->name_ar ?? '');
            $name = trim($brand->name ?? '');
        } else {
            return '';
        }

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $name;
        }
        return $name !== '' ? $name : $nameAr;
    }
}

/**
 * Get localized quality brand name from QualityBrand model or array
 */
if (! function_exists('getLocalizedQualityName')) {
    function getLocalizedQualityName($quality): string
    {
        if (!$quality) return '';

        $isAr = app()->getLocale() === 'ar';

        if (is_array($quality)) {
            $nameAr = trim($quality['name_ar'] ?? '');
            $nameEn = trim($quality['name_en'] ?? '');
        } elseif (is_object($quality)) {
            // If model has localized_name accessor, use it
            if (method_exists($quality, 'getLocalizedNameAttribute') || property_exists($quality, 'localized_name')) {
                return $quality->localized_name ?? '';
            }
            $nameAr = trim($quality->name_ar ?? '');
            $nameEn = trim($quality->name_en ?? '');
        } else {
            return '';
        }

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $nameEn;
        }
        return $nameEn !== '' ? $nameEn : $nameAr;
    }
}

/**
 * Get localized category name from Category/Subcategory/Childcategory model or array
 */
if (! function_exists('getLocalizedCategoryName')) {
    function getLocalizedCategoryName($category): string
    {
        if (!$category) return '';

        $isAr = app()->getLocale() === 'ar';

        if (is_array($category)) {
            $nameAr = trim($category['name_ar'] ?? '');
            $name = trim($category['name'] ?? '');
        } elseif (is_object($category)) {
            // If model has localized_name accessor, use it
            if (method_exists($category, 'getLocalizedNameAttribute') || property_exists($category, 'localized_name')) {
                return $category->localized_name ?? $category->name ?? '';
            }
            $nameAr = trim($category->name_ar ?? '');
            $name = trim($category->name ?? '');
        } else {
            return '';
        }

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $name;
        }
        return $name !== '' ? $name : $nameAr;
    }
}

/**
 * Get localized shop name from merchant/user object
 * Handles both User model and query result objects
 */
if (! function_exists('getLocalizedShopName')) {
    function getLocalizedShopName($merchant): string
    {
        if (!$merchant) return '';

        $isAr = app()->getLocale() === 'ar';

        if (is_array($merchant)) {
            $shopNameAr = trim($merchant['shop_name_ar'] ?? '');
            $shopName = trim($merchant['shop_name'] ?? '');
        } elseif (is_object($merchant)) {
            $shopNameAr = trim($merchant->shop_name_ar ?? '');
            $shopName = trim($merchant->shop_name ?? '');
        } else {
            return '';
        }

        if ($isAr && $shopNameAr !== '') {
            return $shopNameAr;
        }
        return $shopName !== '' ? $shopName : __('Unknown Merchant');
    }
}

/**
 * Get merchant/store name from cart item or MerchantItem
 * Now with localization support
 */
if (! function_exists('getMerchantName')) {
    function getMerchantName($item): string
    {
        if (!$item) return '';

        $isAr = app()->getLocale() === 'ar';

        // From cart array
        if (is_array($item)) {
            if ($isAr && !empty($item['shop_name_ar'])) {
                return $item['shop_name_ar'];
            }
            return $item['merchant_name'] ?? $item['shop_name'] ?? '';
        }

        // From MerchantItem or similar object
        if (is_object($item)) {
            if (isset($item->user) && $item->user) {
                if ($isAr && !empty($item->user->shop_name_ar)) {
                    return $item->user->shop_name_ar;
                }
                return $item->user->shop_name ?? $item->user->name ?? '';
            }
            if ($isAr && isset($item->shop_name_ar) && !empty($item->shop_name_ar)) {
                return $item->shop_name_ar;
            }
            if (isset($item->merchant_name)) {
                return $item->merchant_name;
            }
            if (isset($item->shop_name)) {
                return $item->shop_name;
            }
        }

        return '';
    }
}

/**
 * Get manufacturer name from catalogItem
 */
if (! function_exists('getManufacturerName')) {
    function getManufacturerName($catalogItem): string
    {
        if (!$catalogItem) return '';

        // From array
        if (is_array($catalogItem)) {
            $item = $catalogItem['item'] ?? $catalogItem;
            return $item['manufacturer'] ?? $item['manufacturer_name'] ?? '';
        }

        // From object
        if (is_object($catalogItem)) {
            // If has manufacturer relationship
            if (isset($catalogItem->manufacturer) && $catalogItem->manufacturer) {
                return $catalogItem->manufacturer->name ?? '';
            }
            // If has manufacturer_name attribute
            if (isset($catalogItem->manufacturer_name)) {
                return $catalogItem->manufacturer_name;
            }
            // If has manufacturer attribute
            if (isset($catalogItem->manufacturer)) {
                return $catalogItem->manufacturer;
            }
        }

        return '';
    }
}

// Legacy alias for backward compatibility
if (! function_exists('getLocalizedLabel')) {
    function getLocalizedLabel($item): string
    {
        return getLocalizedCatalogItemName($item);
    }
}


if (! function_exists('formatYearRange')) {
    function formatYearRange($begin, $end, $locale = null)
    {
        // dd([$begin, $end, $locale]); // للتحقق لاحقًا عند الحاجة
        $locale = $locale ?: app()->getLocale();

        // تطبيع القيم: null/""/"0"/0 => null
        $norm = function ($v) {
            if ($v === null) return null;
            if (is_string($v)) $v = trim($v);
            if ($v === '' || $v === '0') return null;

            // لو تاريخ كامل "YYYY-MM-DD" خذ السنة
            if (is_string($v) && preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $v)) {
                return (int) substr($v, 0, 4);
            }

            $v = (int) $v;
            return $v === 0 ? null : $v;
        };

        $b = $norm($begin);
        $e = $norm($end);

        // جميع الحالات المحتملة
        if ($b && $e)  return "{$b} - {$e}";
        if ($b && !$e) return $locale === 'ar' ? "{$b} - حتى الآن" : "{$b} - Present";
        if (!$b && $e) return (string) $e; // نادرة: بداية مفقودة
        return '—'; // كلاهما مفقود
    }
}


if (!function_exists('localizedPartLabel')) {
    function localizedPartLabel($label_en = null, $label_ar = null) {
        // dd($label_en, $label_ar); // فحص سريع عند الحاجة
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            // عربي أولاً، وإن كان فارغ نرجع إنجليزي، ثم شرطة طويلة كبديل
            return $label_ar ?: ($label_en ?: '—');
        }

        // إنجليزي (وأي لغة أخرى) أولاً، ثم عربي، ثم شرطة طويلة
        return $label_en ?: ($label_ar ?: '—');
    }
}


if (! function_exists('catLabel')) {
    function catLabel($model): string {
        if (!$model) return '';
        return $model->localized_name
            ?? (app()->getLocale()==='ar' && !empty($model->name_ar) ? $model->name_ar : ($model->name ?? ''));
    }
}


function module($name)
{

    if ($name == "otp") {
        $otp = file_exists(base_path("/vendor/markury/src/Adapter/module/otp.txt"));
        if ($otp) {
            $data = file_get_contents(base_path("/vendor/markury/src/Adapter/module/otp.txt"));

            if ($data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    return false;
}
/**
 * يبني مسار ملفات الـ Spaces بناءً على التاريخ والفرع
 */
if (! function_exists('space_path')) {
    function space_path(string $directory): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');
        return "sync/{$year}/{$month}/{$day}/{$directory}";
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// MonetaryUnit Helpers - توحيد استدعاء العملة
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get MonetaryUnitService instance (SINGLE SOURCE OF TRUTH)
 *
 * This is the main entry point for all currency operations.
 * Use this helper instead of direct MonetaryUnit queries.
 *
 * Usage:
 *   monetaryUnit()->getCurrent()       // Get current monetary unit
 *   monetaryUnit()->format(100)        // Format with sign: "ر.س100.00"
 *   monetaryUnit()->convert(100)       // Convert from SAR to current
 *   monetaryUnit()->convertAndFormat() // Convert + format in one call
 *
 * @return \App\Domain\Platform\Services\MonetaryUnitService
 */
if (! function_exists('monetaryUnit')) {
    function monetaryUnit(): \App\Domain\Platform\Services\MonetaryUnitService
    {
        return app(\App\Domain\Platform\Services\MonetaryUnitService::class);
    }
}

/**
 * Format price with currency (convenience helper)
 *
 * @param float $amount Amount to format
 * @param bool $convert Whether to convert from base currency first
 * @return string Formatted price with currency sign
 */
if (! function_exists('formatPrice')) {
    function formatPrice(float $amount, bool $convert = false): string
    {
        if ($convert) {
            return monetaryUnit()->convertAndFormat($amount);
        }
        return monetaryUnit()->format($amount);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// PlatformSettings Helpers - UNIFIED SETTINGS SYSTEM
// ═══════════════════════════════════════════════════════════════════════════
// IMPORTANT: $gs is BANNED. Use these helpers instead.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get PlatformSettingsService instance (SINGLE SOURCE OF TRUTH)
 *
 * This is the main entry point for all platform settings.
 * DO NOT use $gs, Muaadhsetting, or any legacy access.
 *
 * Usage:
 *   platformSettings()->logo           // Get logo
 *   platformSettings()->site_name      // Get site name
 *   platformSettings()->get('key')     // Get any setting by key
 *
 * @return \App\Domain\Platform\Services\PlatformSettingsService
 */
if (! function_exists('platformSettings')) {
    function platformSettings(): \App\Domain\Platform\Services\PlatformSettingsService
    {
        return app(\App\Domain\Platform\Services\PlatformSettingsService::class);
    }
}

/**
 * Get the ThemeService instance for theme-related operations
 *
 * Usage:
 *   themeService()->get('theme_primary')     // Get theme setting
 *   themeService()->set('theme_primary', '#006c35')  // Set theme setting
 *   themeService()->applyPreset('saudi')     // Apply preset
 *   themeService()->generateCss()            // Generate CSS file
 *
 * @return \App\Domain\Platform\Services\ThemeService
 */
if (! function_exists('themeService')) {
    function themeService(): \App\Domain\Platform\Services\ThemeService
    {
        return app(\App\Domain\Platform\Services\ThemeService::class);
    }
}

/**
 * Get a specific platform setting value
 *
 * @param string $key Setting key (e.g., 'logo', 'site_name', 'is_maintain')
 * @param mixed $default Default value if not found
 * @return mixed
 */
if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return platformSettings()->get($key, $default);
    }
}

/**
 * Get a setting from a specific group
 *
 * DATA FLOW POLICY: Uses PlatformSettingsService (not direct Model access)
 *
 * @param string $group Group name (e.g., 'branding', 'mail', 'features')
 * @param string $key Setting key within the group
 * @param mixed $default Default value if not found
 * @return mixed
 */
if (! function_exists('groupSetting')) {
    function groupSetting(string $group, string $key, $default = null)
    {
        return platformSettings()->getGroupSetting($group, $key, $default);
    }
}