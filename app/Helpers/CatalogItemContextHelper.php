<?php

namespace App\Helpers;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use Illuminate\Support\Facades\DB;

/**
 * CatalogItemContextHelper
 *
 * Helper مركزي لحقن سياق التاجر (Merchant Context) في كائن CatalogItem.
 *
 * المشكلة التي يحلها:
 * - CatalogItem model لديه __get() الذي يُعيد 'price' => 'merchantPrice()'
 * - merchantPrice() تستدعي activeMerchant() التي تُعيد أول merchant دائماً
 * - هذا يؤدي لأخذ سعر خاطئ عند وجود عدة تجار لنفس المنتج
 *
 * الحل:
 * - حقن السعر والبيانات مباشرة في $attributes
 * - CatalogItem::__get() معدّل ليتحقق من $attributes أولاً قبل استدعاء merchantPrice()
 * - كل combination من (catalog_item_id, user_id, brand_quality_id) يكون مستقل
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
     * - معلومات الجودة (brand_quality_id)
     * - المخزون والأحجام والألوان
     *
     * CRITICAL: القيم المحقونة تُخزن في $attributes مباشرة
     * وبفضل تعديل CatalogItem::__get()، هذه القيم لها الأولوية على merchantPrice()
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

        // حقن معلومات التاجر والسعر
        // CRITICAL: هذه القيم تُخزن في $attributes وتأخذ الأولوية على __get()
        $catalogItem->merchant_user_id = $mp->user_id;
        $catalogItem->user_id = $mp->user_id;
        $catalogItem->merchant_item_id = $mp->id;
        $catalogItem->brand_quality_id = $mp->brand_quality_id; // مهم: لتمييز نفس المنتج بجودة مختلفة
        $catalogItem->price = $calculatedPrice; // الآن __get() سيُعيد هذا بدلاً من merchantPrice()
        $catalogItem->previous_price = $mp->previous_price;
        $catalogItem->stock = $mp->stock;
        $catalogItem->item_condition = $mp->item_condition;

        // معلومات المقاسات
        $catalogItem->size = $mp->size;
        $catalogItem->size_qty = $mp->size_qty;
        $catalogItem->size_price = $mp->size_price;

        // معلومات إضافية
        $catalogItem->stock_check = $mp->stock_check ?? null;
        $catalogItem->minimum_qty = $mp->minimum_qty ?? null;
        $catalogItem->preordered = $mp->preordered ?? 0;
        $catalogItem->whole_sell_qty = $mp->whole_sell_qty ?? null;
        $catalogItem->whole_sell_discount = $mp->whole_sell_discount ?? null;
        $catalogItem->ship = $mp->ship ?? null;
        $catalogItem->policy = $mp->policy ?? null;
        $catalogItem->features = $mp->features ?? null;

        // الألوان (تأتي من merchant_items فقط)
        $catalogItem->color_all = $mp->color_all ?? null;
        $catalogItem->color_price = $mp->color_price ?? null;
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
            'brand_quality_id' => $attributes['brand_quality_id'] ?? null,
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
            'brand_quality_id',
            'price',
            'previous_price',
            'stock',
            'item_condition',
            'size',
            'size_qty',
            'size_price',
            'stock_check',
            'minimum_qty',
            'whole_sell_qty',
            'whole_sell_discount',
            'ship',
            'policy',
            'features',
            'color_all',
            'color_price',
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
