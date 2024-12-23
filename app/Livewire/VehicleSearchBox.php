<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class VehicleSearchBox extends Component
{
    public $query = '';
    public $vehicle;
    public $results = [];

    public function updatedQuery()
    {
        $this->results = $this->search($this->query);
    }

    public function search($query)
    {
//        dd($this);
//        $query = DB::table(Str::lower($this->vehicle))
//            ->select(
//                'code', 'partnumber', 'callout',  'label_'.app()->getLocale(),
//
//            )
//            ->where('code',$code );
        return DB::table(Str::lower($this->vehicle))
            ->where('partnumber', 'like', "%{$query}%")
//            ->orwhere('name', 'like', "%{$query}%")
            ->orwhere('callout', 'like', "%{$query}%")
            ->orwhere('label_en', 'like', "%{$query}%")
            ->orwhere('label_ar', 'like', "%{$query}%")
            ->select('id', 'partnumber',   'label_en', 'label_ar')
            ->limit(50)
            ->get();
//            ->limit(10)
//            ->get(['sku as value', 'name as key'])
//            ->toArray();
    }



    public function selectItem($value)
    {
//        dd($value);
        redirect()->route('search.result', ['sku' => $value]);
//        $this->query = $value;

//        $this->results = [];
    }


    public function render()
    {
        return view('livewire.vehicle-search-box');
    }
}
