<?php

/**
 * @dataflow-exception: Cart context injection requires direct DB access for performance
 * TODO: Consider moving to CatalogContextService in Domain/Catalog/Services
 */

namespace App\Helpers;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\DB;

/**
 * CatalogItemContextHelper
 *
 * Helper مركزي لحقن سياق التاجر (Merchant Context) في كائن CatalogItem.
 *
 * المعمارية الجديدة:
 * - CatalogItem يحتوي فقط على بيانات الكتالوج (catalog_items table)
 * - MerchantItem يحتوي على بيانات التاجر (price, stock, policy, features...)
 * - لا يوجد __get magic fallback بين الجدولين
 *
 * متى يُستخدم هذا Helper:
 * - عند الحاجة لإرفاق سياق تاجر معين مع كائن CatalogItem
 * - في سياق السلة والدفع حيث نحتاج CatalogItem مع سعر/مخزون تاجر محدد
 *
 * كيف يعمل:
 * - يحقن قيم MerchantItem مباشرة في $attributes
 * - Laravel's default __get يُعيد هذه القيم عند الوصول
 *
 * الاستخدام:
 * ```php
 * $catalogItem = CatalogItemContextHelper::createWithContext($merchantItem);
 * // أو
 * CatalogItemContextHelper::apply($catalogItem, $merchantItem);
 * ```
 */
class CatalogItemContextHelper
{
    /**
     * إنشاء CatalogItem instance جديد مع سياق merchant محقون
     *
     * @param MerchantItem $mp
     * @return CatalogItem
     */
    public static function createWithContext(MerchantItem $mp): CatalogItem
    {
        // جلب بيانات المنتج من قاعدة البيانات مباشرة (بدون Eloquent caching)
        $catalogItemData = DB::table('catalog_items')->where('id', $mp->catalog_item_id)->first();

        if (!$catalogItemData) {
            throw new \Exception("Catalog item not found: {$mp->catalog_item_id}");
        }

        // إنشاء CatalogItem instance جديد
        $catalogItem = new CatalogItem();

        // ملء البيانات الأساسية
        foreach ((array)$catalogItemData as $key => $value) {
            $catalogItem->$key = $value;
        }

        $catalogItem->exists = true;
        $catalogItem->wasRecentlyCreated = false;

        // حقن سياق التاجر
        self::apply($catalogItem, $mp);

        return $catalogItem;
    }

    /**
     * حقن سياق merchant في CatalogItem instance موجود
     *
     * هذا Method يحقن:
     * - السعر المحسوب من MerchantItem::merchantSizePrice()
     * - معلومات التاجر (user_id, merchant_item_id)
     * - معلومات الجودة (quality_brand_id)
     * - المخزون وبيانات إضافية
     *
     * القيم المحقونة تُخزن في $attributes ويمكن الوصول إليها عبر Laravel's default __get
     *
     * @param CatalogItem $catalogItem
     * @param MerchantItem $mp
     * @return void
     */
    public static function apply(CatalogItem $catalogItem, MerchantItem $mp): void
    {
        // حساب السعر النهائي (مع العمولة والمقاسات)
        $calculatedPrice = method_exists($mp, 'merchantSizePrice')
            ? $mp->merchantSizePrice()
            : (float)$mp->price;

        // حقن معلومات التاجر والسعر في attributes
        $catalogItem->merchant_user_id = $mp->user_id;
        $catalogItem->user_id = $mp->user_id;
        $catalogItem->merchant_item_id = $mp->id;
        $catalogItem->quality_brand_id = $mp->quality_brand_id;
        $catalogItem->price = $calculatedPrice;
        $catalogItem->previous_price = $mp->previous_price;
        $catalogItem->stock = $mp->stock;
        $catalogItem->item_condition = $mp->item_condition;

        // معلومات إضافية
        $catalogItem->stock_check = $mp->stock_check ?? null;
        $catalogItem->minimum_qty = $mp->minimum_qty ?? null;
        $catalogItem->preordered = $mp->preordered ?? 0;
        $catalogItem->whole_sell_qty = $mp->whole_sell_qty ?? null;
        $catalogItem->whole_sell_discount = $mp->whole_sell_discount ?? null;
        $catalogItem->ship = $mp->ship ?? null;
        $catalogItem->policy = $mp->policy ?? null;
    }

    /**
     * حقن سياق متعدد التجار (للمنتجات ذات التجار المتعددة)
     *
     * يُستخدم عند عرض قائمة منتجات حيث كل منتج قد يكون له عدة تجار
     *
     * @param array $merchantItems مصفوفة من MerchantItem
     * @return array مصفوفة من CatalogItem مع السياق المحقون
     */
    public static function createMultipleWithContext(array $merchantItems): array
    {
        $catalogItems = [];

        foreach ($merchantItems as $mp) {
            if ($mp instanceof MerchantItem) {
                $catalogItems[] = self::createWithContext($mp);
            }
        }

        return $catalogItems;
    }

    /**
     * التحقق من أن CatalogItem له سياق merchant محقون
     *
     * @param CatalogItem $catalogItem
     * @return bool
     */
    public static function hasContext(CatalogItem $catalogItem): bool
    {
        $attributes = $catalogItem->getAttributes();

        return isset($attributes['merchant_item_id'])
            && isset($attributes['user_id'])
            && isset($attributes['price']);
    }

    /**
     * الحصول على معلومات السياق المحقون
     *
     * @param CatalogItem $catalogItem
     * @return array|null
     */
    public static function getContext(CatalogItem $catalogItem): ?array
    {
        if (!self::hasContext($catalogItem)) {
            return null;
        }

        $attributes = $catalogItem->getAttributes();

        return [
            'merchant_item_id' => $attributes['merchant_item_id'] ?? null,
            'user_id' => $attributes['user_id'] ?? null,
            'merchant_user_id' => $attributes['merchant_user_id'] ?? null,
            'quality_brand_id' => $attributes['quality_brand_id'] ?? null,
            'price' => $attributes['price'] ?? null,
            'stock' => $attributes['stock'] ?? null,
        ];
    }

    /**
     * إزالة السياق المحقون وإعادة CatalogItem لحالته الأصلية
     *
     * @param CatalogItem $catalogItem
     * @return void
     */
    public static function removeContext(CatalogItem $catalogItem): void
    {
        $contextKeys = [
            'merchant_user_id',
            'merchant_item_id',
            'quality_brand_id',
            'price',
            'previous_price',
            'stock',
            'item_condition',
            'stock_check',
            'minimum_qty',
            'whole_sell_qty',
            'whole_sell_discount',
            'ship',
            'policy',
            'features',
        ];

        $attributes = $catalogItem->getAttributes();

        foreach ($contextKeys as $key) {
            unset($attributes[$key]);
        }

        // Update attributes using reflection to bypass protected property
        $reflection = new \ReflectionClass($catalogItem);
        $attributesProperty = $reflection->getProperty('attributes');
        $attributesProperty->setAccessible(true);
        $attributesProperty->setValue($catalogItem, $attributes);
    }
}
