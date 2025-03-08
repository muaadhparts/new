<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class CompatibilityTabs extends Component
{
    public $catalogs;
    public $sku;
    public $activeTab;
    public $products;

    public function mount($catalogs)
    {
        $this->catalogs = $catalogs;
        $this->activeTab = $catalogs[0]->data ?? null; // Default to first tab
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

//    public function render()
//    {
//        return view('livewire.catalog-tabs');
//    }


    public function render()
    {
        $this->activeTab = 'HY10GL';
//        dump($this->activeTab);

//

        $results = DB::select("
                SELECT DISTINCT partnumber, callout, label_en, applicability, code 
              FROM ".Str::lower($this->activeTab)."
                WHERE partnumber = :partnumber
            ", ['partnumber' => '1520831U0A']);



//        $results = DB::table(Str::lower($this->activeTab))
//            ->selectRaw('DISTINCT id, partnumber, callout, label_en, label_ar, code') // Ensure DISTINCT selection
//            ->where('partnumber', '=', '1520831U0A') // Exact match for partnumber
//            ->get();
//         dd($results);
        return view('livewire.compatibility-tabs',['products' => $results]);
    }
}
