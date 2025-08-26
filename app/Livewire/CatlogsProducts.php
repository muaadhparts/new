<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Catalog;
use App\Services\PartSearchService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CatlogsProducts extends Component
{
    use WithPagination;

    public $brand;
    public $catalog;
    public $query;
    public $prods;

    public function mount($id, $data, $query)
    {
        $this->query = trim($query);
        $this->catalog = Catalog::where('code', $data)->with('brand')->firstOrFail();
        $this->brand = $this->catalog->brand;
    }

    public function render()
    {
        // ✅ استخراج الفلاتر من الجلسة
        $filterData = session('selected_filters', []);
        $specCodes = [];
        $year = null;
        $month = null;

        foreach ($filterData as $key => $item) {
            $valueId = is_array($item) ? $item['value_id'] : $item;

            if ($key === 'year') {
                $year = $valueId;
            } elseif ($key === 'month') {
                $month = str_pad($valueId, 2, '0', STR_PAD_LEFT);
            } else {
                $specCodes[] = $valueId;
            }
        }

        $filterDate = null;
        if ($year && $month) {
            $filterDate = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        }

        // ✅ تنفيذ الاستعلام وتخزين النتائج في المتغير
        $this->prods = DB::table('part_search_cache as psc')
            ->selectRaw('
                DISTINCT
                p.part_number,
                sp.callout AS part_callout,
                sp.qty AS part_qty,
                sp.label_en AS part_label_en,
                sp.label_ar AS part_label_ar,
                s.code AS section_code,
                c.code AS catalog_code,
                cat.full_code AS category_code,
                na.name AS attribute_name,
                pp.begin_date AS part_begin,
                pp.end_date AS part_end
            ')
            ->leftJoin('parts as p', 'p.id', '=', 'psc.part_id')
            ->leftJoin('section_parts as sp', function ($join) {
                $join->on('sp.part_id', '=', 'psc.part_id')
                     ->on('sp.section_id', '=', 'psc.section_id');
            })
            ->leftJoin('sections as s', 's.id', '=', 'psc.section_id')
            ->leftJoin('catalogs as c', 'c.id', '=', 'psc.catalog_id')
            ->leftJoin('newcategories as cat', 'cat.id', '=', 'psc.category_id')
            ->leftJoin('new_attributes as na', 'na.id', '=', 'psc.attribute_id')
            ->leftJoin('part_periods as pp', 'pp.id', '=', 'psc.part_period_id')
            ->where(function ($q) {
                $q->where('p.part_number', 'LIKE', "%{$this->query}%")
                  ->orWhere('na.name', 'LIKE', "%{$this->query}%");
            })
            ->when($this->catalog, function ($q) {
                $catalogId = is_object($this->catalog) ? $this->catalog->id : $this->catalog;
                $q->where('psc.catalog_id', $catalogId);
            })
            // ✅ فلترة التاريخ
            ->when($filterDate, function ($q) use ($filterDate) {
                $q->where(function ($query) use ($filterDate) {
                    $query->whereNull('pp.begin_date')
                          ->orWhere('pp.begin_date', '<=', $filterDate);
                })->where(function ($query) use ($filterDate) {
                    $query->whereNull('pp.end_date')
                          ->orWhere('pp.end_date', '>=', $filterDate);
                });
            })
            // ✅ فلترة المواصفات
            ->when(!empty($specCodes), function ($q) use ($specCodes) {
                $q->whereIn('na.name', $specCodes);
            })
            ->limit(50)
            ->get();

        return view('livewire.catlogs-products', [
            'prods' => $this->prods,
        ]);
    }
}
