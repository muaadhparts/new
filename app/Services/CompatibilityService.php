<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة موحدة لإدارة التوافق
 */
class CompatibilityService
{
    /**
     * جلب الكتالوجات المتوافقة مع رقم القطعة
     */
    public function getCompatibleCatalogs(string $sku): Collection
    {
        $parts = DB::table('parts_index')
            ->join('catalogs', 'catalogs.code', '=', 'parts_index.catalog_code')
            ->select(
                'parts_index.part_number',
                'parts_index.catalog_code',
                'catalogs.label_en',
                'catalogs.label_ar',
                'catalogs.beginYear',
                'catalogs.endYear'
            )
            ->where('parts_index.part_number', $sku)
            ->get();

        return $parts->map(function ($part) {
            return (object)[
                'part_number'   => $part->part_number,
                'catalog_code'  => $part->catalog_code,
                'label'         => app()->getLocale() === 'ar' ? $part->label_ar : $part->label_en,
                'label_en'      => $part->label_en,
                'label_ar'      => $part->label_ar,
                'begin_year'    => $part->beginYear,
                'end_year'      => $part->endYear ?: __('Until now'),
            ];
        });
    }

    /**
     * التحقق من توافق قطعة مع كتالوج معين
     */
    public function isCompatibleWith(string $sku, string $catalogCode): bool
    {
        return DB::table('parts_index')
            ->where('part_number', $sku)
            ->where('catalog_code', $catalogCode)
            ->exists();
    }

    /**
     * عدد الكتالوجات المتوافقة
     */
    public function countCompatibleCatalogs(string $sku): int
    {
        return DB::table('parts_index')
            ->where('part_number', $sku)
            ->distinct('catalog_code')
            ->count('catalog_code');
    }

    /**
     * جلب أكواد الكتالوجات المتوافقة فقط
     */
    public function getCompatibleCatalogCodes(string $sku): array
    {
        return DB::table('parts_index')
            ->where('part_number', $sku)
            ->distinct()
            ->pluck('catalog_code')
            ->toArray();
    }

    /**
     * جلب معلومات التوافق المفصلة
     */
    public function getDetailedCompatibility(string $sku): Collection
    {
        return DB::table('parts_index as pi')
            ->join('catalogs as c', 'c.code', '=', 'pi.catalog_code')
            ->leftJoin('brands as b', 'b.id', '=', 'c.brand_id')
            ->select(
                'pi.part_number',
                'pi.catalog_code',
                'c.label_en as catalog_label_en',
                'c.label_ar as catalog_label_ar',
                'c.beginYear',
                'c.endYear',
                'b.name as brand_name',
                'b.logo as brand_logo'
            )
            ->where('pi.part_number', $sku)
            ->get()
            ->map(function ($item) {
                return (object)[
                    'part_number'     => $item->part_number,
                    'catalog_code'    => $item->catalog_code,
                    'catalog_label'   => app()->getLocale() === 'ar' ? $item->catalog_label_ar : $item->catalog_label_en,
                    'brand_name'      => $item->brand_name,
                    'brand_logo'      => $item->brand_logo,
                    'year_range'      => $this->formatYearRange($item->beginYear, $item->endYear),
                ];
            });
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
