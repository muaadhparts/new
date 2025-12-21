<?php

namespace App\Helpers;

use App\Models\MerchantProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

/**
 * CartHelper - نظام سلة موحد جديد
 *
 * هذا الـ Helper يحل جميع مشاكل السلة القديمة:
 * 1. توحيد cartKey في كل مكان
 * 2. ربط السلة بـ merchant_products
 * 3. دعم المخزون والحد الأدنى والألوان والمقاسات
 * 4. دعم تعدد التجار
 *
 * الاستخدام:
 * - CartHelper::generateKey($mpId, $size, $color, $values) - توليد مفتاح موحد
 * - CartHelper::addItem($mpId, $qty, $size, $color, $values) - إضافة عنصر
 * - CartHelper::increaseQty($cartKey) - زيادة كمية
 * - CartHelper::decreaseQty($cartKey) - إنقاص كمية
 * - CartHelper::removeItem($cartKey) - حذف عنصر
 * - CartHelper::getCart() - جلب السلة
 * - CartHelper::getItem($cartKey) - جلب عنصر محدد
 */
class CartHelper
{
    /**
     * توليد مفتاح السلة الموحد
     *
     * الصيغة: mp{merchant_product_id}:{size}:{color}:{values_hash}
     *
     * @param int $mpId merchant_product_id
     * @param string $size المقاس
     * @param string $color اللون
     * @param string $values قيم إضافية
     * @return string
     */
    public static function generateKey(int $mpId, string $size = '', string $color = '', string $values = ''): string
    {
        $color = ltrim($color, '#');
        $valuesClean = str_replace([' ', ','], '', $values);

        return implode(':', [
            'mp' . $mpId,
            $size ?: '_',
            $color ?: '_',
            $valuesClean ?: '_'
        ]);
    }

    /**
     * توليد مفتاح آمن للـ DOM (بدون أحرف خاصة)
     *
     * @param string $cartKey
     * @return string
     */
    public static function generateDomKey(string $cartKey): string
    {
        return str_replace([':', '#', ' '], ['_', '', ''], $cartKey);
    }

    /**
     * جلب السلة الحالية
     *
     * @return array
     */
    public static function getCart(): array
    {
        return Session::get('cart_v2', [
            'items' => [],
            'totalQty' => 0,
            'totalPrice' => 0.0
        ]);
    }

    /**
     * حفظ السلة
     *
     * @param array &$cart Reference للتعديل المباشر
     * @return array السلة المحدثة
     */
    public static function saveCart(array &$cart): array
    {
        // إعادة حساب الإجماليات
        $cart['totalQty'] = 0;
        $cart['totalPrice'] = 0.0;

        foreach ($cart['items'] as $item) {
            $cart['totalQty'] += (int)($item['qty'] ?? 0);
            $cart['totalPrice'] += (float)($item['total_price'] ?? 0);
        }

        Session::put('cart_v2', $cart);

        return $cart;
    }

    /**
     * جلب عنصر محدد من السلة
     *
     * @param string $cartKey
     * @return array|null
     */
    public static function getItem(string $cartKey): ?array
    {
        $cart = self::getCart();
        return $cart['items'][$cartKey] ?? null;
    }

