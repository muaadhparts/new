<?php

namespace App\Livewire;

use App\Models\NCategory;
use App\Models\Brand;
use Livewire\Component;

class CatlogTreeLevel2 extends Component
{

    public $brand;
    public $category;
    public $categories;
    public function mount($id,$data,$key1)
    {
//         dd($id ,$data ,$key1);
        $this->vehicle = $data;
        $this->brand = Brand::where('name', $id)->firstorFail();
       $this->category  = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage')
            ->whereNull('key1')
             ->where('code' ,$key1)
            ->first();
//       dd($this->category);
        $this->categories = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage','key1','key2')
            ->where('key1' ,$key1)
            ->whereNull('key2')
            ->get();


//        dd($this->categories);
//        $this->brandId = Brand::where('name', $id)->first();
    }

    public function render()
    {

        return view('livewire.catlog-tree-level2');
    }
}
