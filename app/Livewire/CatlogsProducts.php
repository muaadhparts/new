<?php

namespace App\Livewire;

use App\Models\Partner;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CatlogsProducts extends Component
{
    use WithPagination;
    public    $products = [] ;
//    public    $prods  ;
    public $brand;
    public $query;
    public $vehicle;

    public function mount($id,$data,$query)
    {
//        $skus =   explode(',',$this->products);

        $this->vehicle = $data;
        $this->query = $query;
//         dd($id ,$products);
        $this->brand = Partner::where('name', $id)->firstorFail();



//         dd($id ,$data  ,$this->products ,$this->prods);
//        $this->products = 'GL';
     }

    public function render()
    {
        $prods =      DB::table(Str::lower($this->vehicle))
//

            ->where('partnumber', 'like', "{$this->query}%")
            ->orWhere('callout', 'like', "{$this->query}%")
            ->orWhere('label_en', 'like', "{$this->query}%")
            ->orWhere('label_ar', 'like', "{$this->query}%")
            ->select('id','qty', 'partnumber', 'callout', 'label_en',
                'label_ar' ,'applicability' ,'formattedbegindate' ,'formattedenddate' ,'code')
            ->simplePaginate(20);
//        dd($prods->count());

//        dd($this->products);
        return view('livewire.catlogs-products',[
            'prods' => $prods,
        ]);
    }
}
