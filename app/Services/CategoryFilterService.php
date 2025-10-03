<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة فلترة الفئات الموحدة
 * تحتوي على منطق الفلترة المشترك لجميع مستويات الشجرة
 */
class CategoryFilterService
{
    /**
     * الحصول على أكواد المستوى الثالث المفلترة بناءً على التاريخ والمواصفات
     */
    public function getFilteredLevel3FullCodes(
        Catalog $catalog,
        Brand $brand,
        ?string $filterDate,
        array $specItemIds
    ): array {
        // لو ما فيه مواصفات ممرّرة: فلترة بالتاريخ فقط (إن وُجد)
        if (empty($specItemIds)) {
            return DB::table('newcategories as nc')
                ->join('category_periods as cp', 'cp.category_id', '=', 'nc.id')
                ->where('nc.catalog_id', $catalog->id)
                ->where('nc.brand_id', $brand->id)
                ->where('nc.level', 3)
                ->when($filterDate, fn($q) =>
                    $q->where('cp.begin_date', '<=', $filterDate)
                    ->where(function ($sub) use ($filterDate) {
                        $sub->whereNull('cp.end_date')
                            ->orWhere('cp.end_date', '>=', $filterDate);
                    })
                )
                ->distinct()
                ->pluck('nc.full_code')
                ->toArray();
        }

        // يوجد مواصفات: نطبق "الوتر الحساس" — كل قيم القروب ⊆ specItemIds
        return DB::table('newcategories as nc')
            ->join('category_periods as cp', 'cp.category_id', '=', 'nc.id')
            ->where('nc.catalog_id', $catalog->id)
            ->where('nc.brand_id', $brand->id)
            ->where('nc.level', 3)
            ->when($filterDate, fn($q) =>
                $q->where('cp.begin_date', '<=', $filterDate)
                ->where(function ($sub) use ($filterDate) {
                    $sub->whereNull('cp.end_date')
                        ->orWhere('cp.end_date', '>=', $filterDate);
                })
            )
            // يجب أن يوجد قروب واحد على الأقل يحقق subset
            ->whereExists(function ($q) use ($catalog, $specItemIds) {
                $q->from('category_spec_groups as csg2')
                ->whereColumn('csg2.category_id', 'nc.id')
                ->where('csg2.catalog_id', $catalog->id)
                // لا توجد قيمة واحدة في هذا القروب خارج المواصفات الممرّرة
                ->whereNotExists(function ($sub) use ($specItemIds) {
                    $sub->from('category_spec_group_items as csgi2')
                        ->whereColumn('csgi2.group_id', 'csg2.id')
                        ->whereNotIn('csgi2.specification_item_id', $specItemIds);
                });
            })
            ->distinct()
            ->pluck('nc.full_code')
            ->toArray();
    }

    /**
     * تحميل فئات المستوى الأول
     */
    public function loadLevel1Categories(
        Catalog $catalog,
        Brand $brand,
        string $labelField,
        ?string $filterDate,
        array $allowedLevel3Codes
    ): Collection {
        return DB::table('newcategories as level1')
            ->join('newcategories as level2', 'level2.parent_id', '=', 'level1.id')
            ->join('newcategories as level3', 'level3.parent_id', '=', 'level2.id')
            ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
            ->where('level1.catalog_id', $catalog->id)
            ->where('level1.brand_id', $brand->id)
            ->where('level1.level', 1)
            ->whereNull('level1.parent_id')
            ->when($filterDate, fn($q) =>
                $q->where('cp.begin_date', '<=', $filterDate)
                    ->where(function ($sub) use ($filterDate) {
                        $sub->whereNull('cp.end_date')->orWhere('cp.end_date', '>=', $filterDate);
                    })
            )
            ->when(!empty($allowedLevel3Codes), fn($q) =>
                $q->whereIn('level3.full_code', $allowedLevel3Codes)
            )
            ->select('level1.id', 'level1.full_code', "level1.{$labelField} as label", 'level1.slug', 'level1.thumbnail', 'level1.images')
            ->distinct()
            ->orderBy('level1.full_code')
            ->get();
    }

