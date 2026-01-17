<?php

namespace App\Services\Cart;

use App\Models\MerchantItem;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * MerchantCartManager - المصدر الوحيد للحقيقة
 *
 * ❌ لا يوجد Cart عام
 * ❌ لا Items بدون merchant
 * ❌ لا منطق مشترك بين التجار
 * ✅ كل عملية تتطلب merchantId
 * ✅ Fail-Fast فقط
 */
class MerchantCartManager
{
    private const SESSION_KEY = 'merchant_cart';

    private StockReservation $reservation;

    public function __construct(StockReservation $reservation)
    {
        $this->reservation = $reservation;
    }

    // ══════════════════════════════════════════════════════════════
    // إضافة صنف
    // ══════════════════════════════════════════════════════════════

    /**
     * Add item to cart
     *
     * @param int $merchantItemId
     * @param int $qty
     * @param string|null $size
     * @param string|null $color
     * @return array{success: bool, message: string, data?: array}
     */
    public function addItem(
        int $merchantItemId,
        int $qty = 1,
        ?string $size = null,
        ?string $color = null
    ): array {
        // ═══ التحقق من MerchantItem ═══
        $merchantItem = MerchantItem::with(['catalogItem.brand', 'user', 'qualityBrand'])->find($merchantItemId);

        if (!$merchantItem) {
            return $this->error(__('الصنف غير موجود'));
        }

        if (!$merchantItem->user_id || $merchantItem->user_id <= 0) {
            throw new \RuntimeException(
                "MerchantItem {$merchantItemId} has invalid user_id: {$merchantItem->user_id}"
            );
        }

        if ($merchantItem->status !== 1) {
            return $this->error(__('الصنف غير متاح'));
        }

        // ═══ التحقق من التاجر ═══
        $merchant = $merchantItem->user;
        if (!$merchant || $merchant->is_merchant !== 2) {
            return $this->error(__('التاجر غير نشط'));
        }

        // ═══ التحقق من CatalogItem ═══
        if (!$merchantItem->catalogItem) {
            throw new \RuntimeException(
                "MerchantItem {$merchantItemId} has no CatalogItem"
            );
        }

        // ═══ التحقق من الكمية ═══
        $minQty = max(1, (int) ($merchantItem->minimum_qty ?? 1));
        if ($qty < $minQty) {
            return $this->error(__('الحد الأدنى للكمية') . ' ' . $minQty);
        }

        // ═══ تحديد المقاس واللون ═══
        if ($size === null && !empty($merchantItem->size)) {
            $size = $this->getFirstAvailableSize($merchantItem);
        }

        if ($color === null && !empty($merchantItem->color_all)) {
            $colors = $this->parseToArray($merchantItem->color_all);
            $color = !empty($colors) ? ltrim($colors[0], '#') : null;
        }

        // ═══ التحقق من المخزون ═══
        $stock = $this->getStock($merchantItem, $size);
        $isPreorder = (bool) $merchantItem->preordered;

        if (!$isPreorder && $stock <= 0) {
            return $this->error(__('نفذت الكمية'));
        }

        // ═══ إنشاء مفتاح السلة ═══
        $cartKey = $this->generateKey($merchantItemId, $size, $color);

        // ═══ التحقق من الكمية الموجودة ═══
        $cart = $this->getStorage();
        $existingQty = isset($cart['items'][$cartKey]) ? (int) $cart['items'][$cartKey]['qty'] : 0;
        $newTotalQty = $existingQty + $qty;

        if (!$isPreorder && $stock > 0 && $newTotalQty > $stock) {
            $available = $stock - $existingQty;
            if ($available <= 0) {
                return $this->error(__('وصلت للحد الأقصى'));
            }
            return $this->error(__('المتاح فقط') . ' ' . $available);
        }

        // ═══ حجز المخزون ═══
        if (!$isPreorder && $stock > 0) {
            $this->reservation->update($merchantItemId, $newTotalQty, $size);
        }

        // ═══ بناء بيانات الصنف ═══
        $catalogItem = $merchantItem->catalogItem;
        $unitPrice = (float) $merchantItem->merchantSizePrice();

        if ($unitPrice <= 0) {
            throw new \RuntimeException(
                "MerchantItem {$merchantItemId} has invalid price: {$unitPrice}"
            );
        }

        $sizePrice = $this->calculateSizePrice($merchantItem, $size);
        $colorPrice = $this->calculateColorPrice($merchantItem, $color);
        $effectivePrice = $unitPrice + $sizePrice + $colorPrice;

        // ═══ استخراج Brand و QualityBrand ═══
        $brand = $catalogItem->brand;
        $qualityBrand = $merchantItem->qualityBrand;

        $itemData = [
            // Identifiers
            'key' => $cartKey,
            'merchant_item_id' => $merchantItemId,
            'merchant_id' => $merchantItem->user_id,
            'catalog_item_id' => $merchantItem->catalog_item_id,

            // Product snapshot
            'name' => $catalogItem->name,
            'name_ar' => $catalogItem->label_ar ?: $catalogItem->name,
            'photo' => $catalogItem->photo ?: '',
            'slug' => $catalogItem->slug ?: '',
            'part_number' => $catalogItem->part_number ?: '',

            // Brand (OEM brand - نيسان، تويوتا...)
            'brand_id' => $brand?->id,
            'brand_name' => $brand?->name ?: '',
            'brand_name_ar' => $brand?->name_ar ?: '',
            'brand_logo' => $brand?->photo_url ?: '',

            // Quality Brand (أصلي، بديل...)
            'quality_brand_id' => $qualityBrand?->id,
            'quality_brand_name' => $qualityBrand?->name_en ?: '',
            'quality_brand_name_ar' => $qualityBrand?->name_ar ?: '',
            'quality_brand_logo' => $qualityBrand?->logo_url ?: '',

            // Merchant info
            'merchant_name' => getLocalizedShopName($merchant),
            'merchant_name_ar' => $merchant->shop_name_ar ?: '',

            // Pricing
            'unit_price' => $unitPrice,
            'size_price' => $sizePrice,
            'color_price' => $colorPrice,
            'effective_price' => $effectivePrice,

            // Quantity
            'qty' => $newTotalQty,
            'min_qty' => $minQty,
            'stock' => $stock,
            'preordered' => $isPreorder,

            // Variants
            'size' => $size,
            'color' => $color ? ltrim($color, '#') : null,

            // Wholesale
            'whole_sell_qty' => $this->parseToArray($merchantItem->whole_sell_qty),
            'whole_sell_discount' => $this->parseToArray($merchantItem->whole_sell_discount),

            // Timestamp
            'added_at' => now()->toDateTimeString(),
        ];

        // ═══ حساب السعر الإجمالي ═══
        $itemData['total_price'] = $this->calculateItemTotal($itemData);

        // ═══ حفظ في السلة ═══
        $cart['items'][$cartKey] = $itemData;
        $this->saveStorage($cart);

        return $this->success(
            __('تمت الإضافة للسلة'),
            $this->getMerchantCart($merchantItem->user_id)
        );
    }

