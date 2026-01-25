<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\NewCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * مدير جلسة الكتالوج الموحد
 * يوفر نقطة وصول واحدة لجميع عمليات الجلسة المتعلقة بالكتالوج والفلاتر
 */
class CatalogSessionManager
{
    /**
     * الحصول على الفلاتر المحددة من الجلسة
     */
    public function getSelectedFilters(): array
    {
        return Session::get('selected_filters', []);
    }

    /**
     * حفظ الفلاتر المحددة في الجلسة
     */
    public function setSelectedFilters(array $filters): void
    {
        Session::put('selected_filters', $filters);
    }

    /**
     * الحصول على VIN من الجلسة
     */
    public function getVin(): ?string
    {
        return Session::get('vin');
    }

    /**
     * حفظ VIN في الجلسة
     */
    public function setVin(string $vin): void
    {
        Session::put('vin', $vin);
    }

    /**
     * الحصول على بيانات الكتالوج الحالي
     */
    public function getCurrentCatalog(): ?array
    {
        return Session::get('current_catalog');
    }

    /**
     * حفظ بيانات الكتالوج الحالي
     */
    public function setCurrentCatalog(array $catalog): void
    {
        Session::put('current_catalog', $catalog);
    }

    /**
     * الحصول على أكواد المستوى الثالث المحملة مسبقاً
     */
    public function getAllowedLevel3Codes(): array
    {
        return array_values(array_filter(
            array_map('strval', (array) Session::get('preloaded_full_code', []))
        ));
    }

    /**
     * حفظ أكواد المستوى الثالث المحملة مسبقاً
     */
    public function setAllowedLevel3Codes(array $codes): void
    {
        Session::put('preloaded_full_code', $codes);
    }

    /**
     * استخراج معرفات عناصر المواصفات من الفلاتر
     */
    public function getSpecItemIds(Catalog $catalog): array
    {
        $filters = $this->getSelectedFilters();
        $specItemIds = [];

        foreach ($filters as $specKey => $filter) {
            if (in_array($specKey, ['year', 'month'])) {
                continue;
            }

            $valueId = is_array($filter) ? ($filter['value_id'] ?? null) : $filter;

            if (!$valueId) {
                continue;
            }

            $itemId = DB::table('specification_items')
                ->join('specifications', 'specification_items.specification_id', '=', 'specifications.id')
                ->where('specification_items.catalog_id', $catalog->id)
                ->where('specifications.name', $specKey)
                ->where('specification_items.value_id', $valueId)
                ->value('specification_items.id');

            if ($itemId) {
                $specItemIds[] = $itemId;
            }
        }

        return $specItemIds;
    }

    /**
     * استخراج تاريخ الفلتر من السنة والشهر
     */
    public function getFilterDate(): ?string
    {
        $filters = $this->getSelectedFilters();
        $year = $filters['year']['value_id'] ?? $filters['year'] ?? null;
        $month = $filters['month']['value_id'] ?? $filters['month'] ?? null;

        if ($year && $month) {
            $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
            return Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        }

        return null;
    }

    /**
     * الحصول على الفلاتر المحددة مع التسميات
     */
    public function getLabeledFilters(): array
    {
        return Session::get('selected_filters_labeled', []);
    }

    /**
     * حفظ الفلاتر المحددة مع التسميات
     */
    public function setLabeledFilters(array $filters): void
    {
        Session::put('selected_filters_labeled', $filters);
    }

    /**
     * مسح جميع بيانات الجلسة المتعلقة بالكتالوج
     */
    public function clearAll(): void
    {
        Session::forget([
            'current_catalog',
            'attributes',
            'selected_filters',
            'selected_filters_labeled',
            'filtered_level3_codes',
            'vin',
            'preloaded_full_code',
        ]);
    }

    /**
     * مسح الفلاتر فقط (مع الحفاظ على بيانات الكتالوج)
     */
    public function clearFilters(): void
    {
        Session::forget([
            'selected_filters',
            'selected_filters_labeled',
            'filtered_level3_codes',
        ]);
    }

    /**
     * تحميل معلومات Brand و Catalog معاً بـ eager loading
     * لتجنب تكرار الاستعلامات
     */
    public function loadBrandAndCatalog(string $brandName, string $catalogCode): array
    {
        $brand = Brand::with('regions')
            ->where('name', $brandName)
            ->first();

        if (!$brand) {
            return ['brand' => null, 'catalog' => null];
        }

        $catalog = Catalog::with(['brand', 'brandRegion'])
            ->where('code', $catalogCode)
            ->where('brand_id', $brand->id)
            ->first();

        return [
            'brand' => $brand,
            'catalog' => $catalog,
        ];
    }

    /**
     * تحميل NewCategory بكافة العلاقات الضرورية
     * لتقليل الاستعلامات في مكونات Livewire
     */
    public function loadCategoryWithRelations(int $catalogId, int $brandId, string $fullCode, int $level)
    {
        return NewCategory::with(['catalog', 'brand', 'periods'])
            ->where('catalog_id', $catalogId)
            ->where('brand_id', $brandId)
            ->where('full_code', $fullCode)
            ->where('level', $level)
            ->first();
    }
}
