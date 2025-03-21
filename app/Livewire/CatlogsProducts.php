<?php

namespace App\Livewire;

use App\Models\Partner;
use App\Models\Product;
use Livewire\Component;

class CatlogsProducts extends Component
{
    public    $products = [] ;
    public    $prods = [] ;
    public $brand;
    public $categories;
    public $vehicle;

    public function mount($id,$data,$products)
    {
        $skus =   explode(',',$this->products);

        $this->vehicle = $data;
//        $this->id = $id;
//         dd($id ,$data);
        $this->brand = Partner::where('name', $id)->firstorFail();

        $this->prods =  Product::whereIn('sku', $skus)
            ->take(20)
            ->get();

//         dd($id ,$data  ,$this->products ,$this->prods);
//        $this->products = 'GL';
     }

    public function render()
    {


//        dd($this->products);
        return view('livewire.catlogs-products');
    }
}
