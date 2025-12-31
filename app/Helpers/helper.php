<?php

function merchantCompareCheck($merchant_item_id)
{
    $compare = session('compare');
    if (!$compare || !isset($compare->items)) {
        return false;
    }
    return isset($compare->items[$merchant_item_id]);
}

function getMerchantDisplayName($merchantItem)
{
    if (!$merchantItem || !$merchantItem->user) {
        return '';
    }

    $vendor = $merchantItem->user;
    $displayName = $vendor->shop_name ?: $vendor->name;

    // Add brand quality if available
    if ($merchantItem->qualityBrand) {
        $displayName .= ' (' . $merchantItem->qualityBrand->display_name . ')';
    }

    return $displayName;
}


/**
 * Get localized product name from cart item array or Product model
 * Supports both array format (cart) and object format (Product model)
 */
if (! function_exists('getLocalizedProductName')) {
    function getLocalizedProductName($item, $maxLength = null): string
    {
        $isAr = app()->getLocale() === 'ar';

        // Handle array format (cart item)
        if (is_array($item)) {
            $labelAr = trim($item['label_ar'] ?? '');
            $labelEn = trim($item['label_en'] ?? '');
            $name = trim($item['name'] ?? '');
        }
        // Handle object format (Product model)
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
 * Get localized shop name from vendor/user object
 * Handles both User model and query result objects
 */
if (! function_exists('getLocalizedShopName')) {
    function getLocalizedShopName($vendor): string
    {
        if (!$vendor) return '';

        $isAr = app()->getLocale() === 'ar';

        if (is_array($vendor)) {
            $shopNameAr = trim($vendor['shop_name_ar'] ?? '');
            $shopName = trim($vendor['shop_name'] ?? '');
        } elseif (is_object($vendor)) {
            $shopNameAr = trim($vendor->shop_name_ar ?? '');
            $shopName = trim($vendor->shop_name ?? '');
        } else {
            return '';
        }

        if ($isAr && $shopNameAr !== '') {
            return $shopNameAr;
        }
        return $shopName !== '' ? $shopName : __('Unknown Vendor');
    }
}

/**
 * Get vendor/store name from cart product or MerchantItem
 * Now with localization support
 */
if (! function_exists('getVendorName')) {
    function getVendorName($product): string
    {
        if (!$product) return '';

        $isAr = app()->getLocale() === 'ar';

        // From cart array
        if (is_array($product)) {
            if ($isAr && !empty($product['shop_name_ar'])) {
                return $product['shop_name_ar'];
            }
            return $product['vendor_name'] ?? $product['shop_name'] ?? '';
        }

        // From MerchantItem or similar object
        if (is_object($product)) {
            if (isset($product->user) && $product->user) {
                if ($isAr && !empty($product->user->shop_name_ar)) {
                    return $product->user->shop_name_ar;
                }
                return $product->user->shop_name ?? $product->user->name ?? '';
            }
            if ($isAr && isset($product->shop_name_ar) && !empty($product->shop_name_ar)) {
                return $product->shop_name_ar;
            }
            if (isset($product->vendor_name)) {
                return $product->vendor_name;
            }
            if (isset($product->shop_name)) {
                return $product->shop_name;
            }
        }

        return '';
    }
}

/**
 * Get manufacturer name from product
 */
if (! function_exists('getManufacturerName')) {
    function getManufacturerName($product): string
    {
        if (!$product) return '';

        // From array
        if (is_array($product)) {
            $item = $product['item'] ?? $product;
            return $item['manufacturer'] ?? $item['manufacturer_name'] ?? '';
        }

        // From object
        if (is_object($product)) {
            // If has manufacturer relationship
            if (isset($product->manufacturer) && $product->manufacturer) {
                return $product->manufacturer->name ?? '';
            }
            // If has manufacturer_name attribute
            if (isset($product->manufacturer_name)) {
                return $product->manufacturer_name;
            }
            // If has manufacturer attribute
            if (isset($product->manufacturer)) {
                return $product->manufacturer;
            }
        }

        return '';
    }
}

// Legacy alias for backward compatibility
if (! function_exists('getLocalizedLabel')) {
    function getLocalizedLabel($item): string
    {
        return getLocalizedProductName($item);
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


function addon($name)
{

    if ($name == "otp") {
        $otp = file_exists(base_path("/vendor/markury/src/Adapter/addon/otp.txt"));
        if ($otp) {
            $data = file_get_contents(base_path("/vendor/markury/src/Adapter/addon/otp.txt"));

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