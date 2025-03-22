<?php

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\MajorAttributes;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Attributes extends Component
{

    public  $catalog;
    public $attributes;
    public array $data = [];


    public function mount($vehicle)

    {


        $this->catalog  = Catalog::with('attributes')->select('id','data')->where('data',$vehicle)->firstOrFail();
//           dd($vehicle , $this->catalog );


        $this->data = $this->catalog->attributes->mapWithKeys(function ($attribute) {
            return [
                $attribute->name => null // Initialize with null or a default value
            ];
        })->toArray();

    }


    public function attributes(){
        $this->attributes =  $this->catalog->attributes;

    }

    public function save()
    {


//            Session::put('attributes', $this->data);
//            dd($this->data);
        $this->dispatchBrowserEvent('form-saved');
//             session(['attributes' => $this->data]);

//            dd(Session::get('attributes') ,   session('attributes'));
//        dd($this->data);
        // Handle form submission logic here
//        foreach ($this->attributes as $name => $value) {
//            // Process or save each attribute
//            // For example, update a database record
//        }

//        session()->flash('message', 'Attributes saved successfully.');
    }




    public function render()
    {
//        dd($this->attributes->toArray());
        return view('livewire.attributes');
    }
}
