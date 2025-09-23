<?php

namespace App\Livewire;

use App\Models\SkuAlternative;
use App\Models\MerchantProduct;
use Livewire\Component;

class Alternativerelatedproduct extends Component
{
    /**
     * نعرض عروض البائعين (MerchantProduct) لبدائل الـ SKU.
     * ملاحظة فحص: فعّل dd أدناه عند الحاجة.
     */
    public $alternatives;
    public $sku;

    public function mount($sku)
    {
        $this->sku = $sku;
        $this->alternatives = collect();
        // dd($this->sku); // فحص سريع
    }

    public function render()
    {
        return view('livewire.alternativerelatedproduct', [
            'alternatives' => $this->getalternatives(),
        ]);
    }

    public function getalternatives()
    {
        // 1) سطر الـ SKU الأساسي
        $skuAlternative = SkuAlternative::where('sku', $this->sku)->first();

        if (!$skuAlternative || !$skuAlternative->group_id) {
            return collect();
        }

        // 2) كل SKUs في نفس القروب (باستثناء نفسه)
        $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
            ->where('sku', '<>', $this->sku)
            ->pluck('sku')
            ->toArray();

        if (empty($alternativeSkus)) {
            return collect();
        }

        // 3) عروض البائعين لمنتجات هذه الـ SKUs
        $listings = MerchantProduct::with([
                'product' => function ($q) {
                    $q->select('id','sku','slug','label_en','label_ar','photo','brand_id');
                },
                'user:id,is_vendor',
            ])
            ->where('status', 1)
            ->whereHas('user', fn($u) => $u->where('is_vendor', 2))
            ->whereHas('product', fn($q) => $q->whereIn('sku', $alternativeSkus))
            ->get();

        // 4) ترتيب: المتوفر وسعره > 0 أولاً، ثم حسب السعر تصاعديًا
        $sorted = $listings->sortBy(function (MerchantProduct $mp) {
            $vp  = method_exists($mp, 'vendorSizePrice') ? (float) $mp->vendorSizePrice() : (float) $mp->price;
            $has = ($mp->stock > 0 && $vp > 0) ? 0 : 1;   // 0 أولًا
            return [$has, $vp];
        })->values();

        // خزّن وأعد
        $this->alternatives = $sorted;
        // dd($this->alternatives->take(3)); // فحص سريع
        return $this->alternatives;
    }
}
