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
     * جلب البدائل لـ PART_NUMBER معين
     * يجلب البدائل من sku_alternatives مجمعة حسب catalog_item مع أقل سعر
     *
     * @return Collection of catalog items with lowest_price and offers_count
     */
    public function getAlternatives(string $part_number, bool $includeSelf = false): Collection
    {
        // ✅ استعلام واحد لجلب المنتج و group_id
        $baseData = DB::table('catalog_items as p')
            ->leftJoin('sku_alternatives as sa', 'sa.part_number', '=', 'p.part_number')
            ->where('p.part_number', $part_number)
            ->select('p.id as catalog_item_id', 'sa.group_id')
            ->first();

        if (!$baseData) {
            return collect();
        }

        $catalogItemId = $baseData->catalog_item_id;
        $groupId = $baseData->group_id;

        if (!$groupId) {
            return collect();
        }

        // جلب catalog_item_ids للبدائل
        $alternativeCatalogItemIds = DB::table('sku_alternatives as sa')
            ->join('catalog_items as p', 'p.part_number', '=', 'sa.part_number')
            ->where('sa.group_id', $groupId)
            ->when(!$includeSelf, fn($q) => $q->where('sa.part_number', '<>', $part_number))
            ->pluck('p.id')
            ->toArray();

        if (empty($alternativeCatalogItemIds)) {
            return collect();
        }

        // جلب catalog_items مع أقل سعر وعدد العروض
        $catalogItems = \App\Models\CatalogItem::whereIn('id', $alternativeCatalogItemIds)
            ->with(['fitments.brand'])
            ->get();

        // جلب إحصائيات العروض لكل catalog_item
        $offersStats = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->whereIn('merchant_items.catalog_item_id', $alternativeCatalogItemIds)
            ->where('merchant_items.status', 1)
            ->where('merchant_items.price', '>', 0)
            ->groupBy('merchant_items.catalog_item_id')
            ->select([
                'merchant_items.catalog_item_id',
                DB::raw('MIN(merchant_items.price) as lowest_price'),
                DB::raw('COUNT(*) as offers_count'),
            ])
            ->get()
            ->keyBy('catalog_item_id');

        // دمج البيانات
        return $catalogItems->map(function ($catalogItem) use ($offersStats) {
            $stats = $offersStats->get($catalogItem->id);
            $catalogItem->lowest_price = $stats?->lowest_price ?? null;
            $catalogItem->lowest_price_formatted = $stats?->lowest_price
                ? \App\Models\CatalogItem::convertPrice($stats->lowest_price)
                : null;
            $catalogItem->offers_count = $stats?->offers_count ?? 0;
            return $catalogItem;
        })->filter(fn($item) => $item->offers_count > 0) // فقط القطع التي لها عروض
          ->sortBy('lowest_price')
          ->values();
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
                'catalogItem' => fn($q) => $q->select('id', 'part_number', 'slug', 'label_en', 'label_ar', 'photo'),
                'catalogItem.fitments.brand',  // Vehicle brand from fitments
                'user:id,is_merchant,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_items.id',
                'merchant_items.catalog_item_id',
                'merchant_items.user_id',
                'merchant_items.quality_brand_id',
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
            ->whereIn('part_number', $skus)
            ->pluck('id')
            ->toArray();

        if (empty($catalogItemIds)) return collect();

        $listings = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->whereIn('merchant_items.catalog_item_id', $catalogItemIds)
            ->where('merchant_items.status', 1)
            ->with([
                'catalogItem' => fn($q) => $q->select('id', 'part_number', 'slug', 'label_en', 'label_ar', 'photo'),
                'catalogItem.fitments.brand',  // Vehicle brand from fitments
                'user:id,is_merchant,name,shop_name,shop_name_ar',
                'qualityBrand:id,name_en,name_ar,logo',
            ])
            ->select([
                'merchant_items.id',
                'merchant_items.catalog_item_id',
                'merchant_items.user_id',
                'merchant_items.quality_brand_id',
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
    public function hasAlternatives(string $part_number): bool
    {
        $groupId = SkuAlternative::where('part_number', $part_number)->value('group_id');

        if (!$groupId) {
            return false;
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('part_number', '<>', $part_number)
            ->limit(1)
            ->exists();
    }

    /**
     * عدد البدائل المتاحة
     *
     * ✅ محسّن: استخدام value بدلاً من first
     */
    public function countAlternatives(string $part_number): int
    {
        $groupId = SkuAlternative::where('part_number', $part_number)->value('group_id');

        if (!$groupId) {
            return 0;
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('part_number', '<>', $part_number)
            ->count();
    }

    /**
     * جلب SKUs البديلة فقط (بدون بيانات المنتجات)
     *
     * ✅ محسّن: استخدام value
     */
    public function getAlternativeSkus(string $part_number): array
    {
        $groupId = SkuAlternative::where('part_number', $part_number)->value('group_id');

        if (!$groupId) {
            return [];
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('part_number', '<>', $part_number)
            ->pluck('part_number')
            ->toArray();
    }
}
