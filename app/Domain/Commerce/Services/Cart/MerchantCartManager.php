<?php

namespace App\Domain\Commerce\Services\Cart;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\Session;

/**
 * MerchantCartManager - Branch-Scoped Cart Management
 *
 * All cart operations are scoped by branch (branch_id).
 * Each item belongs to a specific branch.
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
     * @return array{success: bool, message: string, data?: array}
     */
    public function addItem(
        int $merchantItemId,
        int $qty = 1
    ): array {
        // ═══ التحقق من MerchantItem ═══
        // Brand data comes from catalogItem->fitments
        $merchantItem = MerchantItem::with(['catalogItem.fitments.brand', 'user', 'qualityBrand', 'merchantBranch'])->find($merchantItemId);

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

        // ═══ التحقق من الفرع (Branch) ═══
        if (!$merchantItem->merchant_branch_id || !$merchantItem->merchantBranch) {
            throw new \RuntimeException(
                "MerchantItem {$merchantItemId} has no valid branch assigned"
            );
        }
        $branch = $merchantItem->merchantBranch;

        // ═══ التحقق من الكمية ═══
        $minQty = max(1, (int) ($merchantItem->minimum_qty ?? 1));
        if ($qty < $minQty) {
            return $this->error(__('الحد الأدنى للكمية') . ' ' . $minQty);
        }

        // ═══ التحقق من المخزون ═══
        $stock = $this->getStock($merchantItem);
        $isPreorder = (bool) $merchantItem->preordered;

        if (!$isPreorder && $stock <= 0) {
            return $this->error(__('نفذت الكمية'));
        }

        // ═══ إنشاء مفتاح السلة ═══
        $cartKey = $this->generateKey($merchantItemId);

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
            $this->reservation->update($merchantItemId, $newTotalQty);
        }

        // ═══ بناء بيانات الصنف ═══
        $catalogItem = $merchantItem->catalogItem;
        $unitPrice = (float) $merchantItem->merchantSizePrice();

        if ($unitPrice <= 0) {
            throw new \RuntimeException(
                "MerchantItem {$merchantItemId} has invalid price: {$unitPrice}"
            );
        }

        $effectivePrice = $unitPrice;

        // ═══ استخراج Brand (من fitments) و QualityBrand ═══
        // Brand data comes from catalogItem->fitments
        $fitments = $catalogItem->fitments ?? collect();
        $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
        $firstBrand = $brands->first();
        $qualityBrand = $merchantItem->qualityBrand;

        $itemData = [
            // Identifiers
            'key' => $cartKey,
            'merchant_item_id' => $merchantItemId,
            'merchant_id' => $merchantItem->user_id,
            'branch_id' => $merchantItem->merchant_branch_id,
            'branch_name' => $branch->warehouse_name ?? '',
            'catalog_item_id' => $merchantItem->catalog_item_id,

            // Product snapshot
            'name' => $catalogItem->name,
            'name_ar' => $catalogItem->label_ar ?: $catalogItem->name,
            'photo' => $catalogItem->photo ?: '',
            'slug' => $catalogItem->slug ?: '',
            'part_number' => $catalogItem->part_number ?: '',

            // Brand (OEM brand - from fitments) - first brand for compatibility
            'brand_id' => $firstBrand?->id,
            'brand_name' => $firstBrand?->name ?: '',
            'brand_name_ar' => $firstBrand?->name_ar ?: '',
            'brand_logo' => $firstBrand?->photo_url ?: '',

            // All fitment brands (for fitment button display)
            'fitment_brands' => $brands->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->localized_name ?? $b->name,
                'logo' => $b->photo_url,
            ])->values()->toArray(),
            'fitment_count' => $brands->count(),

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
            'effective_price' => $effectivePrice,

            // Quantity
            'qty' => $newTotalQty,
            'min_qty' => $minQty,
            'stock' => $stock,
            'preordered' => $isPreorder,

            // Wholesale
            'whole_sell_qty' => $this->parseToArray($merchantItem->whole_sell_qty),
            'whole_sell_discount' => $this->parseToArray($merchantItem->whole_sell_discount),

            // Shipping (from CatalogItem ONLY)
            // Weight is REQUIRED for shipping. If weight=0, shipping calculation will FAIL.
            'weight' => (float) ($catalogItem->weight ?? 0),

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
            $this->getBranchCart($merchantItem->merchant_branch_id)
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Branch-Scoped Methods
    // ══════════════════════════════════════════════════════════════

    /**
     * Get list of branch IDs in cart
     */
    public function getBranchIds(): array
    {
        $cart = $this->getStorage();
        $ids = [];

        foreach ($cart['items'] as $item) {
            $branchId = (int) ($item['branch_id'] ?? 0);

            if ($branchId <= 0) {
                throw new \RuntimeException(
                    "Cart item has invalid branch_id: {$branchId}"
                );
            }

            if (!in_array($branchId, $ids)) {
                $ids[] = $branchId;
            }
        }

        return $ids;
    }

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
     * Get cart data for a specific branch
     */
    public function getBranchCart(int $branchId): array
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();
        $branchItems = [];

        foreach ($cart['items'] as $key => $item) {
            if ((int) ($item['branch_id'] ?? 0) === $branchId) {
                $branchItems[$key] = $item;
            }
        }

        $totals = $this->calculateTotals($branchItems);

        $branchName = '';
        $merchantId = 0;
        $merchantName = '';
        if (!empty($branchItems)) {
            $first = reset($branchItems);
            $branchName = $first['branch_name'] ?? '';
            $merchantId = (int) ($first['merchant_id'] ?? 0);
            $merchantName = $first['merchant_name'] ?? '';
        }

        $hasOthers = false;
        foreach ($cart['items'] as $item) {
            if ((int) ($item['branch_id'] ?? 0) !== $branchId) {
                $hasOthers = true;
                break;
            }
        }

        return [
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'merchant_id' => $merchantId,
            'merchant_name' => $merchantName,
            'items' => $branchItems,
            'totals' => $totals,
            'has_other_branches' => $hasOthers,
        ];
    }

    /**
     * Get items for a specific branch
     */
    public function getBranchItems(int $branchId): array
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();
        $items = [];

        foreach ($cart['items'] as $key => $item) {
            if ((int) ($item['branch_id'] ?? 0) === $branchId) {
                $items[$key] = $item;
            }
        }

        return $items;
    }

    /**
     * Check if branch has items in cart
     */
    public function hasBranchItems(int $branchId): bool
    {
        return !empty($this->getBranchItems($branchId));
    }

    /**
     * Get totals for a specific branch
     */
    public function getBranchTotals(int $branchId): array
    {
        $items = $this->getBranchItems($branchId);
        return $this->calculateTotals($items);
    }

    /**
     * Get branch cart summary (for checkout pages)
     */
    public function getBranchCartSummary(int $branchId): array
    {
        $this->validateBranchId($branchId);

        $items = $this->getBranchItems($branchId);
        $totals = $this->calculateTotals($items);

        return [
            'items_count' => count($items),
            'total_qty' => $totals['qty'],
            'total_price' => $totals['total'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'items' => $items,
        ];
    }

    /**
     * Build cart payload for purchase creation
     */
    public function buildBranchCartPayload(int $branchId): array
    {
        $this->validateBranchId($branchId);

        $items = $this->getBranchItems($branchId);
        $totals = $this->calculateTotals($items);

        $purchaseItems = [];
        foreach ($items as $key => $item) {
            $purchaseItems[$key] = [
                'item' => [
                    'id' => $item['catalog_item_id'],
                    'user_id' => $item['merchant_id'],
                    'branch_id' => $item['branch_id'] ?? null,
                    'branch_name' => $item['branch_name'] ?? '',
                    'name' => $item['name'],
                    'name_ar' => $item['name_ar'] ?? $item['name'],
                    'photo' => $item['photo'],
                    'slug' => $item['slug'],
                    'part_number' => $item['part_number'] ?? '',
                ],
                'merchant_item_id' => $item['merchant_item_id'],
                'user_id' => $item['merchant_id'],
                'branch_id' => $item['branch_id'] ?? null,
                'branch_name' => $item['branch_name'] ?? '',
                'qty' => $item['qty'],
                'price' => $item['total_price'],
                'stock' => $item['stock'],
                'keys' => null,
                'values' => null,
            ];
        }

        return [
            'totalQty' => $totals['qty'],
            'totalPrice' => $totals['total'],
            'items' => $purchaseItems,
        ];
    }

    /**
     * Get summary for all branches (for cart page display)
     */
    public function getAllBranchesCart(): array
    {
        $branchIds = $this->getBranchIds();
        $result = [];

        foreach ($branchIds as $branchId) {
            $branchCart = $this->getBranchCart($branchId);
            $branchCart['checkout_url'] = route('branch.checkout.address', ['branchId' => $branchId]);
            $result[$branchId] = $branchCart;
        }

        return $result;
    }

    /**
     * Update item quantity (branch-scoped)
     */
    public function updateBranchQty(int $branchId, string $cartKey, int $qty): array
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود في السلة'));
        }

        if ((int) ($item['branch_id'] ?? 0) !== $branchId) {
            throw new \RuntimeException(
                "Cart item '{$cartKey}' belongs to branch {$item['branch_id']}, not {$branchId}"
            );
        }

        $minQty = (int) $item['min_qty'];
        if ($qty < $minQty) {
            return $this->error(__('الحد الأدنى للكمية') . ' ' . $minQty);
        }

        $merchantItem = MerchantItem::find($item['merchant_item_id']);
        if (!$merchantItem || $merchantItem->status !== 1) {
            unset($cart['items'][$cartKey]);
            $this->saveStorage($cart);
            return $this->error(__('الصنف لم يعد متاحاً'));
        }

        $stock = $this->getStock($merchantItem);
        $isPreorder = (bool) $item['preordered'];

        if (!$isPreorder && $stock > 0 && $qty > $stock) {
            return $this->error(__('المتاح فقط') . ' ' . $stock);
        }

        if (!$isPreorder && $stock > 0) {
            $this->reservation->update($item['merchant_item_id'], $qty);
        }

        $cart['items'][$cartKey]['qty'] = $qty;
        $cart['items'][$cartKey]['total_price'] = $this->calculateItemTotal($cart['items'][$cartKey]);
        $this->saveStorage($cart);

        return $this->success(__('تم التحديث'), $this->getBranchCart($branchId));
    }

    /**
     * Remove item from cart (branch-scoped)
     */
    public function removeBranchItem(int $branchId, string $cartKey): array
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();
        $item = $cart['items'][$cartKey] ?? null;

        if (!$item) {
            return $this->error(__('الصنف غير موجود'));
        }

        if ((int) ($item['branch_id'] ?? 0) !== $branchId) {
            throw new \RuntimeException(
                "Cart item '{$cartKey}' belongs to branch {$item['branch_id']}, not {$branchId}"
            );
        }

        $this->reservation->release($item['merchant_item_id']);

        unset($cart['items'][$cartKey]);
        $this->saveStorage($cart);

        return $this->success(__('تم الحذف'), $this->getBranchCart($branchId));
    }

    /**
     * Clear items for a specific branch (after successful checkout)
     */
    public function clearBranch(int $branchId): void
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();

        foreach ($cart['items'] as $key => $item) {
            if ((int) ($item['branch_id'] ?? 0) === $branchId) {
                $this->reservation->release($item['merchant_item_id']);
                unset($cart['items'][$key]);
            }
        }

        $this->saveStorage($cart);
    }

    /**
     * Check if there are other branches after checkout
     */
    public function hasOtherBranches(int $branchId): bool
    {
        $this->validateBranchId($branchId);

        $cart = $this->getStorage();

        foreach ($cart['items'] as $item) {
            if ((int) ($item['branch_id'] ?? 0) !== $branchId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Confirm checkout and deduct stock (branch-scoped)
     */
    public function confirmBranchCheckout(int $branchId): bool
    {
        $this->validateBranchId($branchId);

        $items = $this->getBranchItems($branchId);

        if (empty($items)) {
            return false;
        }

        $confirmData = [];
        foreach ($items as $item) {
            $confirmData[$item['merchant_item_id']] = [
                'qty' => $item['qty'],
            ];
        }

        if (!$this->reservation->confirm($confirmData)) {
            return false;
        }

        $this->clearBranch($branchId);

        return true;
    }

    /**
     * Get data for checkout (branch-scoped)
     */
    public function getForBranchCheckout(int $branchId): array
    {
        $this->validateBranchId($branchId);

        $branchCart = $this->getBranchCart($branchId);

        if (empty($branchCart['items'])) {
            throw new \RuntimeException(
                "No items in cart for branch {$branchId}"
            );
        }

        return [
            'branch_id' => $branchId,
            'branch_name' => $branchCart['branch_name'],
            'merchant_id' => $branchCart['merchant_id'],
            'merchant_name' => $branchCart['merchant_name'],
            'items' => $branchCart['items'],
            'totals' => $branchCart['totals'],
            'has_other_branches' => $branchCart['has_other_branches'],
            'cart_payload' => [
                'totalQty' => $branchCart['totals']['qty'],
                'totalPrice' => $branchCart['totals']['total'],
                'items' => $branchCart['items'],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Storage Methods
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
     * Clear all cart
     */
    public function clearAll(): void
    {
        $this->reservation->releaseAll();
        Session::forget(self::SESSION_KEY);
    }

    // ══════════════════════════════════════════════════════════════
    // Calculation Methods
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
    // Helper Methods
    // ══════════════════════════════════════════════════════════════

    private function validateBranchId(int $branchId): void
    {
        if ($branchId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid branchId: {$branchId}. Must be > 0."
            );
        }
    }

    private function generateKey(int $merchantItemId): string
    {
        $sessionHash = substr(md5(session()->getId()), 0, 8);
        return "s{$sessionHash}_m{$merchantItemId}";
    }

    private function getStock(MerchantItem $mp): int
    {
        return (int) ($mp->stock ?? 0);
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
    // Header Display
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
