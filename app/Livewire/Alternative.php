<?php

namespace App\Livewire;

use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\SkuAlternative;
use Illuminate\Support\Collection;
use Livewire\Component;

class Alternative extends Component
{
    public string $sku;

    /** @var \Illuminate\Support\Collection<int,\App\Models\MerchantProduct> */
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
     * إرجاع قائمة عروض البائعين (merchant_products) المرتبطة بالـ SKU والبدائل.
     * كل عنصر = MerchantProduct (أي بائع محدد على منتج محدد).
     */
    protected function fetchAlternatives(): Collection
    {
        // 1) جلب صف الـ SKU من جدول sku_alternatives
        $skuAlt = SkuAlternative::where('sku', $this->sku)->first();

        // 2) نجمع SKUs: المنتج نفسه + البدائل في نفس القروب
        $skus = [$this->sku];
        if ($skuAlt && $skuAlt->group_id) {
            $groupSkus = SkuAlternative::where('group_id', $skuAlt->group_id)
                ->where('sku', '<>', $this->sku)
                ->pluck('sku')
                ->toArray();
            $skus = array_merge($skus, $groupSkus);
        }

        // 3) اجلب قوائم البائعين للمنتجات التي تحمل هذه الـ SKUs
        $listings = MerchantProduct::with([
                'product' => function ($q) {
                    $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_vendor',
            ])
            ->where('status', 1)
            ->whereHas('user', function ($u) {
                $u->where('is_vendor', 2);
            })
            ->whereHas('product', function ($q) use ($skus) {
                $q->whereIn('sku', $skus);
            })
            ->get();

        // 4) ترتيب: العروض التي بها مخزون وسعر > 0 أولاً، ثم حسب السعر (تنازليًا)
        $sorted = $listings->sortByDesc(function (MerchantProduct $mp) {
            $vp  = method_exists($mp, 'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
            $has = ($mp->stock > 0 && $vp > 0) ? 1 : 0;
            return ($has * 1000000) + $vp;
        })->values();

        return $sorted;
    }
}
