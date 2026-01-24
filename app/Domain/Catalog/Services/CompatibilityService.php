<?php

namespace App\Domain\Catalog\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة موحدة لإدارة التوافق
 *
 * ✅ محسّن: استخدام الفهارس على parts_index.part_number
 */
class CompatibilityService
{
    /**
     * جلب الكتالوجات المتوافقة مع رقم القطعة
     *
     * ✅ محسّن: استعلام محسّن مع select محدد
     */
    public function getCompatibleCatalogs(string $part_number): Collection
    {
        // ✅ استخدام فهرس part_number مباشرة
        $parts = DB::table('parts_index as pi')
            ->join('catalogs as c', 'c.code', '=', 'pi.catalog_code')
            ->where('pi.part_number', $part_number)
            ->select(
                'pi.catalog_code',
                'c.label_en',
                'c.label_ar',
                'c.beginYear',
                'c.endYear'
            )
            ->get();

        $isArabic = app()->getLocale() === 'ar';

        return $parts->map(function ($part) use ($part_number, $isArabic) {
            return (object)[
                'part_number'   => $part_number,
                'catalog_code'  => $part->catalog_code,
                'label'         => $isArabic ? $part->label_ar : $part->label_en,
                'label_en'      => $part->label_en,
                'label_ar'      => $part->label_ar,
                'begin_year'    => $part->beginYear,
                'end_year'      => $part->endYear ?: __('Until now'),
            ];
        });
    }

    /**
     * التحقق من توافق قطعة مع كتالوج معين
     *
     * ✅ محسّن: استخدام فهرس مركب (part_number, catalog_code)
     */
    public function isCompatibleWith(string $part_number, string $catalogCode): bool
    {
        return DB::table('parts_index')
            ->where('part_number', $part_number)
            ->where('catalog_code', $catalogCode)
            ->limit(1)
            ->exists();
    }

    /**
     * عدد الكتالوجات المتوافقة
     *
     * ✅ محسّن: COUNT DISTINCT على catalog_code فقط
     */
    public function countCompatibleCatalogs(string $part_number): int
    {
        return (int) DB::table('parts_index')
            ->where('part_number', $part_number)
            ->distinct()
            ->count('catalog_code');
    }

    /**
     * جلب أكواد الكتالوجات المتوافقة فقط
     *
     * ✅ محسّن: استعلام خفيف مع distinct
     */
    public function getCompatibleCatalogCodes(string $part_number): array
    {
        return DB::table('parts_index')
            ->where('part_number', $part_number)
            ->distinct()
            ->pluck('catalog_code')
            ->toArray();
    }

    /**
     * جلب معلومات التوافق المفصلة
     *
     * ✅ محسّن: JOIN محسّن مع select محدد
     */
    public function getDetailedCompatibility(string $part_number): Collection
    {
        $isArabic = app()->getLocale() === 'ar';

        return DB::table('parts_index as pi')
            ->join('catalogs as c', 'c.code', '=', 'pi.catalog_code')
            ->leftJoin('brands as b', 'b.id', '=', 'c.brand_id')
            ->where('pi.part_number', $part_number)
            ->select(
                'pi.catalog_code',
                'c.label_en as catalog_label_en',
                'c.label_ar as catalog_label_ar',
                'c.beginYear',
                'c.endYear',
                'b.name as brand_name',
                'b.logo as brand_logo'
            )
            ->get()
            ->map(function ($item) use ($part_number, $isArabic) {
                return (object)[
                    'part_number'     => $part_number,
                    'catalog_code'    => $item->catalog_code,
                    'catalog_label'   => $isArabic ? $item->catalog_label_ar : $item->catalog_label_en,
                    'brand_name'      => $item->brand_name,
                    'brand_logo'      => $item->brand_logo,
                    'year_range'      => $this->formatYearRange($item->beginYear, $item->endYear),
                ];
            });
    }

    /**
     * ✅ جلب التوافق لمجموعة من الـ SKUs (batch)
     */
    public function getCompatibleCatalogsBatch(array $skus): Collection
    {
        if (empty($skus)) return collect();

        $parts = DB::table('parts_index as pi')
            ->join('catalogs as c', 'c.code', '=', 'pi.catalog_code')
            ->whereIn('pi.part_number', $skus)
            ->select(
                'pi.part_number',
                'pi.catalog_code',
                'c.label_en',
                'c.label_ar',
                'c.beginYear',
                'c.endYear'
            )
            ->get();

        $isArabic = app()->getLocale() === 'ar';

        return $parts->map(function ($part) use ($isArabic) {
            return (object)[
                'part_number'   => $part->part_number,
                'catalog_code'  => $part->catalog_code,
                'label'         => $isArabic ? $part->label_ar : $part->label_en,
                'label_en'      => $part->label_en,
                'label_ar'      => $part->label_ar,
                'begin_year'    => $part->beginYear,
                'end_year'      => $part->endYear ?: __('Until now'),
            ];
        })->groupBy('part_number');
    }

    /**
     * تنسيق نطاق السنوات
     */
    protected function formatYearRange(?string $beginYear, ?string $endYear): string
    {
        if (!$beginYear && !$endYear) {
            return '-';
        }

        $begin = $beginYear ?: '?';
        $end = $endYear ?: __('now');

        return "{$begin} - {$end}";
    }
}
