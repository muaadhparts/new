<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class Alternative extends Component
{

    public $sku;
    public $product;
     public $latest_products ;
    public $prods ;
//    public $alternatives ;

    public  function mount($sku)
    {
        $this->sku = $sku;

//        Product::take(3)->where("sku", $sku)->get();

    }


    public function render()
    {


        return view('livewire.alternative',['alternatives' => $this->getalternatives()] );
    }

    /**
     * @param $sku
     * @return void
     */
    public function getalternatives(): void
    {
//        $this->sku = $sku;
//        dd($this->sku);
        $alternativesSkus = \App\Models\Alternative::where('sku', $this->sku)->first();
//        dd($alternativesSkus);
        if ($alternativesSkus) {
            $this->alternatives = Product::whereIn('sku', $alternativesSkus->alternative)
                ->get();
        }

//        $this->prods = Product::where('sku', $sku)->get();
//        dd($this->prods  , $this->alternatives ,$alternativesSkus);
    }
}