    /**
     * إضافة عنصر للسلة
     *
     * @param int $mpId merchant_product_id
     * @param int $qty الكمية
     * @param string $size المقاس
     * @param string $color اللون
     * @param string $values قيم إضافية
     * @param array $keys مفاتيح الخصائص
     * @return array ['success' => bool, 'message' => string, 'cart' => array]
     */
    public static function addItem(int $mpId, int $qty = 1, string $size = '', string $color = '', string $values = '', array $keys = []): array
    {
        // جلب بيانات عرض التاجر
        $mp = MerchantProduct::with(['product', 'user', 'qualityBrand'])->find($mpId);

        if (!$mp || $mp->status !== 1) {
            return ['success' => false, 'message' => __('Product not available'), 'cart' => null];
        }

        // فحص الحد الأدنى للكمية
        $minQty = (int)($mp->minimum_qty ?? 1);
        if ($minQty < 1) $minQty = 1;

        if ($qty < $minQty) {
            return ['success' => false, 'message' => __('Minimum order quantity is') . ' ' . $minQty, 'cart' => null];
        }

        // تحديد المقاس الافتراضي إذا لم يُحدد
        if ($size === '' && !empty($mp->size)) {
            $sizes = self::toArray($mp->size);
            $qtys = self::toArray($mp->size_qty);
            foreach ($sizes as $i => $sz) {
                if ((int)($qtys[$i] ?? 0) > 0) {
                    $size = $sz;
                    break;
                }
            }
            if ($size === '' && !empty($sizes)) {
                $size = $sizes[0];
            }
        }

        // تحديد اللون الافتراضي إذا لم يُحدد
        if ($color === '' && !empty($mp->color_all)) {
            $colors = self::toArray($mp->color_all);
            if (!empty($colors)) {
                $color = ltrim($colors[0], '#');
            }
        }

        // حساب المخزون الفعلي
        $stock = self::getEffectiveStock($mp, $size);

        // فحص المخزون مع دعم Preorder
        $isPreorder = (int)($mp->preordered ?? 0) === 1;
        if ($stock <= 0 && !$isPreorder) {
            return ['success' => false, 'message' => __('Out Of Stock'), 'cart' => null];
        }

        if ($stock > 0 && $qty > $stock && !$isPreorder) {
            return ['success' => false, 'message' => __('Only') . ' ' . $stock . ' ' . __('items available'), 'cart' => null];
        }

        // حساب السعر
        $unitPrice = self::calculateUnitPrice($mp, $size, $color);

        // توليد المفتاح
        $cartKey = self::generateKey($mpId, $size, $color, $values);

        // جلب السلة الحالية
        $cart = self::getCart();

        // إذا كان العنصر موجود، زيادة الكمية
        if (isset($cart['items'][$cartKey])) {
            $existingQty = (int)$cart['items'][$cartKey]['qty'];
            $newQty = $existingQty + $qty;

            // فحص المخزون للكمية الجديدة
            if ($stock > 0 && $newQty > $stock && !$isPreorder) {
                return ['success' => false, 'message' => __('Only') . ' ' . $stock . ' ' . __('items available'), 'cart' => null];
            }

            $cart['items'][$cartKey]['qty'] = $newQty;
            $cart['items'][$cartKey]['total_price'] = $unitPrice * $newQty;
        } else {
            // إنشاء عنصر جديد
            $product = $mp->product;

            $cart['items'][$cartKey] = [
                // معرفات
                'cart_key' => $cartKey,
                'dom_key' => self::generateDomKey($cartKey),
                'merchant_product_id' => $mpId,
                'product_id' => $mp->product_id,
                'user_id' => $mp->user_id,
                'vendor_id' => $mp->user_id, // صريح للاستخدام في route
                'brand_quality_id' => $mp->brand_quality_id,

                // معلومات المنتج
                'name' => $product->name ?? '',
                'name_ar' => $product->name_ar ?? '',
                'slug' => $product->slug ?? '',
                'sku' => $product->sku ?? '',
                'photo' => $product->photo ?? '',
                'type' => $product->type ?? 'Physical',

                // معلومات التاجر
                'vendor_name' => getLocalizedShopName($mp->user),
                'shop_name_ar' => $mp->user->shop_name_ar ?? '',
                'brand_name' => $mp->qualityBrand->brand->name ?? '',
                'quality_name' => $mp->qualityBrand->name ?? '',

                // الأسعار
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $qty,
                'previous_price' => (float)($mp->previous_price ?? 0),

                // الكمية والمخزون
                'qty' => $qty,
                'stock' => $stock,
                'minimum_qty' => $minQty,
                'preordered' => $isPreorder ? 1 : 0,

                // المقاس واللون
                'size' => $size,
                'color' => $color,

                // بيانات المقاس
                'size_qty' => self::getSizeQty($mp, $size),
                'size_price' => self::getSizePrice($mp, $size),

                // بيانات إضافية
                'keys' => implode(',', $keys),
                'values' => $values,

                // معلومات إضافية من التاجر
                'ship' => $mp->ship ?? '',
                'policy' => $mp->policy ?? '',
                'product_condition' => $mp->product_condition ?? '',

                // خصم الجملة
                'discount' => 0,
                'whole_sell_qty' => $mp->whole_sell_qty ?? '',
                'whole_sell_discount' => $mp->whole_sell_discount ?? '',
            ];
        }

        // تطبيق خصم الجملة
        self::applyWholesaleDiscount($cart, $cartKey);

        // حفظ السلة وتحديث الإجماليات
        $cart = self::saveCart($cart);

        return ['success' => true, 'message' => __('Item added to cart successfully'), 'cart' => $cart];
    }

