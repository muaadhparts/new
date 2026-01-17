<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * MerchantCartController - Cart API
 *
 * كل العمليات Merchant-Scoped
 * لا يوجد عمليات عامة على السلة بالكامل
 */
class MerchantCartController extends Controller
{
    private MerchantCartManager $cart;

    public function __construct(MerchantCartManager $cart)
    {
        $this->cart = $cart;
    }

    // ══════════════════════════════════════════════════════════════
    // صفحة السلة
    // ══════════════════════════════════════════════════════════════

    /**
     * Cart page - shows all merchants grouped
     * GET /merchant-cart
     *
     * Each merchant has their own section with:
     * - Items list
     * - Per-merchant totals
     * - Per-merchant checkout button
     */
    public function index(): View
    {
        $byMerchant = $this->cart->getAllMerchantsCart();

        return view('merchant.cart.index', [
            'byMerchant' => $byMerchant,
            'isEmpty' => !$this->cart->hasItems(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // إضافة صنف
    // ══════════════════════════════════════════════════════════════

    /**
     * Add item to cart
     * POST /merchant-cart/add
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_item_id' => 'required|integer|exists:merchant_items,id',
            'qty' => 'nullable|integer|min:1',
            'size' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
        ]);

        $result = $this->cart->addItem(
            merchantItemId: (int) $request->merchant_item_id,
            qty: (int) ($request->qty ?? 1),
            size: $request->size,
            color: $request->color
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    // ══════════════════════════════════════════════════════════════
    // تعديل الكمية
    // ══════════════════════════════════════════════════════════════

    /**
     * Update item quantity
     * POST /merchant-cart/update
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
            'key' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $result = $this->cart->updateQty(
            merchantId: (int) $request->merchant_id,
            cartKey: $request->key,
            qty: (int) $request->qty
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Increase item quantity by 1
     * POST /merchant-cart/increase
     */
    public function increase(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
            'key' => 'required|string',
        ]);

        $result = $this->cart->increaseQty(
            merchantId: (int) $request->merchant_id,
            cartKey: $request->key
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Decrease item quantity by 1
     * POST /merchant-cart/decrease
     */
    public function decrease(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
            'key' => 'required|string',
        ]);

        $result = $this->cart->decreaseQty(
            merchantId: (int) $request->merchant_id,
            cartKey: $request->key
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    // ══════════════════════════════════════════════════════════════
    // حذف
    // ══════════════════════════════════════════════════════════════

    /**
     * Remove item from cart
     * DELETE /merchant-cart/remove/{key}
     * POST /merchant-cart/remove
     */
    public function remove(Request $request, ?string $key = null): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
        ]);

        $cartKey = $key ?? $request->input('key');

        if (!$cartKey) {
            return response()->json([
                'success' => false,
                'message' => __('مفتاح الصنف مطلوب'),
            ], 422);
        }

        $result = $this->cart->removeItem(
            merchantId: (int) $request->merchant_id,
            cartKey: urldecode($cartKey)
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Clear merchant items
     * POST /merchant-cart/clear-merchant
     */
    public function clearMerchant(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
        ]);

        $this->cart->clearMerchant((int) $request->merchant_id);

        return response()->json([
            'success' => true,
            'message' => __('تم مسح أصناف التاجر'),
            'header_count' => $this->cart->getHeaderCount(),
        ]);
    }

    /**
     * Clear all cart
     * POST /merchant-cart/clear
     */
    public function clear(): JsonResponse
    {
        $this->cart->clearAll();

        return response()->json([
            'success' => true,
            'message' => __('تم مسح السلة'),
            'header_count' => 0,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // قراءة البيانات
    // ══════════════════════════════════════════════════════════════

    /**
     * Get merchant cart summary (AJAX)
     * GET /merchant-cart/summary?merchant_id=X
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer|min:1',
        ]);

        $merchantId = (int) $request->merchant_id;
        $merchantCart = $this->cart->getMerchantCart($merchantId);

        return response()->json([
            'success' => true,
            'merchant_id' => $merchantId,
            'merchant_name' => $merchantCart['merchant_name'],
            'items' => array_values($merchantCart['items']),
            'totals' => $this->formatTotals($merchantCart['totals']),
            'has_other_merchants' => $merchantCart['has_other_merchants'],
        ]);
    }

    /**
     * Get all merchants cart (for full page)
     * GET /merchant-cart/all
     */
    public function all(): JsonResponse
    {
        $merchantsCart = $this->cart->getAllMerchantsCart();

        return response()->json([
            'success' => true,
            'merchants' => $merchantsCart,
            'header_count' => $this->cart->getHeaderCount(),
        ]);
    }

    /**
     * Get cart count (for header badge)
     * GET /merchant-cart/count
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->cart->getHeaderCount(),
            'has_items' => $this->cart->hasItems(),
        ]);
    }

    /**
     * Get merchant IDs in cart
     * GET /merchant-cart/merchants
     */
    public function merchants(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'merchant_ids' => $this->cart->getMerchantIds(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // مساعدات
    // ══════════════════════════════════════════════════════════════

    /**
     * Format totals for JSON response
     */
    private function formatTotals(array $totals): array
    {
        return [
            'qty' => (int) ($totals['qty'] ?? 0),
            'subtotal' => (float) ($totals['subtotal'] ?? 0),
            'discount' => (float) ($totals['discount'] ?? 0),
            'total' => (float) ($totals['total'] ?? 0),
            'formatted' => monetaryUnit()->convertAndFormat($totals['total'] ?? 0),
        ];
    }
}
