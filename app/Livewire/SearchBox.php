<?php

namespace App\Livewire;

use Livewire\Component;

class SearchBox extends Component
{

    public $query = '';
    public $results = [];

    public function updatedQuery()
    {
        // Simulate fetching data. Replace this with your actual data fetching logic.
        $data = [
            ['key' => 'food', 'value' => 'Pizza'],
            ['key' => 'food', 'value' => 'Burger'],
            ['key' => 'cities', 'value' => 'New York'],
            ['key' => 'cities', 'value' => 'Los Angeles'],
            ['key' => 'animals', 'value' => 'Dog'],
            ['key' => 'animals', 'value' => 'Cat'],
        ];

        $this->results = collect($data)
            ->filter(function ($item) {
                return stripos($item['value'], $this->query) !== false;
            })
            ->take(15) // Limit results
            ->toArray();
    }


    public function render()
    {
        return view('livewire.search-box');
    }
}
