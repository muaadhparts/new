<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class SearchBox extends Component
{

    public $query = '';
    public $results = [];

    public function updatedQuery()
    {
        $this->results = $this->search($this->query);
    }

    public function search($query)
    {
        return \App\Models\Product::Where('sku', 'like', "%{$query}%")
            ->orwhere('name', 'like', "%{$query}%")
            ->orwhere('label_en', 'like', "%{$query}%")
            ->orwhere('label_ar', 'like', "%{$query}%")
            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
            ->limit(300)
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
        return view('livewire.search-box');
    }
}
