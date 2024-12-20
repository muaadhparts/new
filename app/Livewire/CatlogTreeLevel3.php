<?php

namespace App\Livewire;

use App\Models\NCategory;
use App\Models\Partner;
use Livewire\Component;

class CatlogTreeLevel3 extends Component
{

    public $brand;
    public $categories;
    public function mount($id,$data,$key1,$key2)
    {
//         dd($id ,$data);
        $this->vehicle = $data;
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->categories = NCategory::where('data', $data)
            ->select('id','data','code','label','thumbnailimage','key1','key2')
            ->where('key1' ,$key1)
            ->where('key2' ,$key2)
//            ->whereNull('key2')
            ->get();


//        dd($this->categories);

    }

    public function render()
    {

        return view('livewire.catlog-tree-level3');
    }
}
