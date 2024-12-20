<?php

namespace App\Livewire;

use App\Models\NCategory;
use App\Models\Partner;
use Livewire\Component;

class CatlogTreeLevel2 extends Component
{

    public $brand;
    public $categories;
    public function mount($id,$data,$key1)
    {
//         dd($id ,$data);
        $this->vehicle = $data;
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->categories = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage','key1','key2')
            ->where('key1' ,$key1)
            ->whereNull('key2')
            ->get();


//        dd($this->categories);
//        $this->brandId = Partner::where('name', $id)->first();
    }

    public function render()
    {

        return view('livewire.catlog-tree-level2');
    }
}
