<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CompatibilityTabs extends Component
{
    public $sku;
    public $results;

    public function mount($sku)
    {
        $this->sku = $sku;
        $this->results = $this->getCompatibility();
    }

    public function getCompatibility()
    {
        return DB::table('parts_index')
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
            ->get()
            ->map(function ($item) {
                return (object) [
                    'part_number'   => $item->part_number,
                    'catalog_code'  => $item->catalog_code,
                    'label'         => app()->getLocale() === 'ar' ? $item->label_ar : $item->label_en,
                    'begin_year'    => $item->beginYear,
                    'end_year'      => $item->endYear ?: 'حتى الآن',
                ];
            });
    }

    public function render()
    {
        return view('livewire.compatibility-tabs', [
            'results' => $this->results,
            'sku'     => $this->sku,
        ]);
    }
}