    /**
     * تحميل فئات المستوى الثاني مع الفلترة
     */
    public function loadLevel2Categories(
        Catalog $catalog,
        Brand $brand,
        NewCategory $parentCategory,
        ?string $filterDate,
        array $specItemIds
    ): Collection {
        $query = DB::table('newcategories as level2')
            ->where('level2.catalog_id', $catalog->id)
            ->where('level2.brand_id', $brand->id)
            ->where('level2.parent_id', $parentCategory->id)
            ->where('level2.level', 2);

        // إن كانت هناك مواصفات: نطبق "الوتر الحساس" على أي قروب في أي طفل Level3
        if (!empty($specItemIds)) {
            $idsList = implode(',', array_map('intval', $specItemIds));

            $matchedGroupIndexSql = "
                (
                    SELECT MAX(csg2.group_index)
                    FROM newcategories l3
                    JOIN category_spec_groups csg2
                    ON csg2.category_id = l3.id AND csg2.catalog_id = {$catalog->id}
                    " . ($filterDate ? "
                    JOIN category_periods cp2
                    ON cp2.category_id = l3.id
                    " : "") . "
                    WHERE l3.parent_id = level2.id
                    AND l3.catalog_id = {$catalog->id}
                    AND l3.brand_id = {$brand->id}
                    AND l3.level = 3
                    " . ($filterDate ? "
                    AND cp2.begin_date <= '{$filterDate}'
                    AND (cp2.end_date IS NULL OR cp2.end_date >= '{$filterDate}')
                    " : "") . "
                    AND NOT EXISTS (
                        SELECT 1 FROM category_spec_group_items csgi2
                        WHERE csgi2.group_id = csg2.id
                            AND csgi2.specification_item_id NOT IN ({$idsList})
                    )
                )
            ";

            $query
                ->addSelect(
                    'level2.id',
                    'level2.full_code',
                    'level2.label_en',
                    'level2.label_ar',
                    'level2.thumbnail',
                    'level2.images',
                    'level2.level',
                    'level2.slug',
                    DB::raw("{$matchedGroupIndexSql} as matched_group_index")
                )
                ->whereRaw("{$matchedGroupIndexSql} IS NOT NULL")
                ->orderByDesc('matched_group_index');

        } else {
            // لا توجد مواصفات: فقط نضمن وجود طفل Level3 يغطي التاريخ (إن وُجد تاريخ)
            if ($filterDate) {
                $query->whereExists(function ($q) use ($catalog, $brand, $filterDate) {
                    $q->from('newcategories as l3')
                    ->join('category_periods as cp2', 'cp2.category_id', '=', 'l3.id')
                    ->whereColumn('l3.parent_id', 'level2.id')
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

            $query->addSelect(
                'level2.id',
                'level2.full_code',
                'level2.label_en',
                'level2.label_ar',
                'level2.thumbnail',
                'level2.images',
                'level2.level',
                'level2.slug',
                DB::raw('NULL as matched_group_index')
            )
            ->orderBy('level2.full_code', 'asc');
        }

        return $query->get();
    }

    /**
     * تحميل فئات المستوى الثالث مع الفلترة
     */
    public function loadLevel3Categories(
        Catalog $catalog,
        Brand $brand,
        NewCategory $parentCategory2,
        ?string $filterDate,
        array $specItemIds
    ): Collection {
        // إذا ما فيه مواصفات ممرّرة: نحتفظ بمنطق التاريخ فقط ونرجّع كل Level3 تحت الأب
        if (empty($specItemIds)) {
            return DB::table('newcategories as level3')
                ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
                ->where('level3.catalog_id', $catalog->id)
                ->where('level3.brand_id', $brand->id)
                ->where('level3.parent_id', $parentCategory2->id)
                ->where('level3.level', 3)
                ->when($filterDate, fn($q) =>
                    $q->where('cp.begin_date', '<=', $filterDate)
                    ->where(function ($sub) use ($filterDate) {
                        $sub->whereNull('cp.end_date')
                            ->orWhere('cp.end_date', '>=', $filterDate);
                    })
                )
                ->select(
                    'level3.id',
                    'level3.full_code',
                    'level3.label_en',
                    'level3.label_ar',
                    'level3.thumbnail',
                    'level3.images',
                    'level3.level',
                    'level3.slug',
                    DB::raw('NULL as matched_group_index'),
                    'cp.begin_date as debug_begin',
                    'cp.end_date as debug_end'
                )
                ->groupBy(
                    'level3.id',
                    'level3.full_code',
                    'level3.label_en',
                    'level3.label_ar',
                    'level3.thumbnail',
                    'level3.images',
                    'level3.level',
                    'level3.slug',
                    'cp.begin_date',
                    'cp.end_date'
                )
                ->orderBy('level3.full_code', 'asc')
                ->get();
        }

        // الوتر الحساس: نبني Subquery يعيد أعلى group_index بين القروبات المطابقة (subset)
        $idsList = implode(',', array_map('intval', $specItemIds));
        $matchedGroupIndexSql = "
            (
                SELECT MAX(csg2.group_index)
                FROM category_spec_groups csg2
                WHERE csg2.category_id = level3.id
                AND csg2.catalog_id = {$catalog->id}
                AND NOT EXISTS (
                    SELECT 1
                    FROM category_spec_group_items csgi2
                    WHERE csgi2.group_id = csg2.id
                        AND csgi2.specification_item_id NOT IN ({$idsList})
                )
            )
        ";

        return DB::table('newcategories as level3')
            ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
            ->where('level3.catalog_id', $catalog->id)
            ->where('level3.brand_id', $brand->id)
            ->where('level3.parent_id', $parentCategory2->id)
            ->where('level3.level', 3)
            ->when($filterDate, fn($q) =>
                $q->where('cp.begin_date', '<=', $filterDate)
                ->where(function ($sub) use ($filterDate) {
                    $sub->whereNull('cp.end_date')
                        ->orWhere('cp.end_date', '>=', $filterDate);
                })
            )
            ->whereRaw("{$matchedGroupIndexSql} IS NOT NULL")
            ->select(
                'level3.id',
                'level3.full_code',
                'level3.label_en',
                'level3.label_ar',
                'level3.thumbnail',
                'level3.images',
                'level3.level',
                'level3.slug',
                DB::raw("{$matchedGroupIndexSql} as matched_group_index"),
                'cp.begin_date as debug_begin',
                'cp.end_date as debug_end'
            )
            ->groupBy(
                'level3.id',
                'level3.full_code',
                'level3.label_en',
                'level3.label_ar',
                'level3.thumbnail',
                'level3.images',
                'level3.level',
                'level3.slug',
                'cp.begin_date',
                'cp.end_date'
            )
            ->orderByDesc('matched_group_index')
            ->get();
    }

    /**
     * حساب الأكواد المسموح بها للبحث بالمستوى الثاني في وضع Section
     */
    public function computeAllowedCodesForSections(Collection $categories, array $preloadedCodes): array
    {
        if ($categories->isEmpty()) {
            return [];
        }

        // جلب full_code لكل فئة مستوى ثالث تحت الفئات المحمّلة
        $level3Codes = DB::table('newcategories')
            ->whereIn('parent_id', $categories->pluck('id')->toArray())
            ->where('level', 3)
            ->pluck('full_code')
            ->map(fn($v) => (string) $v)
            ->toArray();

        // التقاطع بين أكواد level3 والأكواد المحملة مسبقاً
        $preloaded = array_map('strval', $preloadedCodes);

        return array_values(array_intersect($level3Codes, $preloaded));
    }
}
