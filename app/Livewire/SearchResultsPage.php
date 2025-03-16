<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SearchResultsPage extends Component
{

    public $sku;
    public $latest_products ;
    public $prods ;
    public $alternatives ;

    public  function mount($sku)
    {
        $this->sku = $sku;

//        Product::take(3)->where("sku", $sku)->get();
        $alternativesSkus =  \App\Models\Alternative::where('sku', $sku)->first();

        if($alternativesSkus){
            $this->alternatives =   Product::wherein('sku',$alternativesSkus->alternative)
//                   ->Orwhere('sku', $sku)
                                    ->orderBy('stock','DESC')
                                    ->get();
        }

        $this->prods =  Product::where('sku', $sku)->get();
//        dd($this->alternatives ,  $this->prods ,$alternativesSkus->alternative ,$alternativesSkus);

//        $this->latest_products =  Product::where('sku', $sku)->;
//        $this->prods =    $this->p;
//        dd($this->alternatives ,  $this->prods ,$alternativesSkus->alternative ,$alternativesSkus);

//        dd($sku);

    }
    public function render()
    {

        return view('livewire.search-results-page');
    }
}
