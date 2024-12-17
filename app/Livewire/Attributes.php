<?php

namespace App\Livewire;

use App\Models\MajorAttributes;
use Livewire\Component;

class Attributes extends Component
{

    public  $catalog;
    public $attributes;


    public function mount($catalog)

    {
//        dd($catalog->id);

        $this->attributes =    MajorAttributes::where('catalog_id', $catalog->id)->get();


    }


    public function render()
    {
//        dd($this->attributes->toArray());
        return view('livewire.attributes');
    }
}
