<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة تحميل عُقد الفئات (Nodes) الجاهزة للعرض
 * كل Node تحتوي على مفاتيح التوجيه (key1, key2, key3) من نفس السجل
 */
class CategoryFilterService
{
    /**
     * الحصول على أكواد المستوى الثالث المفلترة
     */
    public function getFilteredLevel3FullCodes(
        Catalog $catalog,
        Brand $brand,
        ?string $filterDate,
        array $specItemIds
    ): array {
        $query = DB::table('newcategories as nc')
            ->join('category_periods as cp', 'cp.category_id', '=', 'nc.id')
            ->where('nc.catalog_id', $catalog->id)
            ->where('nc.brand_id', $brand->id)
            ->where('nc.level', 3)
            ->whereNotNull('nc.full_code')
            ->where('nc.full_code', '!=', '')
            ->when($filterDate, fn($q) => $this->applyDateFilter($q, $filterDate));

        if (!empty($specItemIds)) {
            $query->whereExists(function ($q) use ($catalog, $specItemIds) {
                $q->from('category_spec_groups as csg2')
                    ->whereColumn('csg2.category_id', 'nc.id')
                    ->where('csg2.catalog_id', $catalog->id)
                    ->whereNotExists(function ($sub) use ($specItemIds) {
                        $sub->from('category_spec_group_items as csgi2')
                            ->whereColumn('csgi2.group_id', 'csg2.id')
                            ->whereNotIn('csgi2.specification_item_id', $specItemIds);
                    });
            });
        }

        return $query->distinct()->pluck('nc.full_code')->toArray();
    }

    /**
     * تحميل عُقد المستوى الأول
     * كل Node تحتوي على: key1 (مفتاح التوجيه)
     */
    public function loadLevel1Nodes(
        Catalog $catalog,
        Brand $brand,
        ?string $filterDate,
        array $allowedLevel3Codes
    ): Collection {
        $labelField = $this->getLabelField();

        return DB::table('newcategories as n')
            ->join('newcategories as level2', 'level2.parent_id', '=', 'n.id')
            ->join('newcategories as level3', 'level3.parent_id', '=', 'level2.id')
            ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
            ->where('n.catalog_id', $catalog->id)
            ->where('n.brand_id', $brand->id)
            ->where('n.level', 1)
            ->whereNull('n.parent_id')
            ->whereNotNull('n.full_code')
            ->where('n.full_code', '!=', '')
            ->when($filterDate, fn($q) => $this->applyDateFilter($q, $filterDate))
            ->when(!empty($allowedLevel3Codes), fn($q) => $q->whereIn('level3.full_code', $allowedLevel3Codes))
            ->select([
                'n.id',
                'n.full_code as key1',
                "n.{$labelField} as label",
                'n.slug',
                'n.thumbnail',
                'n.images',
                'n.formatted_code',
            ])
            ->distinct()
            ->orderBy('n.full_code')
            ->get();
    }

    /**
     * تحميل عُقد المستوى الثاني
     * كل Node تحتوي على: key1 (من الأب) + key2 (مفتاح التوجيه)
     */
    public function loadLevel2Nodes(
        Catalog $catalog,
        Brand $brand,
        string $parentKey1,
        ?string $filterDate,
        array $specItemIds
    ): Collection {
        // البحث عن الفئة الأب
        $parent = $this->findCategory($catalog, $brand, $parentKey1, 1);
        if (!$parent) {
            return collect();
        }

        $query = DB::table('newcategories as n')
            ->join('newcategories as parent', 'parent.id', '=', 'n.parent_id')
            ->where('n.catalog_id', $catalog->id)
            ->where('n.brand_id', $brand->id)
            ->where('n.parent_id', $parent->id)
            ->where('n.level', 2)
            ->whereNotNull('n.full_code')
            ->where('n.full_code', '!=', '')
            ->whereNotNull('parent.full_code')
            ->where('parent.full_code', '!=', '');

        // تطبيق فلتر المواصفات
        if (!empty($specItemIds)) {
            $this->applySpecFilterForLevel2($query, $catalog, $brand, $filterDate, $specItemIds);
        } else {
            $this->applyDateFilterForLevel2($query, $catalog, $brand, $filterDate);
            $query->addSelect([
                'n.id',
                'parent.full_code as key1',
                'n.full_code as key2',
                'n.label_en',
                'n.label_ar',
                'n.slug',
                'n.thumbnail',
                'n.images',
                DB::raw('NULL as matched_group_index'),
            ])->orderBy('n.full_code');
        }

        return $query->get()->map(fn($row) => $this->addLocalizedLabel($row));
    }

