<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SkuAlternative;
use Livewire\Component;

class Alternativeproduct extends Component
{
    public $alternatives;
    public $sku;

    public function mount($sku)
    {
        $this->sku = $sku;
    }

    public function render()
    {
        return view('livewire.alternativeproduct', [
            'alternatives' => $this->getalternatives()
        ]);
    }

    public function getalternatives()
    {
        // جلب السطر من جدول sku_alternatives
        $skuAlternative = SkuAlternative::where('sku', $this->sku)->first();

        if ($skuAlternative && $skuAlternative->group_id) {
            // جلب كل الـ SKUs في نفس القروب
            $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
                ->where('sku', '<>', $this->sku) // استثناء نفسه
                ->pluck('sku')
                ->toArray();

            if (!empty($alternativeSkus)) {
                $this->alternatives = Product::whereIn('sku', $alternativeSkus)
                    ->get()
                    ->sortByDesc(function ($product) {
                        $hasStockAndPrice = ($product->stock > 0 && $product->vendorPrice() > 0) ? 1 : 0;
                        return ($hasStockAndPrice * 1000000) + $product->vendorPrice();
                    });
            }
        }

        return $this->alternatives;
    }
}
