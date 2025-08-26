<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SkuAlternative;
use Livewire\Component;
use Illuminate\Support\Collection;

class Alternative extends Component
{
    public string $sku;
    /** @var \Illuminate\Support\Collection<int,\App\Models\Product> */
    public Collection $alternatives;

    public function mount(string $sku): void
    {
        $this->sku = $sku;
        $this->alternatives = $this->fetchAlternatives();
    }

    public function render()
    {
        return view('livewire.alternative', [
            'alternatives' => $this->alternatives,
        ]);
    }

    /**
     * إرجاع قائمة المنتجات البديلة حسب الـ SKU.
     */
    // protected function fetchAlternatives(): Collection
    // {
    //     // جلب صف الـ SKU من جدول sku_alternatives
    //     $skuAlt = SkuAlternative::where('sku', $this->sku)->first();

    //     // نجمع SKUs: البدائل + الكود نفسه دائمًا
    //     $skus = [$this->sku];

    //     if ($skuAlt && $skuAlt->group_id) {
    //         $groupSkus = SkuAlternative::where('group_id', $skuAlt->group_id)
    //             ->where('sku', '<>', $this->sku)
    //             ->pluck('sku')
    //             ->toArray();

    //         $skus = array_merge($skus, $groupSkus);
    //     }

    //     // نجيب فقط الموجود فعلاً في جدول products
    //     return Product::whereIn('sku', $skus)->get();
    // }
    protected function fetchAlternatives(): Collection
    {
        // جلب صف الـ SKU من جدول sku_alternatives
        $skuAlt = SkuAlternative::where('sku', $this->sku)->first();

        // جمع SKUs: المنتج نفسه + البدائل في نفس القروب
        $skus = [$this->sku];

        if ($skuAlt && $skuAlt->group_id) {
            $groupSkus = SkuAlternative::where('group_id', $skuAlt->group_id)
                ->where('sku', '<>', $this->sku)
                ->pluck('sku')
                ->toArray();

            $skus = array_merge($skus, $groupSkus);
        }

        // جلب المنتجات الفعلية من جدول products
        $products = Product::whereIn('sku', $skus)->get();

        // ترتيب:
        // 1) اللي لهم سعر ومخزون بالبداية
        // 2) داخلهم الترتيب من الأعلى سعراً
        return $products->sortByDesc(function ($product) {
            $hasStockAndPrice = ($product->stock > 0 && $product->vendorPrice() > 0) ? 1 : 0;
            return ($hasStockAndPrice * 1000000) + $product->vendorPrice();
        });
    }

}
