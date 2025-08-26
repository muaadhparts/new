<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class SearchResultsModal extends Component
{
    public $sku;
    public $prods;
    public $alternatives;

    protected $listeners = ['showResultsForSku' => 'loadResults'];

    public function loadResults($sku)
    {
        $this->sku = $sku;

        $this->prods = Product::where('sku', $sku)->get();

        $alternativesSkus = \App\Models\Alternative::where('sku', $sku)->first();

        if ($alternativesSkus) {
            $this->alternatives = Product::whereIn('sku', $alternativesSkus->alternative)
                                         ->orderBy('stock', 'DESC')
                                         ->get();
        }
    }

    public function render()
    {
        return view('livewire.search-results-modal');
    }
}
