<?php

namespace App\Services;

use App\Models\MerchantProduct;
use App\Models\SkuAlternative;
use Illuminate\Support\Collection;

/**
 * خدمة موحدة لإدارة البدائل
 */
class AlternativeService
{
    /**
     * جلب البدائل لـ SKU معين
     * يجلب البدائل من sku_alternatives + جميع العروض المختلفة لنفس المنتج
     */
    public function getAlternatives(string $sku, bool $includeSelf = false): Collection
    {
        $skuAlt = SkuAlternative::where('sku', $sku)->first();
        $product = \App\Models\Product::where('sku', $sku)->first();

        if (!$product) {
            return collect();
        }

        $skus = [];

        if ($includeSelf) {
            $skus[] = $sku;
        }

        // جلب البدائل من جدول sku_alternatives
        if ($skuAlt && $skuAlt->group_id) {
            $groupSkus = SkuAlternative::where('group_id', $skuAlt->group_id)
                ->where('sku', '<>', $sku)
                ->pluck('sku')
                ->toArray();
            $skus = array_merge($skus, $groupSkus);
        }

        // جلب جميع merchant_products للبدائل
        $alternatives = empty($skus) ? collect() : $this->fetchMerchantProducts($skus);

        // جلب جميع merchant_products لنفس product_id (العروض المختلفة)
        $sameProductVariants = $this->fetchSameProductVariants($product->id, $includeSelf);

        // دمج النتائج وإزالة التكرار
        $combined = $alternatives->merge($sameProductVariants)->unique('id');

        return $this->sortByPriority($combined);
    }

    /**
     * جلب جميع العروض لنفس المنتج (variants من شركات مختلفة)
     */
    protected function fetchSameProductVariants(int $productId, bool $includeSelf): Collection
    {
        // جلب جميع merchant_products لنفس product_id
        return MerchantProduct::with([
                'product' => function ($q) {
                    $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_vendor,name,shop_name',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->where('status', 1)
            ->where('product_id', $productId)
            ->whereHas('user', function ($u) {
                $u->where('is_vendor', 2);
            })
            ->get();
    }

    /**
     * جلب عروض البائعين للمنتجات
     */
    protected function fetchMerchantProducts(array $skus): Collection
    {
        $listings = MerchantProduct::with([
                'product' => function ($q) {
                    $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id');
                },
                'user:id,is_vendor,name,shop_name',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->where('status', 1)
            ->whereHas('user', function ($u) {
                $u->where('is_vendor', 2);
            })
            ->whereHas('product', function ($q) use ($skus) {
                $q->whereIn('sku', $skus);
            })
            ->get();

        return $this->sortByPriority($listings);
    }

    /**
     * ترتيب العروض حسب الأولوية
     */
    protected function sortByPriority(Collection $listings): Collection
    {
        return $listings->sortByDesc(function (MerchantProduct $mp) {
            $vp = method_exists($mp, 'vendorSizePrice')
                ? (float)$mp->vendorSizePrice()
                : (float)$mp->price;

            $has = ($mp->stock > 0 && $vp > 0) ? 1 : 0;

            return ($has * 1000000) + $vp;
        })->values();
    }

    /**
     * التحقق من وجود بدائل
     */
    public function hasAlternatives(string $sku): bool
    {
        $skuAlt = SkuAlternative::where('sku', $sku)->first();

        if (!$skuAlt || !$skuAlt->group_id) {
            return false;
        }

        return SkuAlternative::where('group_id', $skuAlt->group_id)
            ->where('sku', '<>', $sku)
            ->exists();
    }

    /**
     * عدد البدائل المتاحة
     */
    public function countAlternatives(string $sku): int
    {
        $skuAlt = SkuAlternative::where('sku', $sku)->first();

        if (!$skuAlt || !$skuAlt->group_id) {
            return 0;
        }

        return SkuAlternative::where('group_id', $skuAlt->group_id)
            ->where('sku', '<>', $sku)
            ->count();
    }

    /**
     * جلب SKUs البديلة فقط (بدون بيانات المنتجات)
     */
    public function getAlternativeSkus(string $sku): array
    {
        $skuAlt = SkuAlternative::where('sku', $sku)->first();

        if (!$skuAlt || !$skuAlt->group_id) {
            return [];
        }

        return SkuAlternative::where('group_id', $skuAlt->group_id)
            ->where('sku', '<>', $sku)
            ->pluck('sku')
            ->toArray();
    }
}
