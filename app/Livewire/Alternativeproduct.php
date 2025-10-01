<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SkuAlternative;
use App\Models\MerchantProduct;
use Livewire\Component;

class Alternativeproduct extends Component
{
    /**
     * ملاحظة: صرنا نخزن هنا سجلات MerchantProduct (عروض البائعين)،
     * وليس منتجات فقط. الـ Blade لازم يتعامل مع كل عنصر على أنه
     * MerchantProduct وله علاقة product.
     */
    public $alternatives;
    public $sku;

    public function mount($sku)
    {
        $this->sku = $sku;
        $this->alternatives = collect();
    }

    public function render()
    {
        return view('livewire.alternativeproduct', [
            // الآن ترجع قوائم البائعين لكل بديل
            'alternatives' => $this->getalternatives(),
        ]);
    }

    public function getalternatives()
    {
        // 1) اجلب سطر الـ SKU الأساسي
        $skuAlternative = SkuAlternative::where('sku', $this->sku)->first();

        if (! $skuAlternative || ! $skuAlternative->group_id) {
            return collect();
        }

        // 2) كل SKUs داخل نفس القروب (باستثناء نفسه)
        $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
            ->where('sku', '<>', $this->sku)
            ->pluck('sku')
            ->toArray();

        if (empty($alternativeSkus)) {
            return collect();
        }

        // 3) اجلب قوائم البائعين (merchant_products) للمنتجات التي تحمل هذه الـ SKUs
        $listings = MerchantProduct::with([
                'product' => function ($q) {
                    // اجلب الحقول المهمة فقط لتقليل الحمل (اختياري)
                    $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_vendor',
            ])
            ->where('status', 1) // عرض البائع مفعل لهذه القطعة
            ->whereHas('user', function ($u) {
                $u->where('is_vendor', 2); // البائع مفعل
            })
            ->whereHas('product', function ($q) use ($alternativeSkus) {
                $q->whereIn('sku', $alternativeSkus);
            })
            ->get();

        // 4) ترتيب: العروض التي بها مخزون وسعر > 0 أولاً، ثم حسب السعر
        $sorted = $listings->sortByDesc(function (MerchantProduct $mp) {
            $vp  = method_exists($mp, 'vendorSizePrice') ? (float) $mp->vendorSizePrice() : (float) $mp->price;
            $has = ($mp->stock > 0 && $vp > 0) ? 1 : 0;
            return ($has * 1000000) + $vp;
        })->values();

        // خزّن وأعد
        $this->alternatives = $sorted;
        return $this->alternatives;
    }
}
