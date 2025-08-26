<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Compatibility extends Component
{
    public $sku;

    public function getCompatibility()
    {
        // جلب الكاتالوجات المرتبطة برقم القطعة
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
            ->where('parts_index.part_number', $this->sku)
            ->get();

        return $parts->map(function ($part) {
            return (object)[
                'part_number'   => $part->part_number,
                'catalog_code'  => $part->catalog_code,
                'label'         => app()->getLocale() === 'ar' ? $part->label_ar : $part->label_en,
                'begin_year'    => $part->beginYear,
                'end_year'      => $part->endYear ?: 'حتى الآن',
            ];
        });
    }

    public function render()
    {
        $results = $this->getCompatibility();

        return view('livewire.compatibility', [
            'results' => $results,
            'sku' => $this->sku,
        ]);
    }
}
