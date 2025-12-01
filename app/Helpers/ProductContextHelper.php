<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\MerchantProduct;
use Illuminate\Support\Facades\DB;

/**
 * ProductContextHelper
 *
 * Helper مركزي لحقن سياق التاجر (Merchant Context) في كائن Product.
 *
 * المشكلة التي يحلها:
 * - Product model لديه __get() الذي يُعيد 'price' => 'vendorPrice()'
 * - vendorPrice() تستدعي activeMerchant() التي تُعيد أول merchant دائماً
 * - هذا يؤدي لأخذ سعر خاطئ عند وجود عدة تجار لنفس المنتج
 *
 * الحل:
 * - حقن السعر والبيانات مباشرة في $attributes
 * - Product::__get() معدّل ليتحقق من $attributes أولاً قبل استدعاء vendorPrice()
 * - كل combination من (product_id, user_id, brand_quality_id) يكون مستقل
 *
 * الاستخدام:
 * ```php
 * $product = ProductContextHelper::createWithContext($merchantProduct);
 * // أو
 * ProductContextHelper::apply($product, $merchantProduct);
 * ```
 */
class ProductContextHelper
{
    /**
     * إنشاء Product instance جديد مع سياق merchant محقون
     *
     * @param MerchantProduct $mp
     * @return Product
     */
    public static function createWithContext(MerchantProduct $mp): Product
    {
        // جلب بيانات المنتج من قاعدة البيانات مباشرة (بدون Eloquent caching)
        $productData = DB::table('products')->where('id', $mp->product_id)->first();

        if (!$productData) {
            throw new \Exception("Product not found: {$mp->product_id}");
        }

        // إنشاء Product instance جديد
        $product = new Product();

        // ملء البيانات الأساسية
        foreach ((array)$productData as $key => $value) {
            $product->$key = $value;
        }

        $product->exists = true;
        $product->wasRecentlyCreated = false;

        // حقن سياق التاجر
        self::apply($product, $mp);

        return $product;
    }

    /**
     * حقن سياق merchant في Product instance موجود
     *
     * هذا Method يحقن:
     * - السعر المحسوب من MerchantProduct::vendorSizePrice()
     * - معلومات التاجر (user_id, merchant_product_id)
     * - معلومات الجودة (brand_quality_id)
     * - المخزون والأحجام والألوان
     *
     * CRITICAL: القيم المحقونة تُخزن في $attributes مباشرة
     * وبفضل تعديل Product::__get()، هذه القيم لها الأولوية على vendorPrice()
     *
     * @param Product $product
     * @param MerchantProduct $mp
     * @return void
     */
    public static function apply(Product $product, MerchantProduct $mp): void
    {
        // حساب السعر النهائي (مع العمولة والمقاسات)
        $calculatedPrice = method_exists($mp, 'vendorSizePrice')
            ? $mp->vendorSizePrice()
            : (float)$mp->price;

        // حقن معلومات التاجر والسعر
        // CRITICAL: هذه القيم تُخزن في $attributes وتأخذ الأولوية على __get()
        $product->vendor_user_id = $mp->user_id;
        $product->user_id = $mp->user_id;
        $product->merchant_product_id = $mp->id;
        $product->brand_quality_id = $mp->brand_quality_id; // مهم: لتمييز نفس المنتج بجودة مختلفة
        $product->price = $calculatedPrice; // الآن __get() سيُعيد هذا بدلاً من vendorPrice()
        $product->previous_price = $mp->previous_price;
        $product->stock = $mp->stock;
        $product->product_condition = $mp->product_condition;

        // معلومات المقاسات
        $product->size = $mp->size;
        $product->size_qty = $mp->size_qty;
        $product->size_price = $mp->size_price;

        // معلومات إضافية
        $product->stock_check = $mp->stock_check ?? null;
        $product->minimum_qty = $mp->minimum_qty ?? null;
        $product->preordered = $mp->preordered ?? 0;
        $product->whole_sell_qty = $mp->whole_sell_qty ?? null;
        $product->whole_sell_discount = $mp->whole_sell_discount ?? null;
        $product->ship = $mp->ship ?? null;
        $product->policy = $mp->policy ?? null;
        $product->features = $mp->features ?? null;

        // الألوان (تأتي من merchant_products فقط)
        $product->color_all = $mp->color_all ?? null;
        $product->color_price = $mp->color_price ?? null;
    }

    /**
     * حقن سياق متعدد التجار (للمنتجات ذات التجار المتعددة)
     *
     * يُستخدم عند عرض قائمة منتجات حيث كل منتج قد يكون له عدة تجار
     *
     * @param array $merchantProducts مصفوفة من MerchantProduct
     * @return array مصفوفة من Product مع السياق المحقون
     */
    public static function createMultipleWithContext(array $merchantProducts): array
    {
        $products = [];

        foreach ($merchantProducts as $mp) {
            if ($mp instanceof MerchantProduct) {
                $products[] = self::createWithContext($mp);
            }
        }

        return $products;
    }

    /**
     * التحقق من أن Product له سياق merchant محقون
     *
     * @param Product $product
     * @return bool
     */
    public static function hasContext(Product $product): bool
    {
        $attributes = $product->getAttributes();

        return isset($attributes['merchant_product_id'])
            && isset($attributes['user_id'])
            && isset($attributes['price']);
    }

    /**
     * الحصول على معلومات السياق المحقون
     *
     * @param Product $product
     * @return array|null
     */
    public static function getContext(Product $product): ?array
    {
        if (!self::hasContext($product)) {
            return null;
        }

        $attributes = $product->getAttributes();

        return [
            'merchant_product_id' => $attributes['merchant_product_id'] ?? null,
            'user_id' => $attributes['user_id'] ?? null,
            'vendor_user_id' => $attributes['vendor_user_id'] ?? null,
            'brand_quality_id' => $attributes['brand_quality_id'] ?? null,
            'price' => $attributes['price'] ?? null,
            'stock' => $attributes['stock'] ?? null,
        ];
    }

    /**
     * إزالة السياق المحقون وإعادة Product لحالته الأصلية
     *
     * @param Product $product
     * @return void
     */
    public static function removeContext(Product $product): void
    {
        $contextKeys = [
            'vendor_user_id',
            'merchant_product_id',
            'brand_quality_id',
            'price',
            'previous_price',
            'stock',
            'product_condition',
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

        $attributes = $product->getAttributes();

        foreach ($contextKeys as $key) {
            unset($attributes[$key]);
        }

        // Update attributes using reflection to bypass protected property
        $reflection = new \ReflectionClass($product);
        $attributesProperty = $reflection->getProperty('attributes');
        $attributesProperty->setAccessible(true);
        $attributesProperty->setValue($product, $attributes);
    }
}
