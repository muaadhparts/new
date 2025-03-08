<?php

namespace App\Livewire;

use App\Models\Catalog;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Compatibility extends Component
{

    public $sku ;
    public function getCompatibility(){

//        $this->sku = $sku;
    $data=     DB::table('allcar')->where('partnumber',$this->sku)->pluck('data')->toArray();

    $Catalog =   Catalog::whereIn('data',$data)->get();

    return $Catalog;
//    dd($Catalog );

    }
    public function render()
    {

//        dd($this->getCompatibility());
        return view('livewire.compatibility',[
            'catalogs'=>$this->getCompatibility(),
        ]);
    }
}
