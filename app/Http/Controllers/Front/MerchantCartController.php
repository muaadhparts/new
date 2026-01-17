<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Cart\MerchantCartManager;
use App\Services\Cart\CartStorage;
use App\Services\Cart\StockReservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * MerchantCartController - Clean cart API
 *
 * Only 6 endpoints:
 * POST   /merchant-cart/add         → add()
 * POST   /merchant-cart/update      → update()
 * DELETE /merchant-cart/remove/{key} → remove()
 * GET    /merchant-cart             → index()
 * GET    /merchant-cart/summary     → summary()
 * POST   /merchant-cart/clear       → clear()
 */
class MerchantCartController extends Controller
{
    private MerchantCartManager $cart;

    public function __construct()
    {
        $this->cart = new MerchantCartManager(
            new CartStorage(),
            new StockReservation()
        );
    }

    /**
     * Cart page view
     * GET /merchant-cart
     */
    public function index(): View
    {
        $cart = $this->cart->getCart();
        $byMerchant = $this->cart->getItemsByMerchant();
        $issues = $this->cart->validate();

        return view('merchant.cart.index', [
            'cart' => $cart,
            'byMerchant' => $byMerchant,
            'issues' => $issues,
            'isEmpty' => !$this->cart->hasItems(),
        ]);
    }

    /**
     * Get cart summary (AJAX)
     * GET /merchant-cart/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $cart = $this->cart->getCart();

        // If requested for specific merchant
        if ($request->has('merchant_id')) {
            $merchantId = (int) $request->merchant_id;
            $items = $this->cart->getItemsForMerchant($merchantId);

            $total = 0;
            $qty = 0;
            foreach ($items as $item) {
                $total += (float) ($item['total_price'] ?? 0);
                $qty += (int) ($item['qty'] ?? 0);
            }

            return response()->json([
                'success' => true,
                'merchant_id' => $merchantId,
                'items' => array_values($items),
                'totals' => [
                    'qty' => $qty,
                    'total' => $total,
                    'formatted' => monetaryUnit()->convertAndFormat($total),
                ],
            ]);
        }

        // Full cart summary
        $totals = $cart['totals'] ?? [];

        return response()->json([
            'success' => true,
            'items' => array_values($cart['items'] ?? []),
            'by_merchant' => $cart['by_merchant'] ?? [],
            'totals' => [
                'qty' => (int) ($totals['qty'] ?? 0),
                'subtotal' => (float) ($totals['subtotal'] ?? 0),
                'discount' => (float) ($totals['discount'] ?? 0),
                'total' => (float) ($totals['total'] ?? 0),
                'formatted' => monetaryUnit()->convertAndFormat($totals['total'] ?? 0),
            ],
            'item_count' => $this->cart->getItemCount(),
        ]);
    }

    /**
     * Add item to cart
     * POST /merchant-cart/add
     *
     * Required: merchant_item_id
     * Optional: qty, size, color, keys, values
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_item_id' => 'required|integer|exists:merchant_items,id',
            'qty' => 'nullable|integer|min:1',
            'size' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'keys' => 'nullable|string|max:500',
            'values' => 'nullable|string|max:500',
        ]);

        $result = $this->cart->add(
            merchantItemId: (int) $request->merchant_item_id,
            qty: (int) ($request->qty ?? 1),
            size: $request->size,
            color: $request->color,
            keys: $request->keys,
            values: $request->values
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Update item quantity
     * POST /merchant-cart/update
     *
     * Required: key, qty
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $result = $this->cart->updateQty(
            cartKey: $request->key,
            qty: (int) $request->qty
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Increase item quantity by 1
     * POST /merchant-cart/increase
     */
    public function increase(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $result = $this->cart->increase($request->key);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Decrease item quantity by 1
     * POST /merchant-cart/decrease
     */
    public function decrease(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $result = $this->cart->decrease($request->key);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Remove item from cart
     * DELETE /merchant-cart/remove/{key}
     * POST /merchant-cart/remove (with key in body)
     */
    public function remove(Request $request, ?string $key = null): JsonResponse
    {
        // Get key from route param or request body
        $cartKey = $key ?? $request->input('key');

        if (!$cartKey) {
            return response()->json([
                'success' => false,
                'message' => __('Cart key is required'),
            ], 422);
        }

        // URL decode the key (it may contain special characters)
        $cartKey = urldecode($cartKey);

        $result = $this->cart->remove($cartKey);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Clear cart
     * POST /merchant-cart/clear
     */
    public function clear(): JsonResponse
    {
        $result = $this->cart->clear();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals([]),
            'item_count' => 0,
        ]);
    }

    /**
     * Validate cart items (AJAX)
     * GET /merchant-cart/validate
     */
    public function validateCart(): JsonResponse
    {
        $issues = $this->cart->validate();

        return response()->json([
            'success' => empty($issues),
            'issues' => $issues,
            'has_issues' => !empty($issues),
        ]);
    }

    /**
     * Refresh cart (update prices, remove invalid items)
     * POST /merchant-cart/refresh
     */
    public function refresh(): JsonResponse
    {
        $result = $this->cart->refresh();

        return response()->json([
            'success' => true,
            'updated' => $result['updated'],
            'removed' => $result['removed'],
            'cart' => $result['cart'],
            'totals' => $this->formatTotals($result['cart']['totals'] ?? []),
            'item_count' => $this->cart->getItemCount(),
        ]);
    }

    /**
     * Get cart count (for header badge)
     * GET /merchant-cart/count
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->cart->getTotalQty(),
            'item_count' => $this->cart->getItemCount(),
        ]);
    }

    /**
     * Format totals with currency conversion
     */
    private function formatTotals(array $totals): array
    {
        $subtotal = (float) ($totals['subtotal'] ?? 0);
        $discount = (float) ($totals['discount'] ?? 0);
        $total = (float) ($totals['total'] ?? 0);

        return [
            'qty' => (int) ($totals['qty'] ?? 0),
            'subtotal' => $subtotal,
            'subtotal_formatted' => monetaryUnit()->convertAndFormat($subtotal),
            'discount' => $discount,
            'discount_formatted' => monetaryUnit()->convertAndFormat($discount),
            'total' => $total,
            'total_formatted' => monetaryUnit()->convertAndFormat($total),
        ];
    }
}
