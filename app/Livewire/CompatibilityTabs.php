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
        $this->products =  $this->getProducts(); // Default to first tab
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getProducts()
    {
        return   DB::select("
                SELECT DISTINCT partnumber, callout, label_en, applicability, code 
              FROM ".Str::lower($this->activeTab)."
                WHERE partnumber = :partnumber
            ", ['partnumber' => $this->sku]);

    }


    public function render()
    {

        $results =$this->getProducts();

//            dump($this->getProducts() ,$this->sku);
        return view('livewire.compatibility-tabs',['results' =>$this->getProducts()]);
    }
}
