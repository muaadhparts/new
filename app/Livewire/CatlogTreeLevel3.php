<?php

namespace App\Livewire;

use App\Models\NCategory;
use App\Models\Partner;
use Livewire\Component;

class CatlogTreeLevel3 extends Component
{

    public $brand;
    public $categories;
    public $category;
    public function mount($id,$data,$key1,$key2)
    {
//         dd($id ,$data ,$key1,$key2);
        $this->vehicle = $data;
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->category  = NCategory::where('data', $data)
//            ->select('id','data','code','label','thumbnailimage')
            ->where('key1' ,$key1)
            ->where('code' ,$key2)
            ->first();
//         dd($id ,$data ,$key1,$key2 ,$this->category );


        $this->categories = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage','key1','key2')
            ->where('key1' ,$key1)
            ->where('key2' ,$key2)
//            ->whereNull('key2')
            ->get();


//        dd($this->categories , $this->category);

    }

    public function render()
    {

        return view('livewire.catlog-tree-level3');
    }
}