    // ══════════════════════════════════════════════════════════════
    // تعديل الكمية
    // ══════════════════════════════════════════════════════════════

    /**
     * Update item quantity
     */
    public function updateQty(int $merchantId, string $cartKey, int $qty): array
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود في السلة'));
        }

        // ═══ التحقق من ملكية التاجر ═══
        if ((int) $item['merchant_id'] !== $merchantId) {
            throw new \RuntimeException(
                "Cart item '{$cartKey}' belongs to merchant {$item['merchant_id']}, not {$merchantId}"
            );
        }

        // ═══ التحقق من الحد الأدنى ═══
        $minQty = (int) $item['min_qty'];
        if ($qty < $minQty) {
            return $this->error(__('الحد الأدنى للكمية') . ' ' . $minQty);
        }

        // ═══ التحقق من المخزون ═══
        $merchantItem = MerchantItem::find($item['merchant_item_id']);
        if (!$merchantItem || $merchantItem->status !== 1) {
            unset($cart['items'][$cartKey]);
            $this->saveStorage($cart);
            return $this->error(__('الصنف لم يعد متاحاً'));
        }

        $stock = $this->getStock($merchantItem, $item['size'] ?? null);
        $isPreorder = (bool) $item['preordered'];

        if (!$isPreorder && $stock > 0 && $qty > $stock) {
            return $this->error(__('المتاح فقط') . ' ' . $stock);
        }

        // ═══ تحديث الحجز ═══
        if (!$isPreorder && $stock > 0) {
            $this->reservation->update($item['merchant_item_id'], $qty, $item['size'] ?? null);
        }

        // ═══ تحديث الكمية ═══
        $cart['items'][$cartKey]['qty'] = $qty;
        $cart['items'][$cartKey]['total_price'] = $this->calculateItemTotal($cart['items'][$cartKey]);
        $this->saveStorage($cart);

        return $this->success(__('تم التحديث'), $this->getMerchantCart($merchantId));
    }

    /**
     * Increase quantity by 1
     */
    public function increaseQty(int $merchantId, string $cartKey): array
    {
        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود'));
        }

        return $this->updateQty($merchantId, $cartKey, (int) $item['qty'] + 1);
    }

    /**
     * Decrease quantity by 1
     */
    public function decreaseQty(int $merchantId, string $cartKey): array
    {
        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود'));
        }

        return $this->updateQty($merchantId, $cartKey, (int) $item['qty'] - 1);
    }

    // ══════════════════════════════════════════════════════════════
    // حذف صنف
    // ══════════════════════════════════════════════════════════════

    /**
     * Remove item from cart
     */
    public function removeItem(int $merchantId, string $cartKey): array
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود'));
        }

        // ═══ التحقق من ملكية التاجر ═══
        if ((int) $item['merchant_id'] !== $merchantId) {
            throw new \RuntimeException(
                "Cart item '{$cartKey}' belongs to merchant {$item['merchant_id']}, not {$merchantId}"
            );
        }

        // ═══ تحرير الحجز ═══
        $this->reservation->release($item['merchant_item_id'], $item['size'] ?? null);

        // ═══ حذف من السلة ═══
        unset($cart['items'][$cartKey]);
        $this->saveStorage($cart);

        return $this->success(__('تم الحذف'), $this->getMerchantCart($merchantId));
    }

    // ══════════════════════════════════════════════════════════════
    // قراءة بيانات تاجر محدد
    // ══════════════════════════════════════════════════════════════

    /**
     * Get cart data for a specific merchant
     *
     * @param int $merchantId
     * @return array{
     *   merchant_id: int,
     *   merchant_name: string,
     *   items: array,
     *   totals: array{qty: int, subtotal: float, discount: float, total: float},
     *   has_other_merchants: bool
     * }
     */
    public function getMerchantCart(int $merchantId): array
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();
        $merchantItems = [];

        foreach ($cart['items'] as $key => $item) {
            if ((int) $item['merchant_id'] === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        // ═══ حساب الإجماليات ═══
        $totals = $this->calculateTotals($merchantItems);

        // ═══ معلومات التاجر ═══
        $merchantName = '';
        if (!empty($merchantItems)) {
            $first = reset($merchantItems);
            $merchantName = $first['merchant_name'] ?? '';
        }

        // ═══ هل يوجد تجار آخرين؟ ═══
        $hasOthers = false;
        foreach ($cart['items'] as $item) {
            if ((int) $item['merchant_id'] !== $merchantId) {
                $hasOthers = true;
                break;
            }
        }

        return [
            'merchant_id' => $merchantId,
            'merchant_name' => $merchantName,
            'items' => $merchantItems,
            'totals' => $totals,
            'has_other_merchants' => $hasOthers,
        ];
    }

    /**
     * Get items for a specific merchant (simple array)
     */
    public function getMerchantItems(int $merchantId): array
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();
        $items = [];

        foreach ($cart['items'] as $key => $item) {
            if ((int) $item['merchant_id'] === $merchantId) {
                $items[$key] = $item;
            }
        }

        return $items;
    }

    /**
     * Get totals for a specific merchant
     */
    public function getMerchantTotals(int $merchantId): array
    {
        $items = $this->getMerchantItems($merchantId);
        return $this->calculateTotals($items);
    }

    // ══════════════════════════════════════════════════════════════
    // قائمة التجار في السلة
    // ══════════════════════════════════════════════════════════════

    /**
     * Get list of merchant IDs in cart
     */
    public function getMerchantIds(): array
    {
        $cart = $this->getStorage();
        $ids = [];

        foreach ($cart['items'] as $item) {
            $merchantId = (int) $item['merchant_id'];

            if ($merchantId <= 0) {
                throw new \RuntimeException(
                    "Cart item has invalid merchant_id: {$merchantId}"
                );
            }

            if (!in_array($merchantId, $ids)) {
                $ids[] = $merchantId;
            }
        }

        return $ids;
    }

    /**
     * Get summary for all merchants (for cart page display)
     *
     * @return array<int, array{
     *   merchant_id: int,
     *   merchant_name: string,
     *   items: array,
     *   totals: array,
     *   checkout_url: string
     * }>
     */
    public function getAllMerchantsCart(): array
    {
        $merchantIds = $this->getMerchantIds();
        $result = [];

        foreach ($merchantIds as $merchantId) {
            $merchantCart = $this->getMerchantCart($merchantId);
            $merchantCart['checkout_url'] = route('merchant.checkout.address', ['merchantId' => $merchantId]);
            $result[$merchantId] = $merchantCart;
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════════
    // Checkout
    // ══════════════════════════════════════════════════════════════

    /**
     * Get data for checkout (specific merchant only)
     */
    public function getForCheckout(int $merchantId): array
    {
        $this->validateMerchantId($merchantId);

        $merchantCart = $this->getMerchantCart($merchantId);

        if (empty($merchantCart['items'])) {
            throw new \RuntimeException(
                "No items in cart for merchant {$merchantId}"
            );
        }

        return [
            'merchant_id' => $merchantId,
            'merchant_name' => $merchantCart['merchant_name'],
            'items' => $merchantCart['items'],
            'totals' => $merchantCart['totals'],
            'has_other_merchants' => $merchantCart['has_other_merchants'],
            'cart_payload' => [
                'totalQty' => $merchantCart['totals']['qty'],
                'totalPrice' => $merchantCart['totals']['total'],
                'items' => $merchantCart['items'],
            ],
        ];
    }

    /**
     * Check if there are other merchants after checkout
     */
    public function hasOtherMerchants(int $merchantId): bool
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();

        foreach ($cart['items'] as $item) {
            if ((int) $item['merchant_id'] !== $merchantId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear items for a specific merchant (after successful checkout)
     */
    public function clearMerchant(int $merchantId): void
    {
        $this->validateMerchantId($merchantId);

        $cart = $this->getStorage();

        foreach ($cart['items'] as $key => $item) {
            if ((int) $item['merchant_id'] === $merchantId) {
                // تحرير الحجز
                $this->reservation->release($item['merchant_item_id'], $item['size'] ?? null);
                unset($cart['items'][$key]);
            }
        }

        $this->saveStorage($cart);
    }

    /**
     * Confirm checkout and deduct stock
     */
    public function confirmCheckout(int $merchantId): bool
    {
        $this->validateMerchantId($merchantId);

        $items = $this->getMerchantItems($merchantId);

        if (empty($items)) {
            return false;
        }

        // تأكيد خصم المخزون
        $confirmData = [];
        foreach ($items as $item) {
            $confirmData[$item['merchant_item_id']] = [
                'qty' => $item['qty'],
                'size' => $item['size'] ?? null,
            ];
        }

        if (!$this->reservation->confirm($confirmData)) {
            return false;
        }

        // مسح أصناف التاجر من السلة
        $this->clearMerchant($merchantId);

        return true;
    }

    // ══════════════════════════════════════════════════════════════
    // مساعدات التخزين (خاص - لا يُستخدم من الخارج)
    // ══════════════════════════════════════════════════════════════

    private function getStorage(): array
    {
        return Session::get(self::SESSION_KEY, ['items' => []]);
    }

    private function saveStorage(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Clear all cart (admin/testing only)
     */
    public function clearAll(): void
    {
        $this->reservation->releaseAll();
        Session::forget(self::SESSION_KEY);
    }

    // ══════════════════════════════════════════════════════════════
    // دوال حساب خاصة
    // ══════════════════════════════════════════════════════════════

    private function calculateTotals(array $items): array
    {
        $totals = [
            'qty' => 0,
            'subtotal' => 0.0,
            'discount' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as $item) {
            $totals['qty'] += (int) $item['qty'];
            $totals['subtotal'] += (float) $item['effective_price'] * (int) $item['qty'];
            $totals['total'] += (float) $item['total_price'];
        }

        $totals['discount'] = $totals['subtotal'] - $totals['total'];

        return $totals;
    }

    private function calculateItemTotal(array $item): float
    {
        $effectivePrice = (float) $item['effective_price'];
        $qty = (int) $item['qty'];

        // حساب خصم الجملة
        $discountPercent = $this->getWholesaleDiscount($item);

        if ($discountPercent > 0) {
            $effectivePrice = $effectivePrice * (1 - $discountPercent / 100);
        }

        return round($effectivePrice * $qty, 2);
    }

    private function getWholesaleDiscount(array $item): float
    {
        $wholeSellQty = $item['whole_sell_qty'] ?? [];
        $wholeSellDiscount = $item['whole_sell_discount'] ?? [];
        $qty = (int) $item['qty'];

        if (empty($wholeSellQty) || empty($wholeSellDiscount)) {
            return 0.0;
        }

        $discount = 0.0;
        foreach ($wholeSellQty as $i => $threshold) {
            if ($qty >= (int) $threshold && isset($wholeSellDiscount[$i])) {
                $discount = (float) $wholeSellDiscount[$i];
            }
        }

        return $discount;
    }

    // ══════════════════════════════════════════════════════════════
    // دوال مساعدة
    // ══════════════════════════════════════════════════════════════

    private function validateMerchantId(int $merchantId): void
    {
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid merchantId: {$merchantId}. Must be > 0."
            );
        }
    }

    private function generateKey(int $merchantItemId, ?string $size, ?string $color): string
    {
        $sessionHash = substr(md5(session()->getId()), 0, 8);
        $sizeKey = $size ? preg_replace('/[^a-zA-Z0-9]/', '', $size) : '_';
        $colorKey = $color ? ltrim($color, '#') : '_';

        return "s{$sessionHash}_m{$merchantItemId}_{$sizeKey}_{$colorKey}";
    }

    private function getStock(MerchantItem $mp, ?string $size): int
    {
        if ($size && !empty($mp->size) && !empty($mp->size_qty)) {
            $sizes = $this->parseToArray($mp->size);
            $qtys = $this->parseToArray($mp->size_qty);
            $idx = array_search(trim($size), array_map('trim', $sizes), true);

            if ($idx !== false && isset($qtys[$idx])) {
                return (int) $qtys[$idx];
            }
        }

        return (int) ($mp->stock ?? 0);
    }

    private function getFirstAvailableSize(MerchantItem $mp): ?string
    {
        $sizes = $this->parseToArray($mp->size);
        $qtys = $this->parseToArray($mp->size_qty);

        foreach ($sizes as $i => $size) {
            if ((int) ($qtys[$i] ?? 0) > 0) {
                return trim($size);
            }
        }

        return !empty($sizes) ? trim($sizes[0]) : null;
    }

    private function calculateSizePrice(MerchantItem $mp, ?string $size): float
    {
        if (!$size || empty($mp->size) || empty($mp->size_price)) {
            return 0.0;
        }

        $sizes = $this->parseToArray($mp->size);
        $prices = $this->parseToArray($mp->size_price);
        $idx = array_search(trim($size), array_map('trim', $sizes), true);

        if ($idx !== false && isset($prices[$idx])) {
            return (float) $prices[$idx];
        }

        return 0.0;
    }

    private function calculateColorPrice(MerchantItem $mp, ?string $color): float
    {
        if (!$color || empty($mp->color_all) || empty($mp->color_price)) {
            return 0.0;
        }

        $colors = $this->parseToArray($mp->color_all);
        $prices = $this->parseToArray($mp->color_price);
        $color = ltrim($color, '#');

        foreach ($colors as $i => $c) {
            if (ltrim($c, '#') === $color && isset($prices[$i])) {
                return (float) $prices[$i];
            }
        }

        return 0.0;
    }

    private function parseToArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') return array_map('trim', explode(',', $value));
        return [];
    }

    private function success(string $message, array $data = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    private function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // للعرض في الهيدر (عدد الأصناف فقط)
    // ══════════════════════════════════════════════════════════════

    /**
     * Get total item count for header display
     */
    public function getHeaderCount(): int
    {
        $cart = $this->getStorage();
        return count($cart['items']);
    }

    /**
     * Check if cart has any items
     */
    public function hasItems(): bool
    {
        $cart = $this->getStorage();
        return !empty($cart['items']);
    }
}
