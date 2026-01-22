<?php

namespace App\Services;

use App\Models\MerchantItem;
use App\Models\SkuAlternative;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * خدمة موحدة لإدارة البدائل
 *
 * تستخدم جدول sku_alternatives مع group_id
 *
 * الهيكل:
 * - كل الأصناف بنفس group_id هي بدائل لبعضها البعض
 * - إذا A في group_id=100 و B في group_id=100، فـ A بديل لـ B و B بديل لـ A
 */
class AlternativeService
{
    /**
     * جلب البدائل لـ PART_NUMBER معين
     * يجلب البدائل من sku_alternatives باستخدام group_id
     *
     * @param string $part_number رقم القطعة
     * @param bool $includeSelf تضمين الصنف نفسه في النتائج
     * @param bool $returnSelfIfNoAlternatives إرجاع الصنف نفسه إذا لم يكن له بدائل
     * @return Collection of catalog items with lowest_price and offers_count
     */
    public function getAlternatives(string $part_number, bool $includeSelf = false, bool $returnSelfIfNoAlternatives = false): Collection
    {
        // جلب catalog_item_id و group_id
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

        // إذا لم يكن له group_id
        if (!$groupId) {
            if ($returnSelfIfNoAlternatives) {
                return $this->getSelfAsAlternative($catalogItemId);
            }
            return collect();
        }

        // جلب كل الأصناف في نفس المجموعة
        $query = DB::table('sku_alternatives as sa')
            ->join('catalog_items as p', 'p.part_number', '=', 'sa.part_number')
            ->where('sa.group_id', $groupId);

        // استثناء الصنف نفسه إذا لم يُطلب تضمينه
        if (!$includeSelf) {
            $query->where('sa.part_number', '<>', $part_number);
        }

        $alternativeCatalogItemIds = $query->pluck('p.id')->toArray();

        // إذا لم يوجد بدائل
        if (empty($alternativeCatalogItemIds)) {
            if ($returnSelfIfNoAlternatives) {
                return $this->getSelfAsAlternative($catalogItemId);
            }
            return collect();
        }

        // جلب catalog_items مع العلاقات
        $catalogItems = \App\Models\CatalogItem::whereIn('id', $alternativeCatalogItemIds)
            ->with(['fitments.brand'])
            ->get();

        // جلب MerchantItems لحساب الأسعار مع العمولة
        $merchantItems = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->whereIn('merchant_items.catalog_item_id', $alternativeCatalogItemIds)
            ->where('merchant_items.status', 1)
            ->where('merchant_items.price', '>', 0)
            ->select('merchant_items.*')
            ->get();

        // حساب إحصائيات العروض مع العمولة لكل catalog_item
        $offersStats = [];
        foreach ($merchantItems as $mi) {
            $catalogItemId = $mi->catalog_item_id;
            $priceWithCommission = $mi->merchantSizePrice();

            if (!isset($offersStats[$catalogItemId])) {
                $offersStats[$catalogItemId] = [
                    'lowest_price' => $priceWithCommission,
                    'highest_price' => $priceWithCommission,
                    'offers_count' => 1,
                ];
            } else {
                $offersStats[$catalogItemId]['offers_count']++;
                if ($priceWithCommission < $offersStats[$catalogItemId]['lowest_price']) {
                    $offersStats[$catalogItemId]['lowest_price'] = $priceWithCommission;
                }
                if ($priceWithCommission > $offersStats[$catalogItemId]['highest_price']) {
                    $offersStats[$catalogItemId]['highest_price'] = $priceWithCommission;
                }
            }
        }

        // دمج البيانات
        $result = $catalogItems->map(function ($catalogItem) use ($offersStats) {
            $stats = $offersStats[$catalogItem->id] ?? null;
            $catalogItem->lowest_price = $stats['lowest_price'] ?? null;
            $catalogItem->highest_price = $stats['highest_price'] ?? null;
            $catalogItem->lowest_price_formatted = $stats
                ? \App\Models\CatalogItem::convertPrice($stats['lowest_price'])
                : null;
            $catalogItem->highest_price_formatted = $stats
                ? \App\Models\CatalogItem::convertPrice($stats['highest_price'])
                : null;
            $catalogItem->offers_count = $stats['offers_count'] ?? 0;
            return $catalogItem;
        })->filter(fn($item) => $item->offers_count > 0)
          ->sortBy('lowest_price')
          ->values();

        // إذا لم يوجد بدائل لها عروض، أرجع الصنف نفسه
        if ($result->isEmpty() && $returnSelfIfNoAlternatives) {
            return $this->getSelfAsAlternative($catalogItemId);
        }

        return $result;
    }

    /**
     * إرجاع الصنف نفسه كبديل (عندما لا يوجد بدائل أخرى)
     */
    protected function getSelfAsAlternative(int $catalogItemId): Collection
    {
        $catalogItem = \App\Models\CatalogItem::with(['fitments.brand'])
            ->find($catalogItemId);

        if (!$catalogItem) {
            return collect();
        }

        // جلب MerchantItems لحساب الأسعار مع العمولة
        $merchantItems = MerchantItem::query()
            ->join('users as u', 'u.id', '=', 'merchant_items.user_id')
            ->where('u.is_merchant', 2)
            ->where('merchant_items.catalog_item_id', $catalogItemId)
            ->where('merchant_items.status', 1)
            ->where('merchant_items.price', '>', 0)
            ->select('merchant_items.*')
            ->get();

        if ($merchantItems->isEmpty()) {
            return collect();
        }

        // حساب الأسعار مع العمولة
        $lowestPrice = null;
        $highestPrice = null;
        foreach ($merchantItems as $mi) {
            $priceWithCommission = $mi->merchantSizePrice();
            if ($lowestPrice === null || $priceWithCommission < $lowestPrice) {
                $lowestPrice = $priceWithCommission;
            }
            if ($highestPrice === null || $priceWithCommission > $highestPrice) {
                $highestPrice = $priceWithCommission;
            }
        }

        $catalogItem->lowest_price = $lowestPrice;
        $catalogItem->highest_price = $highestPrice;
        $catalogItem->lowest_price_formatted = $lowestPrice
            ? \App\Models\CatalogItem::convertPrice($lowestPrice)
            : null;
        $catalogItem->highest_price_formatted = $highestPrice
            ? \App\Models\CatalogItem::convertPrice($highestPrice)
            : null;
        $catalogItem->offers_count = $merchantItems->count();

        return collect([$catalogItem]);
    }

    /**
     * جلب جميع العروض لنفس المنتج (variants من شركات مختلفة)
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
                'catalogItem.fitments.brand',
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
     */
    protected function fetchMerchantItems(array $skus): Collection
    {
        if (empty($skus)) return collect();

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
                'catalogItem.fitments.brand',
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
     */
    protected function sortByPriority(Collection $listings): Collection
    {
        return $listings->sortBy([
            fn($mp) => ($mp->stock > 0 && $mp->price > 0) ? 0 : 1,
            fn($mp) => (float) $mp->price,
        ])->values();
    }

    /**
     * التحقق من وجود بدائل
     */
    public function hasAlternatives(string $part_number): bool
    {
        $groupId = SkuAlternative::where('part_number', $part_number)->value('group_id');

        if (!$groupId) {
            return false;
        }

        return SkuAlternative::where('group_id', $groupId)
            ->where('part_number', '<>', $part_number)
            ->exists();
    }

    /**
     * عدد البدائل المتاحة
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