    /**
     * زيادة كمية عنصر في السلة
     *
     * @param string $cartKey
     * @return array
     */
    public static function increaseQty(string $cartKey): array
    {
        $cart = self::getCart();

        if (!isset($cart['items'][$cartKey])) {
            return ['success' => false, 'message' => __('Item not found in cart'), 'item' => null, 'cart' => null];
        }

        $item = $cart['items'][$cartKey];
        $mpId = (int)$item['merchant_product_id'];
        $size = $item['size'] ?? '';

        // جلب بيانات التاجر الحالية
        $mp = MerchantProduct::find($mpId);
        if (!$mp || $mp->status !== 1) {
            return ['success' => false, 'message' => __('Product no longer available'), 'item' => null, 'cart' => null];
        }

        // حساب المخزون الفعلي
        $stock = self::getEffectiveStock($mp, $size);
        $isPreorder = (int)($mp->preordered ?? 0) === 1;

        $currentQty = (int)$item['qty'];
        $newQty = $currentQty + 1;

        // فحص المخزون
        if ($stock > 0 && $newQty > $stock && !$isPreorder) {
            return ['success' => false, 'message' => __('Stock limit reached') . ': ' . $stock, 'item' => $item, 'cart' => $cart];
        }

        // تحديث الكمية والسعر
        $unitPrice = (float)$item['unit_price'];
        $cart['items'][$cartKey]['qty'] = $newQty;
        $cart['items'][$cartKey]['total_price'] = $unitPrice * $newQty;

        // تطبيق خصم الجملة
        self::applyWholesaleDiscount($cart, $cartKey);

        // حفظ السلة وتحديث الإجماليات
        $cart = self::saveCart($cart);

        return ['success' => true, 'message' => __('Quantity increased'), 'item' => $cart['items'][$cartKey], 'cart' => $cart];
    }

    /**
     * إنقاص كمية عنصر في السلة
     *
     * @param string $cartKey
     * @return array
     */
    public static function decreaseQty(string $cartKey): array
    {
        $cart = self::getCart();

        if (!isset($cart['items'][$cartKey])) {
            return ['success' => false, 'message' => __('Item not found in cart'), 'item' => null, 'cart' => null];
        }

        $item = $cart['items'][$cartKey];
        $currentQty = (int)$item['qty'];
        $minQty = (int)($item['minimum_qty'] ?? 1);
        if ($minQty < 1) $minQty = 1;

        // فحص الحد الأدنى
        if ($currentQty <= $minQty) {
            return ['success' => false, 'message' => __('Minimum quantity is') . ' ' . $minQty, 'item' => $item, 'cart' => $cart];
        }

        $newQty = $currentQty - 1;

        // تحديث الكمية والسعر
        $unitPrice = (float)$item['unit_price'];
        $cart['items'][$cartKey]['qty'] = $newQty;
        $cart['items'][$cartKey]['total_price'] = $unitPrice * $newQty;

        // تطبيق خصم الجملة
        self::applyWholesaleDiscount($cart, $cartKey);

        // حفظ السلة وتحديث الإجماليات
        $cart = self::saveCart($cart);

        return ['success' => true, 'message' => __('Quantity decreased'), 'item' => $cart['items'][$cartKey], 'cart' => $cart];
    }

    /**
     * حذف عنصر من السلة
     *
     * @param string $cartKey
     * @return array
     */
    public static function removeItem(string $cartKey): array
    {
        $cart = self::getCart();

        if (!isset($cart['items'][$cartKey])) {
            return ['success' => false, 'message' => __('Item not found in cart'), 'cart' => $cart];
        }

        unset($cart['items'][$cartKey]);

        // حفظ السلة وتحديث الإجماليات
        $cart = self::saveCart($cart);

        // إذا فارغة، حذفها
        if (empty($cart['items'])) {
            Session::forget('cart_v2');
        }

        return ['success' => true, 'message' => __('Item removed from cart'), 'cart' => $cart];
    }

    /**
     * مسح السلة بالكامل
     *
     * @return void
     */
    public static function clearCart(): void
    {
        Session::forget('cart_v2');
    }

    /**
     * تجميع العناصر حسب التاجر
     *
     * @return array
     */
    public static function groupByVendor(): array
    {
        $cart = self::getCart();
        $grouped = [];

        foreach ($cart['items'] as $cartKey => $item) {
            $vendorId = $item['user_id'] ?? 0;

            if (!isset($grouped[$vendorId])) {
                $grouped[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $item['vendor_name'] ?? __('Unknown Vendor'),
                    'items' => [],
                    'total' => 0,
                    'count' => 0
                ];
            }

            $grouped[$vendorId]['items'][$cartKey] = $item;
            $grouped[$vendorId]['total'] += (float)($item['total_price'] ?? 0);
            $grouped[$vendorId]['count'] += (int)($item['qty'] ?? 0);
        }

        return $grouped;
    }

    /**
     * ===================== Helper Methods =====================
     */

