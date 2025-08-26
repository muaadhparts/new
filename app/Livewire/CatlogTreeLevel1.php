<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Catalog;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class CatlogTreeLevel1 extends Component
{
    public $catalog;
    public $brand;
    public $categories;

    public function mount($id, $data)
    {
        try {
            $this->catalog = Catalog::where('code', $data)->firstOrFail();
            $this->brand = Brand::where('name', $id)->firstOrFail();

            $filters = session('selected_filters', []);
            $specItemIds = $this->extractSpecItemIds($filters);
            $filterDate = $this->extractFilterDate($filters);

            $allowedLevel3Codes = $this->getFilteredLevel3FullCodes($filterDate, $specItemIds);

            Session::put('preloaded_full_code', $allowedLevel3Codes);

            $labelField = app()->getLocale() === 'ar' ? 'label_ar' : 'label_en';
            $this->categories = $this->loadLevel1Categories($labelField, $filterDate, $allowedLevel3Codes);

            // // 🟡 Debug طباعة
            // dd([
            //     '✅ الشجرة رقم 1',
            //     'brand' => $this->brand->name,
            //     'catalog' => $this->catalog->code,
            //     'filter_date' => $filterDate,
            //     'spec_item_ids' => $specItemIds,
            //     'level3_full_codes' => $allowedLevel3Codes,
            //     'categories_loaded' => $this->categories->pluck('full_code'),
            // ]);

        } catch (\Exception $e) {
            Log::error("❌ Error in CatlogTreeLevel1 mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->categories = collect();
        }
    }

    public function render()
    {
        return view('livewire.catlog-tree-level1', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'categories' => $this->categories ?? collect(),
        ]);
    }

    protected function extractSpecItemIds(array $filters): array
    {
        $matchedSpecItemIds = [];
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
                $matchedSpecItemIds[] = $itemId;
            }
        }
        return $matchedSpecItemIds;
    }

    protected function extractFilterDate(array $filters): ?string
    {
        $year = $filters['year']['value_id'] ?? $filters['year'] ?? null;
        $month = $filters['month']['value_id'] ?? $filters['month'] ?? null;

        if ($year && $month) {
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            return Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        }

        return null;
    }

    protected function getFilteredLevel3FullCodes(?string $filterDate, array $specItemIds): array
    {
        // لو ما فيه مواصفات ممرّرة: فلترة بالتاريخ فقط (إن وُجد)
        if (empty($specItemIds)) {
            return DB::table('newcategories as nc')
                ->join('category_periods as cp', 'cp.category_id', '=', 'nc.id')
                ->where('nc.catalog_id', $this->catalog->id)
                ->where('nc.brand_id', $this->brand->id)
                ->where('nc.level', 3)
                ->when($filterDate, fn($q) =>
                    $q->where('cp.begin_date', '<=', $filterDate)
                    ->where(function ($sub) use ($filterDate) {
                        $sub->whereNull('cp.end_date')
                            ->orWhere('cp.end_date', '>=', $filterDate); // ضمن الفترة (شامل للنهاية)
                    })
                )
                ->distinct()
                ->pluck('nc.full_code')
                ->toArray();
        }

        // يوجد مواصفات: نطبق "الوتر الحساس" — كل قيم القروب ⊆ specItemIds
        return DB::table('newcategories as nc')
            ->join('category_periods as cp', 'cp.category_id', '=', 'nc.id')
            ->where('nc.catalog_id', $this->catalog->id)
            ->where('nc.brand_id', $this->brand->id)
            ->where('nc.level', 3)
            ->when($filterDate, fn($q) =>
                $q->where('cp.begin_date', '<=', $filterDate)
                ->where(function ($sub) use ($filterDate) {
                    $sub->whereNull('cp.end_date')
                        ->orWhere('cp.end_date', '>=', $filterDate); // ضمن الفترة (شامل للنهاية)
                })
            )
            // يجب أن يوجد قروب واحد على الأقل يحقق subset
            ->whereExists(function ($q) use ($specItemIds) {
                $q->from('category_spec_groups as csg2')
                ->whereColumn('csg2.category_id', 'nc.id')
                ->where('csg2.catalog_id', $this->catalog->id)
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


    protected function loadLevel1Categories(string $labelField, ?string $filterDate, array $allowedLevel3Codes)
    {
        return DB::table('newcategories as level1')
            ->join('newcategories as level2', 'level2.parent_id', '=', 'level1.id')
            ->join('newcategories as level3', 'level3.parent_id', '=', 'level2.id')
            ->join('category_periods as cp', 'cp.category_id', '=', 'level3.id')
            ->where('level1.catalog_id', $this->catalog->id)
            ->where('level1.brand_id', $this->brand->id)
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
}  


