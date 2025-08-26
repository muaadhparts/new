<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Models\Section;
    use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatlogTreeLevel2 extends Component
{
    public $brand;
    public $catalog;
    public $category;
    public $categories;
    public $sections;

    /**
     * الأكواد المسموح بها للبحث في وضع Section بالمستوى الثاني.
     * يتم حسابها بناءً على أبناء المستوى الثالث لهذه الفئات وتقاطعها مع الأكواد المحمّلة مسبقاً في الجلسة.
     */
    public $level2AllowedCodes = [];

    public function mount($id, $data, $key1)
    {
        try {
            if (request()->has('vin')) {
                session()->put('vin', request()->get('vin'));
            }
            $this->loadBasicData($id, $data, $key1);

            $filterDate = $this->getFilterDateFromSession();
            $specItemIds = $this->getSpecItemIdsFromSession();

            $this->categories = $this->loadFilteredLevel2Categories($filterDate, $specItemIds);
            $this->sections = $this->loadSectionsForCategories($this->categories);

            // بعد تحميل الفئات والأقسام، نحسب الأكواد المسموح بها لوضع Section
            $this->level2AllowedCodes = $this->computeAllowedCodesForSections($this->categories);

        } catch (\Exception $e) {
            Log::error("Error in CatlogTreeLevel2 mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');

            $this->categories = collect();
            $this->sections = collect();
        }
    }

    protected function loadBasicData($brandName, $catalogCode, $parentFullCode)
    {
        $this->brand = Brand::where('name', $brandName)->firstOrFail();

        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();

        $this->category = NewCategory::where([
            ['catalog_id', $this->catalog->id],
            ['brand_id', $this->brand->id],
            ['full_code', $parentFullCode],
            ['level', 1],
        ])->firstOrFail();
    }

    protected function getSpecItemIdsFromSession(): array
    {
        $filters = session('selected_filters', []);
        $specItemIds = [];

        foreach ($filters as $specKey => $filter) {
            if (in_array($specKey, ['year', 'month'])) continue;

            $valueId = is_array($filter) ? $filter['value_id'] : $filter;

            $itemId = DB::table('specification_items')
                ->join('specifications', 'specification_items.specification_id', '=', 'specifications.id')
                ->where('specification_items.catalog_id', $this->catalog->id)
                ->where('specifications.name', $specKey)
                ->where('specification_items.value_id', $valueId)
                ->value('specification_items.id');

            if ($itemId) {
                $specItemIds[] = $itemId;
            }
        }

        return $specItemIds;
    }

    protected function getFilterDateFromSession(): ?string
    {
        $filters = session('selected_filters', []);
        $year = $filters['year']['value_id'] ?? null;
        $month = isset($filters['month']['value_id']) ? str_pad($filters['month']['value_id'], 2, '0', STR_PAD_LEFT) : null;

        return ($year && $month)
            ? Carbon::createFromDate($year, $month, 1)->format('Y-m-d')
            : null;
    }

    protected function loadFilteredLevel2Categories(?string $filterDate, array $specItemIds)
    {
        // بناء استعلام مستوى 2 فقط؛ سنحسب المطابقة عبر Subquery على أطفال Level 3
        $query = DB::table('newcategories as level2')
            ->where('level2.catalog_id', $this->catalog->id)
            ->where('level2.brand_id', $this->brand->id)
            ->where('level2.parent_id', $this->category->id)
            ->where('level2.level', 2);

        // إن كانت هناك مواصفات: نطبق "الوتر الحساس" على أي قروب في أي طفل Level3
        if (!empty($specItemIds)) {
            $idsList = implode(',', array_map('intval', $specItemIds));

            // أعلى group_index بين القروبات المطابقة (subset) عبر أي طفل Level3
            $matchedGroupIndexSql = "
                (
                    SELECT MAX(csg2.group_index)
                    FROM newcategories l3
                    JOIN category_spec_groups csg2
                    ON csg2.category_id = l3.id AND csg2.catalog_id = {$this->catalog->id}
                    " . ($filterDate ? "
                    JOIN category_periods cp2
                    ON cp2.category_id = l3.id
                    " : "") . "
                    WHERE l3.parent_id = level2.id
                    AND l3.catalog_id = {$this->catalog->id}
                    AND l3.brand_id = {$this->brand->id}
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
                // يجب أن يوجد طفل Level3 لديه قروب مطابق (subset)
                ->whereRaw("{$matchedGroupIndexSql} IS NOT NULL")
                ->orderByDesc('matched_group_index');

        } else {
            // لا توجد مواصفات: فقط نضمن وجود طفل Level3 يغطي التاريخ (إن وُجد تاريخ)
            if ($filterDate) {
                $query->whereExists(function ($q) use ($filterDate) {
                    $q->from('newcategories as l3')
                    ->join('category_periods as cp2', 'cp2.category_id', '=', 'l3.id')
                    ->whereColumn('l3.parent_id', 'level2.id')
                    ->where('l3.catalog_id', $this->catalog->id)
                    ->where('l3.brand_id', $this->brand->id)
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

        $results = $query->get();

        return $results;
    }

    protected function loadSectionsForCategories($categories)
    {
        if ($categories->isEmpty()) return collect();

        return Section::whereIn('category_id', $categories->pluck('id')->toArray())
            ->where('catalog_id', $this->catalog->id)
            ->with(['illustrations' => function ($query) {
                $query->select('id', 'section_id', 'code', 'image_name', 'folder');
            }])
            ->select('id', 'code', 'full_code', 'category_id', 'catalog_id')
            ->get()
            ->groupBy('category_id');
    }

    /**
     * حساب الأكواد المسموح بها للبحث بالمستوى الثاني في وضع Section.
     * نقوم بجلب جميع أكواد المستوى الثالث التابعة للفئات الحالية، ثم تقاطعها مع الأكواد الموجودة في session('preloaded_full_code').
     *
     * @param \Illuminate\Support\Collection $categories
     * @return array
     */
    protected function computeAllowedCodesForSections($categories): array
    {
        if (!$categories || $categories->isEmpty()) {
            return [];
        }
        // جلب full_code لكل فئة مستوى ثالث تحت الفئات المحمّلة
        $level3Codes = DB::table('newcategories')
            ->whereIn('parent_id', $categories->pluck('id')->toArray())
            ->where('level', 3)
            ->pluck('full_code')
            ->map(fn($v) => (string) $v)
            ->toArray();

        // الأكواد المحمّلة مسبقاً من الجلسة
        $preloaded = array_map('strval', (array) session('preloaded_full_code', []));

        // التقاطع بين أكواد level3 والأكواد المحملة مسبقاً
        return array_values(array_intersect($level3Codes, $preloaded));
    }

    public function render()
    {
        return view('livewire.catlog-tree-level2', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'category' => $this->category,
            'categories' => $this->categories ?? collect(),
            'sections' => $this->sections ?? collect(),
            'allowedCodes' => $this->level2AllowedCodes,  // تمرير الأكواد إلى الـ Blade
        ]);
    }
}
