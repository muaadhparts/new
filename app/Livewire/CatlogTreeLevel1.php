<?php

namespace App\Livewire;

use App\Models\NCategory;
use App\Models\Partner;
use Livewire\Component;

class CatlogTreeLevel1 extends Component
{

//    public $id;
    public $brand;
    public $categories;
    public $vehicle;

    public function mount($id,$data)
    {
        $this->vehicle = $data;
//        $this->id = $id;
//         dd($id ,$data);
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->categories = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage')
            ->whereNull('key1')
            ->get();
//        dd($this->categories);
//        $this->brandId = Partner::where('name', $id)->first();
    }

    public function render()
    {

        return view('livewire.catlog-tree-level1');
    }
}