    /**
     * تحميل عُقد المستوى الثالث
     * كل Node تحتوي على: key1 + key2 (من الآباء) + key3 (مفتاح التوجيه)
     */
    public function loadLevel3Nodes(
        Catalog $catalog,
        Brand $brand,
        string $parentKey1,
        string $parentKey2,
        ?string $filterDate,
        array $specItemIds
    ): Collection {
        // البحث عن الفئة الأب
        $parent2 = $this->findCategory($catalog, $brand, $parentKey2, 2);
        if (!$parent2) {
            return collect();
        }

        $baseQuery = DB::table('newcategories as n')
            ->join('category_periods as cp', 'cp.category_id', '=', 'n.id')
            ->join('newcategories as parent2', 'parent2.id', '=', 'n.parent_id')
            ->join('newcategories as parent1', 'parent1.id', '=', 'parent2.parent_id')
            ->where('n.catalog_id', $catalog->id)
            ->where('n.brand_id', $brand->id)
            ->where('n.parent_id', $parent2->id)
            ->where('n.level', 3)
            ->whereNotNull('n.full_code')
            ->where('n.full_code', '!=', '')
            ->whereNotNull('parent1.full_code')
            ->where('parent1.full_code', '!=', '')
            ->whereNotNull('parent2.full_code')
            ->where('parent2.full_code', '!=', '')
            ->when($filterDate, fn($q) => $this->applyDateFilter($q, $filterDate));

        $selectColumns = [
            'n.id',
            'parent1.full_code as key1',
            'parent2.full_code as key2',
            'n.full_code as key3',
            'n.formatted_code',
            'n.label_en',
            'n.label_ar',
            'n.slug',
            'n.thumbnail',
            'n.images',
            'n.Applicability',
        ];

        $groupByColumns = [
            'n.id', 'n.full_code', 'n.formatted_code', 'n.label_en', 'n.label_ar',
            'n.slug', 'n.thumbnail', 'n.images', 'n.Applicability',
            'parent1.full_code', 'parent2.full_code', 'cp.begin_date', 'cp.end_date',
        ];

        if (!empty($specItemIds)) {
            $matchedGroupIndexSql = $this->buildMatchedGroupIndexSql($catalog->id, $specItemIds);

            $baseQuery
                ->whereRaw("{$matchedGroupIndexSql} IS NOT NULL")
                ->select(array_merge($selectColumns, [DB::raw("{$matchedGroupIndexSql} as matched_group_index")]))
                ->groupBy($groupByColumns)
                ->orderByDesc('matched_group_index');
        } else {
            $baseQuery
                ->select(array_merge($selectColumns, [DB::raw('NULL as matched_group_index')]))
                ->groupBy($groupByColumns)
                ->orderBy('n.full_code');
        }

        return $baseQuery->get()->map(fn($row) => $this->addLocalizedLabel($row));
    }

    /**
     * التحقق من وجود الفئة الأب
     */
    public function findCategory(Catalog $catalog, Brand $brand, string $fullCode, int $level): ?object
    {
        return DB::table('newcategories')
            ->where('catalog_id', $catalog->id)
            ->where('brand_id', $brand->id)
            ->where('full_code', $fullCode)
            ->where('level', $level)
            ->whereNotNull('full_code')
            ->where('full_code', '!=', '')
            ->first();
    }

    /**
     * حساب الأكواد المسموح بها
     */
    public function computeAllowedCodesForSections(Collection $nodes, array $preloadedCodes): array
    {
        if ($nodes->isEmpty()) {
            return [];
        }

        $level3Codes = DB::table('newcategories')
            ->whereIn('parent_id', $nodes->pluck('id')->toArray())
            ->where('level', 3)
            ->pluck('full_code')
            ->map(fn($v) => (string)$v)
            ->toArray();

        return array_values(array_intersect($level3Codes, array_map('strval', $preloadedCodes)));
    }

