<?php

function wishlistCheck($product_id, $merchant_product_id = null)
{
    $query = \App\Models\Wishlist::where('user_id', auth()->id());

    if ($merchant_product_id) {
        // Check for specific merchant product
        $query->where('merchant_product_id', $merchant_product_id);
    } else {
        // Legacy: check if any vendor of this product is in wishlist
        $query->where('product_id', $product_id);
    }

    return $query->exists();
}

function merchantWishlistCheck($merchant_product_id)
{
    return \App\Models\Wishlist::where('merchant_product_id', $merchant_product_id)
        ->where('user_id', auth()->id())
        ->exists();
}

function merchantCompareCheck($merchant_product_id)
{
    $compare = session('compare');
    if (!$compare || !isset($compare->items)) {
        return false;
    }
    return isset($compare->items[$merchant_product_id]);
}

function getMerchantDisplayName($merchantProduct)
{
    if (!$merchantProduct || !$merchantProduct->user) {
        return '';
    }

    $vendor = $merchantProduct->user;
    $displayName = $vendor->shop_name ?: $vendor->name;

    // Add brand quality if available
    if ($merchantProduct->qualityBrand) {
        $displayName .= ' (' . $merchantProduct->qualityBrand->display_name . ')';
    }

    return $displayName;
}


if (! function_exists('getLocalizedLabel')) {
    function getLocalizedLabel($item): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar') {
            return $item['label_ar'] ?: $item['label_en'];
        }
        return $item['label_en'] ?: $item['label_ar'];
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