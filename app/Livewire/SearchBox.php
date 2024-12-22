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
//             orwhere('name', 'like', "%{$query}%")
//            ->limit(10)
            ->get(['sku as value', 'sku as key'])
            ->toArray();
    }


//
//    public function search($query)
//    {
//        // Replace this with your actual data source logic
//
//
//            Product::pluck('sku','name');
//        $data = [
//            ['value' => 'Apple', 'key' => 'Fruit'],
//            ['value' => 'Carrot', 'key' => 'Vegetable'],
//            ['value' => 'Dog', 'key' => 'Animal'],
//            ['value' => 'Cat', 'key' => 'Animal'],
//        ];
//
//        return collect($data)
//            ->filter(fn($item) => stripos($item['value'], $query) !== false)
//            ->values()
//            ->toArray();
//    }
//    public function updatedQuery()
//    {
//        // Simulate fetching data. Replace this with your actual data fetching logic.
//        $data = [
//            ['key' => 'food', 'value' => 'Pizza'],
//            ['key' => 'food', 'value' => 'Burger'],
//            ['key' => 'cities', 'value' => 'New York'],
//            ['key' => 'cities', 'value' => 'Los Angeles'],
//            ['key' => 'animals', 'value' => 'Dog'],
//            ['key' => 'animals', 'value' => 'Cat'],
//        ];
//
//        $this->results = collect($data)
//            ->filter(function ($item) {
//                return stripos($item['value'], $this->query) !== false;
//            })
//            ->take(15) // Limit results
//            ->toArray();
//    }


    public function selectItem($value)
    {
        dd($value);
//        $this->query = $value;

//        $this->results = [];
    }

    public function render()
    {
        return view('livewire.search-box');
    }
}