    // ========================================
    // PRIVATE HELPERS
    // ========================================

    private function getLabelField(): string
    {
        return app()->getLocale() === 'ar' ? 'label_ar' : 'label_en';
    }

    private function addLocalizedLabel(object $row): object
    {
        $row->label = app()->getLocale() === 'ar'
            ? ($row->label_ar ?? $row->label_en)
            : ($row->label_en ?? $row->label_ar);
        return $row;
    }

    private function applyDateFilter($query, string $filterDate): void
    {
        $query->where('cp.begin_date', '<=', $filterDate)
            ->where(function ($sub) use ($filterDate) {
                $sub->whereNull('cp.end_date')
                    ->orWhere('cp.end_date', '>=', $filterDate);
            });
    }

    private function applyDateFilterForLevel2($query, Catalog $catalog, Brand $brand, ?string $filterDate): void
    {
        if (!$filterDate) return;

        $query->whereExists(function ($q) use ($catalog, $brand, $filterDate) {
            $q->from('newcategories as l3')
                ->join('category_periods as cp2', 'cp2.category_id', '=', 'l3.id')
                ->whereColumn('l3.parent_id', 'n.id')
                ->where('l3.catalog_id', $catalog->id)
                ->where('l3.brand_id', $brand->id)
                ->where('l3.level', 3)
                ->where('cp2.begin_date', '<=', $filterDate)
                ->where(function ($sub) use ($filterDate) {
                    $sub->whereNull('cp2.end_date')
                        ->orWhere('cp2.end_date', '>=', $filterDate);
                });
        });
    }

    private function applySpecFilterForLevel2($query, Catalog $catalog, Brand $brand, ?string $filterDate, array $specItemIds): void
    {
        $idsList = implode(',', array_map('intval', $specItemIds));

        $dateJoin = $filterDate ? "JOIN category_periods cp2 ON cp2.category_id = l3.id" : "";
        $dateWhere = $filterDate
            ? "AND cp2.begin_date <= '{$filterDate}' AND (cp2.end_date IS NULL OR cp2.end_date >= '{$filterDate}')"
            : "";

        $matchedGroupIndexSql = "
            (SELECT MAX(csg2.group_index)
             FROM newcategories l3
             JOIN category_spec_groups csg2 ON csg2.category_id = l3.id AND csg2.catalog_id = {$catalog->id}
             {$dateJoin}
             WHERE l3.parent_id = n.id
               AND l3.catalog_id = {$catalog->id}
               AND l3.brand_id = {$brand->id}
               AND l3.level = 3
               {$dateWhere}
               AND NOT EXISTS (
                   SELECT 1 FROM category_spec_group_items csgi2
                   WHERE csgi2.group_id = csg2.id
                     AND csgi2.specification_item_id NOT IN ({$idsList})
               ))
        ";

        $query
            ->addSelect([
                'n.id',
                'parent.full_code as key1',
                'n.full_code as key2',
                'n.label_en',
                'n.label_ar',
                'n.slug',
                'n.thumbnail',
                'n.images',
                DB::raw("{$matchedGroupIndexSql} as matched_group_index"),
            ])
            ->whereRaw("{$matchedGroupIndexSql} IS NOT NULL")
            ->orderByDesc(DB::raw($matchedGroupIndexSql));
    }

    private function buildMatchedGroupIndexSql(int $catalogId, array $specItemIds): string
    {
        $idsList = implode(',', array_map('intval', $specItemIds));

        return "
            (SELECT MAX(csg2.group_index)
             FROM category_spec_groups csg2
             WHERE csg2.category_id = n.id
               AND csg2.catalog_id = {$catalogId}
               AND NOT EXISTS (
                   SELECT 1 FROM category_spec_group_items csgi2
                   WHERE csgi2.group_id = csg2.id
                     AND csgi2.specification_item_id NOT IN ({$idsList})
               ))
        ";
    }

}
