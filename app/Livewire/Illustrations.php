<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\NCategory;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Illustrations extends Component
{


    public $category;
    public $partCallouts;
    public $illustration;
    public $products;
    public function mount($id,$data,$key1,$key2,$code)
    {
//         dd($id,$data,$key1,$key2,$code );


        $this->category = NCategory::where('data', $data)
            ->select('id','data','code','label','images','key1','key2')
             ->where('key1' ,$key1)
                ->where('key2' ,$key2)
                ->where('code' ,$code)
               ->firstOrfail();

        $this->illustration  =   \App\Models\Illustrations::where('data',$data)
            ->where('code',$code)
            ->first();

//        $this->partCallouts = collect( $this->illustration->illustrationWithCallouts);
        $this->partCallouts = collect($this->illustration->illustrationwithcallouts)['partCallouts'];

//        dd($this->illustration ,$this->illustration->illustrationwithcallouts , $this->partCallouts,$this->category);
 //        $products = Product::take(10)->get();

        $this->products = DB::table(Str::lower($data))
            ->select('code', 'partnumber', 'callout' ,'label_'.app()->getLocale())
            ->distinct()
            ->where('code','101A-001')
            ->orderBy('callout')
            ->get();


//        dd($this->illustration->illustrationWithCallouts ,$this->products);

    }

    public function render()
    {


        return view('livewire.illustrations');
    }
}