    /**
     * تحويل قيمة لمصفوفة
     */
    private static function toArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') return array_map('trim', explode(',', $value));
        return [];
    }

    /**
     * حساب المخزون الفعلي (مع دعم المقاسات)
     */
    public static function getEffectiveStock(MerchantProduct $mp, string $size = ''): int
    {
        if ($size !== '' && !empty($mp->size) && !empty($mp->size_qty)) {
            $sizes = self::toArray($mp->size);
            $qtys = self::toArray($mp->size_qty);
            $idx = array_search(trim($size), array_map('trim', $sizes), true);

            if ($idx !== false && isset($qtys[$idx])) {
                return (int)$qtys[$idx];
            }
        }

        return (int)($mp->stock ?? 0);
    }

    /**
     * جلب كمية المقاس
     */
    private static function getSizeQty(MerchantProduct $mp, string $size): ?int
    {
        if ($size === '' || empty($mp->size) || empty($mp->size_qty)) return null;

        $sizes = self::toArray($mp->size);
        $qtys = self::toArray($mp->size_qty);
        $idx = array_search(trim($size), array_map('trim', $sizes), true);

        if ($idx !== false && isset($qtys[$idx])) {
            return (int)$qtys[$idx];
        }

        return null;
    }

    /**
     * جلب سعر المقاس الإضافي
     */
    private static function getSizePrice(MerchantProduct $mp, string $size): float
    {
        if ($size === '' || empty($mp->size) || empty($mp->size_price)) return 0.0;

        $sizes = self::toArray($mp->size);
        $prices = self::toArray($mp->size_price);
        $idx = array_search(trim($size), array_map('trim', $sizes), true);

        if ($idx !== false && isset($prices[$idx])) {
            return (float)$prices[$idx];
        }

        return 0.0;
    }

    /**
     * حساب سعر الوحدة مع المقاس واللون
     */
    private static function calculateUnitPrice(MerchantProduct $mp, string $size = '', string $color = ''): float
    {
        // السعر الأساسي (مع العمولة)
        $basePrice = method_exists($mp, 'vendorSizePrice') ? $mp->vendorSizePrice() : (float)$mp->price;

        // إضافة سعر المقاس
        $sizePrice = self::getSizePrice($mp, $size);

        // إضافة سعر اللون (إذا موجود)
        $colorPrice = 0.0;
        if ($color !== '' && !empty($mp->color_all) && !empty($mp->color_price)) {
            $colors = self::toArray($mp->color_all);
            $colorPrices = self::toArray($mp->color_price);
            $color = ltrim($color, '#');

            foreach ($colors as $i => $c) {
                if (ltrim($c, '#') === $color && isset($colorPrices[$i])) {
                    $colorPrice = (float)$colorPrices[$i];
                    break;
                }
            }
        }

        return $basePrice + $sizePrice + $colorPrice;
    }

    /**
     * تطبيق خصم الجملة
     */
    private static function applyWholesaleDiscount(array &$cart, string $cartKey): void
    {
        if (!isset($cart['items'][$cartKey])) return;

        $item = &$cart['items'][$cartKey];
        $qty = (int)$item['qty'];
        $unitPrice = (float)$item['unit_price'];

        $wsQty = self::toArray($item['whole_sell_qty'] ?? '');
        $wsDiscount = self::toArray($item['whole_sell_discount'] ?? '');

        if (empty($wsQty) || empty($wsDiscount)) {
            $item['discount'] = 0;
            $item['total_price'] = $unitPrice * $qty;
            return;
        }

        $discount = 0;
        foreach ($wsQty as $i => $threshold) {
            if ($qty >= (int)$threshold && isset($wsDiscount[$i])) {
                $discount = (float)$wsDiscount[$i];
            }
        }

        $item['discount'] = $discount;

        if ($discount > 0) {
            $discountedPrice = $unitPrice * (1 - $discount / 100);
            $item['total_price'] = $discountedPrice * $qty;
        } else {
            $item['total_price'] = $unitPrice * $qty;
        }
    }

    /**
     * التحقق من وجود سلة
     */
    public static function hasCart(): bool
    {
        $cart = self::getCart();
        return !empty($cart['items']);
    }

    /**
     * عدد العناصر في السلة
     */
    public static function getItemCount(): int
    {
        $cart = self::getCart();
        return count($cart['items']);
    }

    /**
     * إجمالي الكميات
     */
    public static function getTotalQty(): int
    {
        $cart = self::getCart();
        return (int)($cart['totalQty'] ?? 0);
    }

    /**
     * إجمالي السعر
     */
    public static function getTotalPrice(): float
    {
        $cart = self::getCart();
        return (float)($cart['totalPrice'] ?? 0);
    }

    /**
     * مزامنة السلة القديمة مع الجديدة (للتوافق)
     */
    public static function syncFromOldCart(): void
    {
        if (!Session::has('cart')) return;

        $oldCart = Session::get('cart');
        if (!$oldCart || empty($oldCart->items)) return;

        foreach ($oldCart->items as $key => $item) {
            $mpId = $item['merchant_product_id'] ?? 0;
            if (!$mpId) continue;

            $qty = (int)($item['qty'] ?? 1);
            $size = $item['size'] ?? '';
            $color = $item['color'] ?? '';
            $values = $item['values'] ?? '';
            $keys = $item['keys'] ? explode(',', $item['keys']) : [];

            self::addItem($mpId, $qty, $size, $color, $values, $keys);
        }
    }
}
