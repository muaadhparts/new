<?php

namespace App\Livewire;

use Livewire\Component;

class Alternative extends Component
{

    public $sku;
    public $product;

    protected $listeners = ['showAlternativeModal'];

    public function showAlternativeModal($sku)
    {
        $this->sku = $sku;

        $this->product = \App\Models\Product::where('sku', $sku)->first(); // Adjust model and query as needed

        $this->emit('openModal'); // Trigger the modal
    }
    public function render()
    {
        return view('livewire.alternative');
    }
}
