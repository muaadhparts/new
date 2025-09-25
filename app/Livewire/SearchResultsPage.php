<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SkuAlternative;
use Livewire\Component;

class SearchResultsPage extends Component
{
    public $sku;
    public $alternatives;
    public $prods;

    public function mount($sku)
    {
        $this->sku = $sku;

        // المنتج الأساسي
        $this->prods = Product::where('sku', $sku)->get();

        // جلب السطر من جدول sku_alternatives
        $skuAlternative = SkuAlternative::where('sku', $this->sku)->first();

        if ($skuAlternative && $skuAlternative->group_id) {
            // جلب كل الـ SKUs في نفس القروب مع استثناء نفسه
            $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
                ->where('sku', '<>', $this->sku)
                ->pluck('sku')
                ->toArray();

            if (!empty($alternativeSkus)) {
                $this->alternatives = Product::whereIn('sku', $alternativeSkus)
                    ->get()
                    ->sortByDesc(function ($product) {
                        $hasStockAndPrice = ($product->vendorSizeStock() > 0 && $product->vendorPrice() > 0) ? 1 : 0;
                        return ($hasStockAndPrice * 1000000) + $product->vendorPrice();
                    });
            } else {
                $this->alternatives = collect();
            }
        } else {
            $this->alternatives = collect();
        }
    }

    public function render()
    {
        return view('livewire.search-results-page', [
            'alternatives' => $this->alternatives,
            'prods' => $this->prods,
        ]);
    }
}
