<?php

namespace App\Services;

use App\Models\MerchantProduct;
use App\Models\SkuAlternative;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة موحدة لإدارة البدائل
 *
 * ✅ محسّن: استخدام JOIN بدلاً من whereHas + تقليل N+1 queries
 */
class AlternativeService
{
    /**
     * جلب البدائل لـ SKU معين
     * يجلب البدائل من sku_alternatives + جميع العروض المختلفة لنفس المنتج
     *
     * ✅ محسّن: استعلام واحد مجمع
     */
    public function getAlternatives(string $sku, bool $includeSelf = false): Collection
    {
        // ✅ استعلام واحد لجلب المنتج و group_id
        $baseData = DB::table('products as p')
            ->leftJoin('sku_alternatives as sa', 'sa.sku', '=', 'p.sku')
            ->where('p.sku', $sku)
            ->select('p.id as product_id', 'sa.group_id')
            ->first();

        if (!$baseData) {
            return collect();
        }

        $productId = $baseData->product_id;
        $groupId = $baseData->group_id;

        // ✅ جمع جميع product_ids في استعلام واحد
        $productIds = collect([$productId]);

        if ($groupId) {
            // جلب product_ids للبدائل
            $alternativeProductIds = DB::table('sku_alternatives as sa')
                ->join('products as p', 'p.sku', '=', 'sa.sku')
                ->where('sa.group_id', $groupId)
                ->when(!$includeSelf, fn($q) => $q->where('sa.sku', '<>', $sku))
                ->pluck('p.id');

            $productIds = $productIds->merge($alternativeProductIds)->unique();
        }

        // ✅ استعلام واحد لجميع merchant_products باستخدام JOIN
        $listings = MerchantProduct::query()
            ->join('users as u', 'u.id', '=', 'merchant_products.user_id')
            ->where('u.is_vendor', 2)
            ->whereIn('merchant_products.product_id', $productIds->toArray())
            ->where('merchant_products.status', 1)
            ->with([
                'product' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_vendor,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_products.id',
                'merchant_products.product_id',
                'merchant_products.user_id',
                'merchant_products.brand_quality_id',
                'merchant_products.price',
                'merchant_products.previous_price',
                'merchant_products.stock',
                'merchant_products.preordered',
                'merchant_products.minimum_qty',
                'merchant_products.status'
            ])
            ->get();

        // إزالة المنتج الأصلي إذا لم يكن includeSelf
        if (!$includeSelf) {
            $listings = $listings->filter(fn($mp) => $mp->product_id != $productId);
        }

        return $this->sortByPriority($listings);
    }

    /**
     * جلب جميع العروض لنفس المنتج (variants من شركات مختلفة)
     *
     * ✅ محسّن: استخدام JOIN بدلاً من whereHas
     */
    protected function fetchSameProductVariants(int $productId, bool $includeSelf): Collection
    {
        return MerchantProduct::query()
            ->join('users as u', 'u.id', '=', 'merchant_products.user_id')
            ->where('u.is_vendor', 2)
            ->where('merchant_products.status', 1)
            ->where('merchant_products.product_id', $productId)
            ->with([
                'product' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_vendor,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_products.id',
                'merchant_products.product_id',
                'merchant_products.user_id',
                'merchant_products.brand_quality_id',
                'merchant_products.price',
                'merchant_products.previous_price',
                'merchant_products.stock',
                'merchant_products.preordered',
                'merchant_products.minimum_qty',
                'merchant_products.status'
            ])
            ->get();
    }

    /**
     * جلب عروض البائعين للمنتجات
     *
     * ✅ محسّن: استخدام JOIN بدلاً من whereHas المتعددة
     */
    protected function fetchMerchantProducts(array $skus): Collection
    {
        if (empty($skus)) return collect();

        // ✅ جلب product_ids أولاً
        $productIds = DB::table('products')
            ->whereIn('sku', $skus)
            ->pluck('id')
            ->toArray();

        if (empty($productIds)) return collect();

        $listings = MerchantProduct::query()
            ->join('users as u', 'u.id', '=', 'merchant_products.user_id')
            ->where('u.is_vendor', 2)
            ->whereIn('merchant_products.product_id', $productIds)
            ->where('merchant_products.status', 1)
            ->with([
                'product' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_vendor,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_products.id',
                'merchant_products.product_id',
                'merchant_products.user_id',
                'merchant_products.brand_quality_id',
                'merchant_products.price',
                'merchant_products.previous_price',
                'merchant_products.stock',
                'merchant_products.preordered',
                'merchant_products.minimum_qty',
                'merchant_products.status'
            ])
            ->get();

        return $this->sortByPriority($listings);
    }

    /**
     * ترتيب العروض حسب الأولوية
     * ✅ محسّن: تبسيط المنطق
     */
    protected function sortByPriority(Collection $listings): Collection
    {
        return $listings->sortBy([
            // أولاً: المتوفر (stock > 0 && price > 0)
            fn($mp) => ($mp->stock > 0 && $mp->price > 0) ? 0 : 1,
            // ثانياً: السعر الأقل
            fn($mp) => (float) $mp->price,
        ])->values();
    }

    /**
     * التحقق من وجود بدائل
     *
     * ✅ محسّن: استعلام مباشر مع limit
     */
    public function hasAlternatives(string $sku): bool
    {
        $groupId = SkuAlternative::where('sku', $sku)->value('group_id');

        if (!$groupId) {
            return false;
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('sku', '<>', $sku)
            ->limit(1)
            ->exists();
    }

    /**
     * عدد البدائل المتاحة
     *
     * ✅ محسّن: استخدام value بدلاً من first
     */
    public function countAlternatives(string $sku): int
    {
        $groupId = SkuAlternative::where('sku', $sku)->value('group_id');

        if (!$groupId) {
            return 0;
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('sku', '<>', $sku)
            ->count();
    }

    /**
     * جلب SKUs البديلة فقط (بدون بيانات المنتجات)
     *
     * ✅ محسّن: استخدام value
     */
    public function getAlternativeSkus(string $sku): array
    {
        $groupId = SkuAlternative::where('sku', $sku)->value('group_id');

        if (!$groupId) {
            return [];
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('sku', '<>', $sku)
            ->pluck('sku')
            ->toArray();
    }
}
