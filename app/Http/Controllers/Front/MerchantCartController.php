<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * MerchantCartController - Cart API
 *
 * كل العمليات Branch-Scoped (التجميع حسب الفرع)
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
     * Cart page - shows all branches grouped
     * GET /merchant-cart
     *
     * Each branch has their own section with:
     * - Items list
     * - Per-branch totals
     * - Per-branch checkout button
     */
    public function index(): View
    {
        $byBranch = $this->cart->getAllBranchesCart();

        return view('merchant.cart.index', [
            'byBranch' => $byBranch,
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
        ]);

        $result = $this->cart->addItem(
            merchantItemId: (int) $request->merchant_item_id,
            qty: (int) ($request->qty ?? 1)
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    // ══════════════════════════════════════════════════════════════
    // تعديل الكمية (Branch-Scoped)
    // ══════════════════════════════════════════════════════════════

    /**
     * Update item quantity (branch-scoped)
     * POST /merchant-cart/update
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
            'key' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $result = $this->cart->updateBranchQty(
            branchId: (int) $request->branch_id,
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
     * Increase item quantity by 1 (branch-scoped)
     * POST /merchant-cart/increase
     */
    public function increase(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
            'key' => 'required|string',
        ]);

        $branchId = (int) $request->branch_id;
        $cartKey = $request->key;

        // Get current item
        $items = $this->cart->getBranchItems($branchId);
        $item = $items[$cartKey] ?? null;

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('الصنف غير موجود'),
            ], 422);
        }

        $newQty = ($item['qty'] ?? 1) + 1;

        $result = $this->cart->updateBranchQty(
            branchId: $branchId,
            cartKey: $cartKey,
            qty: $newQty
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Decrease item quantity by 1 (branch-scoped)
     * POST /merchant-cart/decrease
     */
    public function decrease(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
            'key' => 'required|string',
        ]);

        $branchId = (int) $request->branch_id;
        $cartKey = $request->key;

        // Get current item
        $items = $this->cart->getBranchItems($branchId);
        $item = $items[$cartKey] ?? null;

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => __('الصنف غير موجود'),
            ], 422);
        }

        $currentQty = $item['qty'] ?? 1;
        $minQty = (int) ($item['min_qty'] ?? 1);

        // Check minimum quantity
        if ($currentQty <= $minQty) {
            return response()->json([
                'success' => false,
                'message' => __('الحد الأدنى للكمية') . ' ' . $minQty,
            ], 422);
        }

        $newQty = $currentQty - 1;

        $result = $this->cart->updateBranchQty(
            branchId: $branchId,
            cartKey: $cartKey,
            qty: $newQty
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'header_count' => $this->cart->getHeaderCount(),
        ], $result['success'] ? 200 : 422);
    }

    // ══════════════════════════════════════════════════════════════
    // حذف (Branch-Scoped)
    // ══════════════════════════════════════════════════════════════

    /**
     * Remove item from cart (branch-scoped)
     * DELETE /merchant-cart/remove/{key}
     * POST /merchant-cart/remove
     */
    public function remove(Request $request, ?string $key = null): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
        ]);

        $cartKey = $key ?? $request->input('key');

        if (!$cartKey) {
            return response()->json([
                'success' => false,
                'message' => __('مفتاح الصنف مطلوب'),
            ], 422);
        }

        $result = $this->cart->removeBranchItem(
            branchId: (int) $request->branch_id,
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
     * Clear branch items
     * POST /merchant-cart/clear-branch
     */
    public function clearBranch(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
        ]);

        $this->cart->clearBranch((int) $request->branch_id);

        return response()->json([
            'success' => true,
            'message' => __('تم مسح أصناف الفرع'),
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
    // قراءة البيانات (Branch-Scoped)
    // ══════════════════════════════════════════════════════════════

    /**
     * Get branch cart summary (AJAX)
     * GET /merchant-cart/summary?branch_id=X
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1',
        ]);

        $branchId = (int) $request->branch_id;
        $branchCart = $this->cart->getBranchCart($branchId);

        return response()->json([
            'success' => true,
            'branch_id' => $branchId,
            'branch_name' => $branchCart['branch_name'],
            'merchant_id' => $branchCart['merchant_id'],
            'merchant_name' => $branchCart['merchant_name'],
            'items' => array_values($branchCart['items']),
            'totals' => $this->formatTotals($branchCart['totals']),
            'has_other_branches' => $branchCart['has_other_branches'],
        ]);
    }

    /**
     * Get all branches cart (for full page)
     * GET /merchant-cart/all
     */
    public function all(): JsonResponse
    {
        $branchesCart = $this->cart->getAllBranchesCart();

        return response()->json([
            'success' => true,
            'branches' => $branchesCart,
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
     * Get branch IDs in cart
     * GET /merchant-cart/branches
     */
    public function branches(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'branch_ids' => $this->cart->getBranchIds(),
        ]);
    }

    /**
     * Get merchant IDs in cart (legacy support)
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
