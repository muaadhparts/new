<?php

namespace App\Livewire;

use App\Models\Brand;
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
        $this->brand = Brand::where('name', $id)->firstorFail();



//         dd($id ,$data  ,$this->products ,$this->prods);
//        $this->products = 'GL';
     }

    public function render()
    {
        $labelColumn = app()->getLocale() === 'ar'
            ? DB::raw('label_ar as label')
            : DB::raw('label_en as label');

        $prods = DB::table(Str::lower($this->vehicle))
            ->where(function ($query) {
                $query->where('partnumber', 'like', "{$this->query}%")
                    ->orWhere('callout', 'like', "{$this->query}%")
                    ->orWhere('label_en', 'like', "{$this->query}%")
                    ->orWhere('label_ar', 'like', "{$this->query}%");
            })
            ->select(
                'id',
                'qty',
                'partnumber',
                'callout',
                $labelColumn, // 👈 فقط عمود واحد حسب اللغة
                'applicability',
                'formattedbegindate',
                'formattedenddate',
                'key1',
                'key2',
                'code'
            )
            ->simplePaginate(20);

        return view('livewire.catlogs-products', [
            'prods' => $prods,
        ]);
    }

}
