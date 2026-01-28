<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ActiveMerchantsQuery - Query for active merchants with catalog items
 *
 * Provides methods to retrieve merchants who have active listings.
 */
class ActiveMerchantsQuery
{
    /**
     * Get all active merchants with catalog items
     */
    public static function all(): Collection
    {
        return MerchantItem::select('merchant_items.user_id')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->where('merchant_items.status', 1)
            ->where('users.is_merchant', 2)
            ->groupBy('merchant_items.user_id')
            ->selectRaw('merchant_items.user_id, users.shop_name, users.shop_name_ar')
            ->orderBy('users.shop_name', 'asc')
            ->get();
    }

    /**
     * Get merchants with items in specific category
     */
    public static function forCategory(int $categoryId): Collection
    {
        return MerchantItem::select('merchant_items.user_id')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->join('catalog_items', 'catalog_items.id', '=', 'merchant_items.catalog_item_id')
            ->where('merchant_items.status', 1)
            ->where('users.is_merchant', 2)
            ->where('catalog_items.category_id', $categoryId)
            ->groupBy('merchant_items.user_id')
            ->selectRaw('merchant_items.user_id, users.shop_name, users.shop_name_ar')
            ->orderBy('users.shop_name', 'asc')
            ->get();
    }

    /**
     * Get merchants with items for specific brand (via fitments)
     */
    public static function forBrand(int $brandId): Collection
    {
        return MerchantItem::select('merchant_items.user_id')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->join('catalog_items', 'catalog_items.id', '=', 'merchant_items.catalog_item_id')
            ->join('catalog_item_fitments', 'catalog_item_fitments.catalog_item_id', '=', 'catalog_items.id')
            ->where('merchant_items.status', 1)
            ->where('users.is_merchant', 2)
            ->where('catalog_item_fitments.brand_id', $brandId)
            ->groupBy('merchant_items.user_id')
            ->selectRaw('merchant_items.user_id, users.shop_name, users.shop_name_ar')
            ->orderBy('users.shop_name', 'asc')
            ->get();
    }

    /**
     * Get merchant count by ID
     */
    public static function getItemCount(int $merchantId): int
    {
        return MerchantItem::where('user_id', $merchantId)
            ->where('status', 1)
            ->count();
    }

    /**
     * Check if merchant has active items
     */
    public static function hasActiveItems(int $merchantId): bool
    {
        return MerchantItem::where('user_id', $merchantId)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Get merchants with their item counts
     */
    public static function withItemCounts(): Collection
    {
        return DB::table('merchant_items')
            ->join('users', 'users.id', '=', 'merchant_items.user_id')
            ->where('merchant_items.status', 1)
            ->where('users.is_merchant', 2)
            ->groupBy('merchant_items.user_id', 'users.shop_name', 'users.shop_name_ar')
            ->select(
                'merchant_items.user_id',
                'users.shop_name',
                'users.shop_name_ar',
                DB::raw('COUNT(*) as items_count')
            )
            ->orderBy('items_count', 'desc')
            ->get();
    }
}
