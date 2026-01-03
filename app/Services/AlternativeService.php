<?php

namespace App\Services;

use App\Models\MerchantItem;
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
        $baseData = DB::table('catalog_items as p')
            ->leftJoin('sku_alternatives as sa', 'sa.sku', '=', 'p.sku')
            ->where('p.sku', $sku)
            ->select('p.id as catalog_item_id', 'sa.group_id')
            ->first();

        if (!$baseData) {
            return collect();
        }

        $catalogItemId = $baseData->catalog_item_id;
        $groupId = $baseData->group_id;

        // ✅ جمع جميع catalog_item_ids في استعلام واحد
        $catalogItemIds = collect([$catalogItemId]);

        if ($groupId) {
            // جلب catalog_item_ids للبدائل
            $alternativeCatalogItemIds = DB::table('sku_alternatives as sa')
                ->join('catalog_items as p', 'p.sku', '=', 'sa.sku')
                ->where('sa.group_id', $groupId)
                ->when(!$includeSelf, fn($q) => $q->where('sa.sku', '<>', $sku))
                ->pluck('p.id');

            $catalogItemIds = $catalogItemIds->merge($alternativeCatalogItemIds)->unique();
        }

        // ✅ استعلام واحد لجميع merchant_items باستخدام JOIN
        $listings = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->whereIn('merchant_items.catalog_item_id', $catalogItemIds->toArray())
            ->where('merchant_items.status', 1)
            ->with([
                'catalogItem' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_merchant,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_items.id',
                'merchant_items.catalog_item_id',
                'merchant_items.user_id',
                'merchant_items.brand_quality_id',
                'merchant_items.price',
                'merchant_items.previous_price',
                'merchant_items.stock',
                'merchant_items.preordered',
                'merchant_items.minimum_qty',
                'merchant_items.status'
            ])
            ->get();

        // إزالة المنتج الأصلي إذا لم يكن includeSelf
        if (!$includeSelf) {
            $listings = $listings->filter(fn($mp) => $mp->catalog_item_id != $catalogItemId);
        }

        return $this->sortByPriority($listings);
    }

    /**
     * جلب جميع العروض لنفس المنتج (variants من شركات مختلفة)
     *
     * ✅ محسّن: استخدام JOIN بدلاً من whereHas
     */
    protected function fetchSameCatalogItemVariants(int $catalogItemId, bool $includeSelf): Collection
    {
        return MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->where('merchant_items.status', 1)
            ->where('merchant_items.catalog_item_id', $catalogItemId)
            ->with([
                'catalogItem' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_merchant,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_items.id',
                'merchant_items.catalog_item_id',
                'merchant_items.user_id',
                'merchant_items.brand_quality_id',
                'merchant_items.price',
                'merchant_items.previous_price',
                'merchant_items.stock',
                'merchant_items.preordered',
                'merchant_items.minimum_qty',
                'merchant_items.status'
            ])
            ->get();
    }

    /**
     * جلب عروض البائعين للمنتجات
     *
     * ✅ محسّن: استخدام JOIN بدلاً من whereHas المتعددة
     */
    protected function fetchMerchantItems(array $skus): Collection
    {
        if (empty($skus)) return collect();

        // ✅ جلب catalog_item_ids أولاً
        $catalogItemIds = DB::table('catalog_items')
            ->whereIn('sku', $skus)
            ->pluck('id')
            ->toArray();

        if (empty($catalogItemIds)) return collect();

        $listings = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->whereIn('merchant_items.catalog_item_id', $catalogItemIds)
            ->where('merchant_items.status', 1)
            ->with([
                'catalogItem' => fn($q) => $q->select('id', 'sku', 'slug', 'label_en', 'label_ar', 'photo', 'brand_id')
                    ->with('brand:id,name,name_ar,photo'),
                'user:id,is_merchant,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_items.id',
                'merchant_items.catalog_item_id',
                'merchant_items.user_id',
                'merchant_items.brand_quality_id',
                'merchant_items.price',
                'merchant_items.previous_price',
                'merchant_items.stock',
                'merchant_items.preordered',
                'merchant_items.minimum_qty',
                'merchant_items.status'
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
