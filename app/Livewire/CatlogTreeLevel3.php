<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CatlogTreeLevel3 extends Component
{
    public $brand;
    public $catalog;
    public $parentCategory1;
    public $parentCategory2;
    public $categories;

    public function mount($id, $data, $key1, $key2)
    {
        try {
            if (request()->has('vin')) {
                session()->put('vin', request()->get('vin'));
            }
            $this->loadBasicData($id, $data, $key1, $key2);

            $filters = Session::get('selected_filters', []);
            $filterDate = null;

            if (isset($filters['year']['value_id']) && isset($filters['month']['value_id'])) {
                $filterDate = Carbon::createFromDate(
                    $filters['year']['value_id'],
                    str_pad($filters['month']['value_id'], 2, '0', STR_PAD_LEFT),
                    1
                )->format('Y-m-d');
            }

            $matchedSpecItemIds = [];
            foreach ($filters as $specKey => $filter) {
                if (in_array($specKey, ['year', 'month'])) continue;
                $valueId = $filter['value_id'];

                $itemId = DB::table('specification_items')
                    ->join('specifications', 'specification_items.specification_id', '=', 'specifications.id')
                    ->where('specification_items.catalog_id', $this->catalog->id)
                    ->where('specifications.name', $specKey)
                    ->where('specification_items.value_id', $valueId)
                    ->value('specification_items.id');

                if ($itemId) {
                    $matchedSpecItemIds[] = $itemId;
                }
            }

            $this->categories = $this->loadFilteredLevel3Categories(
                $filterDate,
                $matchedSpecItemIds
            );


            // dd([
            //     '✅ الشجرة رقم 3 [mount()]',
            //     'brand' => $this->brand->name,
            //     'catalog' => $this->catalog->code,
            //     'parent_category1' => $this->parentCategory1->full_code,
            //     'parent_category2' => $this->parentCategory2->full_code,
            //     'filter_date' => $filterDate,
            //     'spec_item_ids' => $matchedSpecItemIds,
            //     'results_count' => $this->categories->count(),
            //     'full_codes' => $this->categories->pluck('full_code')->toArray(),
            // ]);

        } catch (\Exception $e) {
            Log::error("❌ Error in CatlogTreeLevel3 mount: " . $e->getMessage());
            dd([
                '❌ Exception in CatlogTreeLevel3',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->categories = collect();
        }
    }


    protected function loadBasicData($brandName, $catalogCode, $fullCode1, $fullCode2)
    {
        $this->brand = Brand::where('name', $brandName)->firstOrFail();

        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();

        $this->parentCategory1 = NewCategory::where([
            ['catalog_id', $this->catalog->id],
            ['brand_id', $this->brand->id],
            ['full_code', $fullCode1],
            ['level', 1],
        ])->firstOrFail();

        $this->parentCategory2 = NewCategory::where([
            ['catalog_id', $this->catalog->id],
            ['brand_id', $this->brand->id],
            ['full_code', $fullCode2],
            ['level', 2],
            ['parent_id', $this->parentCategory1->id],
        ])->firstOrFail();
    }
    // protected function loadFilteredLevel3Categories(?string $filterDate, array $specItemIds)
    // {
    //     $query = DB::table('newcategories as level3')
    //         ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
    //         ->join('category_spec_groups as csg', function ($join) {
    //             $join->on('csg.category_id', '=', 'level3.id')
    //                 ->where('csg.catalog_id', '=', $this->catalog->id);
    //         })
    //         ->join('category_spec_group_items as csgi', 'csgi.group_id', '=', 'csg.id')
    //         ->where('level3.catalog_id', $this->catalog->id)
    //         ->where('level3.brand_id', $this->brand->id)
    //         ->where('level3.parent_id', $this->parentCategory2->id)
    //         ->where('level3.level', 3)
    //         ->when($filterDate, fn($q) =>
    //             $q->where('cp.begin_date', '<=', $filterDate)
    //                 ->where(function ($sub) use ($filterDate) {
    //                     $sub->whereNull('cp.end_date')
    //                         ->orWhere('cp.end_date', '>', $filterDate); // 🔁 صارم: فقط من إلى بدون مساواة في النهاية
    //                 })
    //         )
    //         ->when(!empty($specItemIds), fn($q) =>
    //             $q->whereIn('csgi.specification_item_id', $specItemIds)
    //         )
    //         ->select(
    //             'level3.id',
    //             'level3.full_code',
    //             'level3.label_en',
    //             'level3.label_ar',
    //             'level3.thumbnail',
    //             'level3.images',
    //             'level3.level',
    //             'level3.slug',
    //             DB::raw('MAX(csg.group_index) as max_group_index'),
    //             DB::raw('COUNT(DISTINCT csgi.specification_item_id) as matched_specs'),
    //             'cp.begin_date as debug_begin',
    //             'cp.end_date as debug_end'
    //         )
    //         ->groupBy(
    //             'level3.id',
    //             'level3.full_code',
    //             'level3.label_en',
    //             'level3.label_ar',
    //             'level3.thumbnail',
    //             'level3.images',
    //             'level3.level',
    //             'level3.slug',
    //             'cp.begin_date',
    //             'cp.end_date'
    //         )
    //         ->havingRaw('matched_specs >= 1')
    //         ->orderByDesc('max_group_index');

    //     $results = $query->get();

    //     // // ✅ طباعة التحقق
    //     // dd([
    //     //     '✅ الشجرة رقم 3 [loadFilteredLevel3Categories]',
    //     //     'brand' => $this->brand->name,
    //     //     'catalog' => $this->catalog->code,
    //     //     'parent_category2' => $this->parentCategory2->full_code,
    //     //     'filter_date' => $filterDate,
    //     //     'spec_item_ids' => $specItemIds,
    //     //     'results_count' => $results->count(),
    //     //     'full_codes' => $results->pluck('full_code')->toArray(),
    //     //     'dates' => $results->map(fn($row) => [
    //     //         'code' => $row->full_code,
    //     //         'from' => $row->debug_begin,
    //     //         'to' => $row->debug_end,
    //     //     ])->toArray(),
    //     // ]);

    //     return $results;
    // }

    protected function loadFilteredLevel3Categories(?string $filterDate, array $specItemIds)
    {
        // إذا ما فيه مواصفات ممرّرة: نحتفظ بمنطق التاريخ فقط ونرجّع كل Level3 تحت الأب.
        if (empty($specItemIds)) {
            return DB::table('newcategories as level3')
                ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
                ->where('level3.catalog_id', $this->catalog->id)
                ->where('level3.brand_id', $this->brand->id)
                ->where('level3.parent_id', $this->parentCategory2->id)
                ->where('level3.level', 3)
                ->when($filterDate, fn($q) =>
                    $q->where('cp.begin_date', '<=', $filterDate)
                    ->where(function ($sub) use ($filterDate) {
                        $sub->whereNull('cp.end_date')
                            ->orWhere('cp.end_date', '>=', $filterDate); // ضمن الفترة
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

        // ⛳️ الوتر الحساس: نبني Subquery يعيد أعلى group_index بين القروبات المطابقة (subset)
        $idsList = implode(',', array_map('intval', $specItemIds));
        $matchedGroupIndexSql = "
            (
                SELECT MAX(csg2.group_index)
                FROM category_spec_groups csg2
                WHERE csg2.category_id = level3.id
                AND csg2.catalog_id = {$this->catalog->id}
                AND NOT EXISTS (
                    SELECT 1
                    FROM category_spec_group_items csgi2
                    WHERE csgi2.group_id = csg2.id
                        AND csgi2.specification_item_id NOT IN ({$idsList})
                )
            )
        ";

        $query = DB::table('newcategories as level3')
            ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
            ->where('level3.catalog_id', $this->catalog->id)
            ->where('level3.brand_id', $this->brand->id)
            ->where('level3.parent_id', $this->parentCategory2->id)
            ->where('level3.level', 3)
            ->when($filterDate, fn($q) =>
                $q->where('cp.begin_date', '<=', $filterDate)
                ->where(function ($sub) use ($filterDate) {
                    $sub->whereNull('cp.end_date')
                        ->orWhere('cp.end_date', '>=', $filterDate); // ضمن الفترة
                })
            )
            // يُشترط وجود قروب مطابق (subset) بإرجاع matched_group_index غير NULL
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
            ->orderByDesc('matched_group_index');

        $results = $query->get();

        // // 🟡 Debug (اتركه مُعلّق حسب قاعدتك)
        // dd([
        //     '✅ الشجرة رقم 3 [loadFilteredLevel3Categories — الوتر الحساس]',
        //     'brand' => $this->brand->name,
        //     'catalog' => $this->catalog->code,
        //     'parent_category2' => $this->parentCategory2->full_code,
        //     'filter_date' => $filterDate,
        //     'spec_item_ids' => $specItemIds,
        //     'results_count' => $results->count(),
        //     'full_codes' => $results->pluck('full_code')->toArray(),
        //     'dates' => $results->map(fn($row) => [
        //         'code' => $row->full_code,
        //         'from' => $row->debug_begin,
        //         'to' => $row->debug_end,
        //     ])->toArray(),
        // ]);

        return $results;
    }


    public function render()
    {
        return view('livewire.catlog-tree-level3', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'parentCategory1' => $this->parentCategory1,
            'parentCategory2' => $this->parentCategory2,
            'categories' => $this->categories ?? collect(),
        ]);
    }
}

