<?php

/**
 * ====================================================================
 * UNIFIED CART CONTROLLER
 * ====================================================================
 *
 * Single entry point for all cart operations.
 * Uses merchant_product_id EXCLUSIVELY - NO fallbacks.
 *
 * Key Features:
 * 1. Unified endpoint: POST /cart/unified
 * 2. Strict validation - fails on missing required fields
 * 3. All data from MerchantProduct - no Product fallback
 * 4. Groups cart products by vendor_id
 *
 * @version 3.0 - Unified System
 * ====================================================================
 */

namespace App\Http\Controllers\Front;

use App\Helpers\ProductContextHelper;
use App\Helpers\CartHelper;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Generalsetting;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\State;
use App\Models\StockReservation;
use App\Services\VendorCartService;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CartController extends FrontBaseController
{
    /* =====================================================================
     * UNIFIED CART ENDPOINT (v3)
     * =====================================================================
     * Single entry point for ALL cart add operations.
     * REQUIRED: merchant_product_id
     * STRICT: Fails immediately on missing/invalid data - NO fallbacks
     * ===================================================================== */

    /**
     * Unified cart add endpoint
     * POST /cart/unified
     *
     * Required payload:
     * - merchant_product_id: int (REQUIRED)
     * - qty: int (default: from minimum_qty)
     * - size: string (optional)
     * - color: string (optional)
     *
     * Optional payload:
     * - vendor_id: int (validated against mp.user_id)
     * - size_price: float
     * - color_price: float
     */
    public function unifiedAdd(Request $request)
    {
        // ==========================================
        // STEP 1: STRICT VALIDATION
        // ==========================================
        $validator = Validator::make($request->all(), [
            'merchant_product_id' => 'required|integer|min:1',
            'qty' => 'nullable|integer|min:1',
            'size' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|integer',
            'size_price' => 'nullable|numeric|min:0',
            'color_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        // ==========================================
        // STRICT ASSERTIONS - Fail fast on missing required fields
        // ==========================================
        $mpId = $request->input('merchant_product_id');

        // ASSERT: merchant_product_id is REQUIRED and must be a positive integer
        if (!$mpId || !is_numeric($mpId) || (int)$mpId < 1) {
            \Log::error('unifiedAdd: ASSERTION FAILED - merchant_product_id missing or invalid', [
                'received' => $mpId,
                'request' => $request->all(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'merchant_product_id is required and must be a positive integer',
                'error_code' => 'ASSERT_MP_ID_REQUIRED',
            ], 400);
        }

        $mpId = (int)$mpId;

        // Get qty with strict default
        $qty = $request->input('qty');
        if ($qty !== null && $qty !== '') {
            $qty = (int)$qty;
            if ($qty < 1) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'qty must be at least 1',
                    'error_code' => 'ASSERT_QTY_INVALID',
                ], 400);
            }
        }
        // qty will be validated against minimum_qty after loading MerchantProduct

        // ==========================================
        // STEP 2: LOAD MERCHANT PRODUCT (NO FALLBACK)
        // ==========================================
        $mp = MerchantProduct::with(['product', 'user', 'qualityBrand'])->find($mpId);

        if (!$mp) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Product not found'),
                'error_code' => 'MP_NOT_FOUND',
            ], 404);
        }

        // ==========================================
        // STEP 3: STRICT GUARDS
        // ==========================================

        // Guard: MerchantProduct must be active
        if ((int) $mp->status !== 1) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Product is not available'),
                'error_code' => 'MP_INACTIVE',
            ], 400);
        }

        // Guard: Vendor must be active (is_vendor = 2)
        if (!$mp->user || (int) $mp->user->is_vendor !== 2) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Vendor is not active'),
                'error_code' => 'VENDOR_INACTIVE',
            ], 400);
        }

        // Guard: vendor_id must match (if provided)
        if ($request->filled('vendor_id')) {
            $requestedVendorId = (int) $request->input('vendor_id');
            if ($requestedVendorId !== (int) $mp->user_id) {
                \Log::warning('Cart: vendor_id mismatch', [
                    'requested' => $requestedVendorId,
                    'actual' => $mp->user_id,
                    'mp_id' => $mpId,
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => __('Invalid vendor'),
                    'error_code' => 'VENDOR_MISMATCH',
                ], 403);
            }
        }

        // ==========================================
        // STEP 4: EXTRACT & VALIDATE QTY/SIZE/COLOR
        // ==========================================
        $minQty = max(1, (int) ($mp->minimum_qty ?? 1));

        // Use the pre-validated $qty or default to minQty
        if ($qty === null) {
            $qty = $minQty;
        } else {
            $qty = max($minQty, $qty);
        }

        // ASSERT: qty must be >= minimum_qty
        if ($qty < $minQty) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Minimum quantity is :min', ['min' => $minQty]),
                'error_code' => 'ASSERT_QTY_BELOW_MIN',
                'minimum_qty' => $minQty,
            ], 400);
        }

        $size = trim((string) ($request->input('size') ?? ''));
        $color = trim((string) ($request->input('color') ?? ''));

        // Get stock
        $stock = (int) ($mp->stock ?? 0);
        $preordered = (bool) ($mp->preordered ?? false);

        // Handle sizes
        $sizes = $this->toArrayValues($mp->size ?? '');
        $sizeQtys = $this->toArrayValues($mp->size_qty ?? '');
        $sizePrices = $this->toArrayValues($mp->size_price ?? '');

        $effectiveStock = $stock;
        $sizePrice = 0;

        if (count($sizes) > 0) {
            // If size not provided, pick first available
            if ($size === '') {
                foreach ($sizes as $i => $sz) {
                    $szQty = (int) ($sizeQtys[$i] ?? 0);
                    if ($szQty > 0 || $preordered) {
                        $size = $sz;
                        $effectiveStock = $szQty;
                        $sizePrice = (float) ($sizePrices[$i] ?? 0);
                        break;
                    }
                }
            } else {
                // Validate provided size
                $sizeIdx = array_search(trim($size), array_map('trim', $sizes), true);
                if ($sizeIdx === false) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => __('Invalid size selected'),
                        'error_code' => 'INVALID_SIZE',
                    ], 400);
                }
                $effectiveStock = (int) ($sizeQtys[$sizeIdx] ?? 0);
                $sizePrice = (float) ($sizePrices[$sizeIdx] ?? 0);
            }
        }

        // Handle colors
        $colors = $this->toArrayValues($mp->color_all ?? '');
        $colorPrices = $this->toArrayValues($mp->color_price ?? '');
        $colorPrice = 0;

        if (count($colors) > 0) {
            if ($color === '') {
                $color = ltrim(trim($colors[0] ?? ''), '#');
                $colorPrice = (float) ($colorPrices[0] ?? 0);
            } else {
                $colorNorm = ltrim(trim($color), '#');
                $colorIdx = false;
                foreach ($colors as $i => $c) {
                    if (ltrim(trim($c), '#') === $colorNorm) {
                        $colorIdx = $i;
                        break;
                    }
                }
                if ($colorIdx !== false) {
                    $colorPrice = (float) ($colorPrices[$colorIdx] ?? 0);
                }
            }
        }

        // ==========================================
        // STEP 5: STOCK VALIDATION
        // ==========================================
        if ($effectiveStock <= 0 && !$preordered) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Out of Stock'),
                'error_code' => 'OUT_OF_STOCK',
            ], 422);
        }

        if (!$preordered && $qty > $effectiveStock) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Only') . ' ' . $effectiveStock . ' ' . __('items available'),
                'error_code' => 'INSUFFICIENT_STOCK',
                'available_stock' => $effectiveStock,
            ], 422);
        }

        // Guard: qty >= minimum_qty
        if ($qty < $minQty) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Minimum order quantity is') . ' ' . $minQty,
                'error_code' => 'BELOW_MIN_QTY',
                'min_qty' => $minQty,
            ], 400);
        }

        // ==========================================
        // STEP 6: GENERATE CART KEY & RESERVE STOCK (Atomic)
        // ==========================================
        // Generate unique cart key for this item
        $cartKey = $this->generateCartKey($mpId, $size, $color);

        if (!($preordered && $effectiveStock <= 0)) {
            try {
                $this->reserveStock($mpId, $qty, $size, $cartKey);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => __('Out of Stock'),
                    'error_code' => 'STOCK_RESERVE_FAILED',
                ], 422);
            }
        }

        // ==========================================
        // STEP 7: BUILD PRODUCT WITH CONTEXT
        // ==========================================
        try {
            $prod = ProductContextHelper::createWithContext($mp);
        } catch (\Exception $e) {
            // Return stock if product creation fails
            if (!($preordered && $effectiveStock <= 0)) {
                $this->returnStock($mpId, $qty, $size, $cartKey);
            }
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('Product not found'),
                'error_code' => 'PRODUCT_CREATE_FAILED',
            ], 500);
        }

        // Apply size/color prices
        if ($sizePrice > 0) {
            $prod->price += $sizePrice;
        }
        if ($colorPrice > 0) {
            $prod->price += $colorPrice;
        }

        // ==========================================
        // STEP 8: ADD TO CART
        // ==========================================
        $keys = (string) $request->input('keys', '');
        $values = (string) $request->input('values', '');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        if ($qty == 1) {
            $cart->add($prod, $mpId, $size, $color, $keys, $values);
        } else {
            $cart->addnum(
                $prod, $mpId, $qty, $size, $color,
                '', $sizePrice, $colorPrice, '',
                $keys, $values, $request->input('affilate_user', '0')
            );
        }

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        // ==========================================
        // STEP 9: RETURN SUCCESS RESPONSE
        // ==========================================
        return response()->json([
            'success' => true,
            'ok' => true,
            'status' => 'success',
            'message' => __('Item added to cart successfully'),
            'cart_count' => count($cart->items),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
            'totalQty' => $cart->totalQty,
            'totalPrice' => $cart->totalPrice,
            // Backwards compatibility
            'data' => [
                'cart_count' => count($cart->items),
                'cart_total' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
            ],
        ]);
    }

    /* ===================== Cart Summary Endpoint ===================== */

    /**
     * Get cart summary (for fallback/refresh)
     */
    public function cartSummary()
    {
        if (!Session::has('cart')) {
            return response()->json([
                'ok' => true,
                'cart_count' => 0,
                'cart_total' => $this->curr->sign . '0.00',
                'totalQty' => 0,
                'totalPrice' => 0
            ]);
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        return response()->json([
            'ok' => true,
            'cart_count' => count($cart->items),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
            'totalQty' => $cart->totalQty,
            'totalPrice' => $cart->totalPrice
        ]);
    }

    /* ===================== Stock Reservation (Atomic + Timed) ===================== */

    /**
     * Reserve stock atomically with size support + time-based reservation
     * @throws \Exception if insufficient stock
     */
    private function reserveStock(int $merchantProductId, int $deltaQty, ?string $size = null, ?string $cartKey = null): void
    {
        DB::transaction(function() use ($merchantProductId, $deltaQty, $size, $cartKey) {
            $row = DB::table('merchant_products')->where('id', $merchantProductId)->lockForUpdate()->first();
            if (!$row) {
                throw new \Exception('Merchant product not found');
            }

            // دعم المقاسات: إذا كان المنتج له مقاسات
            if ($size && !empty($row->size) && !empty($row->size_qty)) {
                $sizes = $this->toArrayValues($row->size);
                $qtys = $this->toArrayValues($row->size_qty);
                $idx = $this->findSizeIndex($sizes, $size);

                if ($idx !== false && isset($qtys[$idx])) {
                    // تحديث المقاس المحدد
                    $currentSizeQty = (int)$qtys[$idx];
                    $newSizeQty = $currentSizeQty - $deltaQty;

                    if ($newSizeQty < 0) {
                        throw new \Exception('Insufficient stock for size: ' . $size);
                    }

                    $qtys[$idx] = (string)$newSizeQty;
                    $newSizeQtyStr = implode(',', $qtys);

                    DB::table('merchant_products')
                        ->where('id', $merchantProductId)
                        ->update(['size_qty' => $newSizeQtyStr, 'updated_at' => now()]);
                }
            } else {
                // السقوط إلى stock العام إذا لم يكن هناك مقاس
                $newStock = (int)$row->stock - $deltaQty;
                if ($newStock < 0) {
                    throw new \Exception('Insufficient stock');
                }

                DB::table('merchant_products')
                    ->where('id', $merchantProductId)
                    ->update(['stock' => $newStock, 'updated_at' => now()]);
            }

            // إنشاء أو تحديث سجل الحجز المؤقت
            if ($cartKey) {
                StockReservation::reserve($merchantProductId, $deltaQty, $cartKey, $size);
            }
        });
    }

    /**
     * Update existing reservation quantity (or create if not exists)
     */
    private function updateReservationQty(string $cartKey, int $newQty, ?int $mpId = null, ?string $size = null): void
    {
        StockReservation::updateReservation($cartKey, $newQty, $mpId, $size);
    }

    /**
     * Return stock atomically with size support + release reservation
     */
    private function returnStock(int $merchantProductId, int $deltaQty, ?string $size = null, ?string $cartKey = null): void
    {
        DB::transaction(function() use ($merchantProductId, $deltaQty, $size, $cartKey) {
            $row = DB::table('merchant_products')->where('id', $merchantProductId)->lockForUpdate()->first();
            if (!$row) return; // المنتج محذوف، تجاهل

            // دعم المقاسات: إرجاع للمقاس المحدد
            if ($size && !empty($row->size) && !empty($row->size_qty)) {
                $sizes = $this->toArrayValues($row->size);
                $qtys = $this->toArrayValues($row->size_qty);
                $idx = $this->findSizeIndex($sizes, $size);

                if ($idx !== false && isset($qtys[$idx])) {
                    $qtys[$idx] = (string)((int)$qtys[$idx] + $deltaQty);
                    $newSizeQtyStr = implode(',', $qtys);

                    DB::table('merchant_products')
                        ->where('id', $merchantProductId)
                        ->update(['size_qty' => $newSizeQtyStr, 'updated_at' => now()]);
                }
            } else {
                // السقوط إلى stock العام
                DB::table('merchant_products')
                    ->where('id', $merchantProductId)
                    ->increment('stock', $deltaQty);
            }

            // حذف سجل الحجز (بدون إرجاع المخزون لأنه تم إرجاعه أعلاه)
            if ($cartKey) {
                StockReservation::release($cartKey, true);
            }
        });
    }

    /* ===================== New Merchant-Product-Based Methods ===================== */

    /**
     * Add merchant product to cart (New standardized method)
     */
    public function addMerchantCart($merchantProductId)
    {
        $mp = MerchantProduct::with(['product', 'user', 'qualityBrand'])->findOrFail($merchantProductId);

        if (!$mp || $mp->status !== 1) {
            return response()->json(['status' => 'error', 'msg' => __('Product not available')], 400);
        }

        $qty = max(1, (int) request('qty', 1));

        // فحص الحد الأدنى للكمية (MOQ)
        if ($mp->minimum_qty && $qty < (int)$mp->minimum_qty) {
            return response()->json([
                'status' => 'error',
                'msg' => __('Minimum order quantity is') . ' ' . $mp->minimum_qty
            ], 400);
        }

        // فحص المخزون مع دعم Preorder
        $effectiveStock = (int)($mp->stock ?? 0);
        if ($effectiveStock <= 0 && !$mp->preordered) {
            return response()->json(['status' => 'error', 'msg' => __('Out Of Stock')], 422);
        }

        // إذا كان preorder مفعل، السماح بالإضافة حتى لو المخزون = 0
        // لكن إذا المخزون موجود، يجب أن تكون الكمية المطلوبة أقل أو تساوي المخزون
        if ($effectiveStock > 0 && $qty > $effectiveStock && !$mp->preordered) {
            return response()->json([
                'status' => 'error',
                'msg' => __('Only') . ' ' . $effectiveStock . ' ' . __('items available')
            ], 422);
        }

        // CRITICAL: استخدام ProductContextHelper لإنشاء Product مع سياق merchant
        // Helper يضمن:
        // - Product instance جديد تماماً (لا يوجد Eloquent caching)
        // - حقن السعر الصحيح من MerchantProduct::vendorSizePrice()
        // - كل combination من (product_id, user_id, brand_quality_id) مستقل
        // - القيم المحقونة لها الأولوية على Product::__get() magic methods
        try {
            $prod = ProductContextHelper::createWithContext($mp);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => __('Product not found')], 404);
        }

        // تحديد المقاس
        $size = (string) request('size', '');
        if ($size === '') {
            [$size, $_] = $this->pickAvailableSize($mp->size, $mp->size_qty);
        }

        $color = (string) request('color', '');
        if ($color === '' && !empty($mp->color_all)) {
            $colors = $this->toArrayValues($mp->color_all);
            if (!empty($colors)) {
                $color = ltrim((string)$colors[0], '#');
            }
        }

        // حجز المخزون ذرياً مع المقاس (تخطي إذا كان preorder مفعل والمخزون = 0)
        if (!($mp->preordered && $effectiveStock <= 0)) {
            try {
                $this->reserveStock($merchantProductId, $qty, $size);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'msg' => __('Out Of Stock')], 422);
            }
        }

        // Merchant context already injected by ProductContextHelper::createWithContext()

        $keys = (string) request('keys','');
        $values = (string) request('values','');

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);

        // إضافة الكمية للسلة (المخزون محجوز مسبقاً)
        if ($qty == 1) {
            $cart->add($prod, $merchantProductId, $size, $color, $keys, $values);
        } else {
            $cart->addnum(
                $prod, $merchantProductId, $qty, $size, $color,
                '', 0, 0, '',
                $keys, $values, request('affilate_user', '0')
            );
        }

        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        return response()->json([
            'ok' => true,
            'cart_count' => count($cart->items),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
            'totalQty' => $cart->totalQty,
            'totalPrice' => $cart->totalPrice,
            'success' => __('Item added to cart successfully')
        ]);
    }

    /**
     * Quick add merchant product to cart
     */
    public function quickAddMerchantCart($merchantProductId)
    {
        return $this->addMerchantCart($merchantProductId);
    }

    /* ===================== Cart pages ===================== */

    public function cart(Request $request)
    {
        if (!Session::has('cart')) {
            return view('frontend.cart');
        }

        foreach (['already','coupon','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            if (Session::has($k)) Session::forget($k);
        }

        $oldCart    = Session::get('cart');
        $cart       = new Cart($oldCart);
        $products   = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;

        // تجميع المنتجات حسب التاجر
        $productsByVendor = $this->groupProductsByVendor($products);

        if ($request->ajax()) {
            // استخدام cart-page-v2 المحسّن (N+1 FIX)
            return view('frontend.ajax.cart-page-v2', compact('products', 'totalPrice', 'mainTotal', 'productsByVendor'));
        }
        return view('frontend.cart', compact('products', 'totalPrice', 'mainTotal', 'productsByVendor'));
    }

    /**
     * تجميع منتجات السلة حسب التاجر
     * يستخدم VendorCartService للحصول على بيانات كاملة لكل تاجر
     * بما في ذلك الوزن والأبعاد وخصم الجملة
     */
    private function groupProductsByVendor(array $products): array
    {
        $grouped = [];

        foreach ($products as $rowKey => $product) {
            // استخراج vendor_id باستخدام نفس منطق VendorCartService
            $vendorId = $product['user_id']
                ?? data_get($product, 'item.user_id')
                ?? data_get($product, 'item.vendor_user_id')
                ?? 0;

            if (!isset($grouped[$vendorId])) {
                // جلب مدينة التاجر باستخدام ShippingCalculatorService
                $vendorCityData = ShippingCalculatorService::getVendorCity($vendorId);
                $vendor = \App\Models\User::find($vendorId);

                $grouped[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor ? ($vendor->shop_name ?? $vendor->name) : null,
                    'vendor_city' => $vendorCityData['city_name'] ?? null,
                    'vendor_city_id' => $vendorCityData['city_id'] ?? null,
                    'products' => [],
                    'total' => 0,
                    'count' => 0,
                    'total_weight' => 0,
                    'shipping_data' => null,
                ];
            }

            // حساب خصم الجملة والأبعاد لكل منتج
            $mpId = $product['merchant_product_id']
                ?? data_get($product, 'item.merchant_product_id')
                ?? 0;
            $qty = (int)($product['qty'] ?? 1);
            $sizeVal = $product['size'] ?? '';
            $size = is_array($sizeVal) ? ($sizeVal[0] ?? '') : (string)$sizeVal;
            $colorVal = $product['color'] ?? '';
            $color = is_array($colorVal) ? ($colorVal[0] ?? '') : (string)$colorVal;

            // استخدام VendorCartService لحساب خصم الجملة
            $bulkDiscount = $mpId ? VendorCartService::calculateBulkDiscount($mpId, $qty) : null;

            // استخدام VendorCartService لجلب الأبعاد (بدون fallback)
            $dimensions = $mpId ? VendorCartService::getProductDimensions($mpId) : null;

            // جلب بيانات الحجز المؤقت
            $cartKey = $this->generateCartKey($mpId, $size ?: null, $color ?: null);
            $reservation = StockReservation::where('cart_key', $cartKey)
                ->where('session_id', session()->getId())
                ->first();

            $reservationData = null;
            if ($reservation) {
                $reservationData = [
                    'expires_at' => $reservation->expires_at->toIso8601String(),
                    'remaining_seconds' => $reservation->remainingSeconds(),
                    'remaining_minutes' => $reservation->remainingMinutes(),
                    'is_expired' => $reservation->isExpired(),
                ];
            }

            // إضافة البيانات المحسوبة للمنتج
            $product['bulk_discount'] = $bulkDiscount;
            $product['dimensions'] = $dimensions;
            $product['row_weight'] = $dimensions && $dimensions['weight'] ? $dimensions['weight'] * $qty : null;
            $product['reservation'] = $reservationData;

            $grouped[$vendorId]['products'][$rowKey] = $product;
            $grouped[$vendorId]['total'] += (float)($product['price'] ?? 0);
            $grouped[$vendorId]['count'] += (int)($product['qty'] ?? 1);

            // تجميع الوزن الكلي
            if ($dimensions && $dimensions['weight']) {
                $grouped[$vendorId]['total_weight'] += $dimensions['weight'] * $qty;
            }
        }

        // بناء بيانات الشحن لكل تاجر باستخدام VendorCartService
        foreach ($grouped as $vendorId => &$vendorData) {
            $shippingData = VendorCartService::calculateVendorShipping($vendorId, $products);
            $vendorData['shipping_data'] = $shippingData;
            $vendorData['has_complete_data'] = $shippingData['has_complete_data'];
            $vendorData['missing_data'] = $shippingData['missing_data'];
        }

        return $grouped;
    }

    public function cartview() { return view('load.cart'); }

    public function view_cart()
    {
        if (!Session::has('cart')) { return view('frontend.cart'); }
        foreach (['already','coupon','coupon_code','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            if (Session::has($k)) Session::forget($k);
        }
        $oldCart    = Session::get('cart');
        $cart       = new Cart($oldCart);
        $products   = $cart->items;
        $totalPrice = $cart->totalPrice;
        $mainTotal  = $totalPrice;

        // تجميع المنتجات حسب التاجر
        $productsByVendor = $this->groupProductsByVendor($products);

        // استخدام cart-page-v2 المحسّن (N+1 FIX)
        return view('frontend.ajax.cart-page-v2', compact('products', 'totalPrice', 'mainTotal', 'productsByVendor'));
    }

    /* ===================== Utilities ===================== */

    private function toArrayValues($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') return array_map('trim', explode(',', $v));
        return [];
    }
    private function findSizeIndex(array $sizes, string $size)
    {
        if ($size === '') return false;
        return array_search(trim($size), array_map('trim', $sizes), true);
    }
    private function pickAvailableSize(?string $sizeCsv, ?string $qtyCsv): array
    {
        $sizes = $this->toArrayValues($sizeCsv ?? '');
        $qtys  = $this->toArrayValues($qtyCsv  ?? '');
        $picked = '';
        $pickedQty = null;
        foreach ($sizes as $i => $sz) {
            $q = (int)($qtys[$i] ?? 0);
            if ($q > 0) { $picked = $sz; $pickedQty = $q; break; }
        }
        if ($picked === '' && !empty($sizes)) {
            $picked    = $sizes[0];
            $pickedQty = (int)($qtys[0] ?? 0);
        }
        return [$picked, $pickedQty];
    }
    // REMOVED: pickDefaultListing - No longer needed, use unifiedAdd with explicit merchant_product_id
    private function effectiveStock(MerchantProduct $mp, string $size = ''): int
    {
        if (!empty($mp->size) && !empty($mp->size_qty) && $size !== '') {
            $sizes = $this->toArrayValues($mp->size);
            $qtys  = $this->toArrayValues($mp->size_qty);
            $idx   = $this->findSizeIndex($sizes, $size);
            if ($idx !== false && isset($qtys[$idx]) && $qtys[$idx] !== '') return (int)$qtys[$idx];
            return 0;
        }
        if (is_null($mp->stock) || $mp->stock === '') return 999999;
        return (int)$mp->stock;
    }
    // داخل CartController

    /** هويّة المنتج فقط من products (بدون ألوان) */
    private function fetchIdentity(int $id): ?Product
    {
        // ملاحظة: تم نقل الألوان إلى merchant_products (color_all, color_price)
        return Product::query()->select([
            'id','slug','sku','name','photo',
            'weight','type','file','link','measure','attributes','cross_products',
        ])->find($id);
    }

    // REMOVED: fetchListingOrFallback - No longer needed, use unifiedAdd with explicit merchant_product_id

    private function normNum($v, $default = 0.0) { return is_numeric($v) ? (float)$v : (float)$default; }

    /**
     * توليد مفتاح فريد للسلة
     * يستخدم لتتبع الحجوزات
     */
    private function generateCartKey(int $mpId, ?string $size = null, ?string $color = null): string
    {
        $sessionId = session()->getId();
        $parts = [$sessionId, $mpId];
        if ($size) $parts[] = 'sz_' . $size;
        if ($color) $parts[] = 'cl_' . ltrim($color, '#');
        return implode('_', $parts);
    }

    /**
     * استخراج vendor_id من صف السلة
     * يدعم الـ object و array
     */
    private function extractVendorIdFromRow(array $row): int
    {
        // الأولوية للـ user_id في المستوى الأول
        if (isset($row['user_id']) && $row['user_id']) {
            return (int) $row['user_id'];
        }

        // ثم من item object/array
        $item = $row['item'] ?? null;
        if ($item) {
            if (is_object($item)) {
                return (int) ($item->user_id ?? $item->vendor_user_id ?? 0);
            }
            if (is_array($item)) {
                return (int) ($item['user_id'] ?? $item['vendor_user_id'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * استخراج merchant_product_id من صف السلة
     */
    private function extractMerchantProductId(array $row): int
    {
        if (isset($row['merchant_product_id']) && $row['merchant_product_id']) {
            return (int) $row['merchant_product_id'];
        }

        $item = $row['item'] ?? null;
        if ($item) {
            if (is_object($item)) {
                return (int) ($item->merchant_product_id ?? 0);
            }
            if (is_array($item)) {
                return (int) ($item['merchant_product_id'] ?? 0);
            }
        }

        return 0;
    }

    private function recomputeTotals(Cart $cart): void
    {
        $totalQty = 0; $totalPrice = 0.0;
        if (is_array($cart->items)) {
            foreach ($cart->items as $row) {
                $totalQty   += (int)($row['qty'] ?? 0);
                $totalPrice += (float)($row['price'] ?? 0);
            }
        }
        $cart->totalQty   = $totalQty;
        $cart->totalPrice = $totalPrice;
    }

    private function findRowKeyInCart(Cart $cart, int $productId, ?int $vendorId, ?string $sizeKey, ?string $size, ?string $color, ?string $values): ?string
    {
        $valuesNorm = is_string($values) ? str_replace([' ', ','], '', $values) : null;
        foreach ((array)$cart->items as $k => $row) {
            $rowItem = $row['item'] ?? null;
            if (!$rowItem) continue;
            if ((int)($rowItem->id ?? 0) !== $productId) continue;
            if ($vendorId !== null) {
                $rowVendor = (int)($rowItem->vendor_user_id ?? $rowItem->user_id ?? 0);
                if ($rowVendor !== $vendorId) continue;
            }
            if ($sizeKey !== null && $sizeKey !== '') {
                if ((string)($row['size_key'] ?? '') !== (string)$sizeKey) continue;
            }
            if ($size !== null && $size !== '') {
                if (strcasecmp((string)($row['size'] ?? ''), (string)$size) !== 0) continue;
            }
            if ($color !== null && $color !== '') {
                if (strcasecmp((string)($row['color'] ?? ''), (string)$color) !== 0) continue;
            }
            if ($valuesNorm !== null && $valuesNorm !== '') {
                $rowValuesNorm = str_replace([' ', ','], '', (string)($row['values'] ?? ''));
                if ($rowValuesNorm !== $valuesNorm) continue;
            }
            return $k;
        }
        return null;
    }

    /* ===================== DEPRECATED METHODS - Use unifiedAdd() instead ===================== */

    /**
     * @deprecated Use unifiedAdd() with merchant_product_id
     */
    public function addcart($id)
    {
        return response()->json([
            'ok' => false,
            'error' => 'This endpoint is deprecated. Use POST /cart/unified with merchant_product_id',
            'deprecated' => true
        ], 410);
    }

    /**
     * @deprecated Use unifiedAdd() with merchant_product_id
     */
    public function addtocart($id)
    {
        return redirect()->route('front.cart')->with('unsuccess', __('This method is deprecated. Please refresh the page and try again.'));
    }

    /**
     * @deprecated Use unifiedAdd() with merchant_product_id
     */
    public function addnumcart(Request $request)
    {
        return response()->json([
            'ok' => false,
            'error' => 'This endpoint is deprecated. Use POST /cart/unified with merchant_product_id',
            'deprecated' => true
        ], 410);
    }

    /**
     * @deprecated Use unifiedAdd() with merchant_product_id
     */
    public function addtonumcart(Request $request)
    {
        return redirect()->route('front.cart')->with('unsuccess', __('This method is deprecated. Please refresh the page and try again.'));
    }

    /* ===================== زيادة/نقصان (الأسامي الجديدة) ===================== */

    public function increaseItem(Request $request)
    {
        if (!Session::has('cart')) {
            return response()->json(['status'=>'error','msg'=>'No cart'], 400);
        }

        $row = (string)$request->input('row', '');
        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        if (!isset($cart->items[$row])) {
            return response()->json(['status'=>'error','msg'=>'Row not found'], 404);
        }

        $rowData  = $cart->items[$row];
        $item     = $rowData['item'];
        $mpId     = (int)($item->merchant_product_id ?? 0);
        $size     = (string)($rowData['size'] ?? '');

        if (!$mpId) {
            return response()->json(['status'=>'error','msg'=>'Invalid merchant product'], 400);
        }

        $mp = MerchantProduct::where('id', $mpId)->first();
        if (!$mp || (int)$mp->status !== 1) {
            return response()->json(['status'=>'error','msg'=>'Vendor listing invalid'], 400);
        }

        // حجز المخزون ذرياً مع المقاس
        try {
            $this->reserveStock($mpId, 1, $size);
        } catch (\Exception $e) {
            return response()->json(['status'=>'error','msg'=>__('Out Of Stock')], 422);
        }

        $cart->adding($item, $row, $rowData['size_qty'] ?? '', 0);
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        return response()->json([
            'status'     => 'ok',
            'row'        => $row,
            'qty'        => $cart->items[$row]['qty'],
            'row_total'  => \PriceHelper::showCurrencyPrice($cart->items[$row]['price'] * $this->curr->value),
            'totalQty'   => $cart->totalQty,
            'totalPrice' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
        ]);
    }

    public function decreaseItem(Request $request)
    {
        if (!Session::has('cart')) {
            return response()->json(['status'=>'error','msg'=>'No cart'], 400);
        }

        $row = (string)$request->input('row', '');
        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        if (!isset($cart->items[$row])) {
            return response()->json(['status'=>'error','msg'=>'Row not found'], 404);
        }

        $rowData = $cart->items[$row];
        $item    = $rowData['item'];
        $mpId    = (int)($item->merchant_product_id ?? 0);
        $size    = (string)($rowData['size'] ?? '');
        $qtyNow  = (int)($rowData['qty'] ?? 0);

        if (!$mpId) {
            return response()->json(['status'=>'error','msg'=>'Invalid merchant product'], 400);
        }

        if ($qtyNow <= 1) {
            // إرجاع المخزون الكامل عند الحذف
            $this->returnStock($mpId, 1, $size);
            $cart->removeItem($row);
            $this->recomputeTotals($cart);
            Session::put('cart', $cart);
            if (empty($cart->items)) { Session::forget('cart'); }
            return response()->json(['status'=>'ok','msg'=>'Item removed','qty'=>0]);
        }

        // إرجاع قطعة واحدة للمخزون
        $this->returnStock($mpId, 1, $size);

        $cart->reducing($item, $row, $rowData['size_qty'] ?? '', 0);
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);

        return response()->json([
            'status'     => 'ok',
            'row'        => $row,
            'qty'        => $cart->items[$row]['qty'],
            'row_total'  => \PriceHelper::showCurrencyPrice($cart->items[$row]['price'] * $this->curr->value),
            'totalQty'   => $cart->totalQty,
            'totalPrice' => \PriceHelper::showCurrencyPrice($cart->totalPrice * $this->curr->value),
        ]);
    }

    public function addbyone()
    {
        if (\Session::has('coupon')) {
            \Session::forget('coupon');
        }

        $curr       = $this->curr;
        $id         = (int) request('id', 0);
        $itemid     = (string) request('itemid', '');
        $size_qty   = request('size_qty');
        $size_price = (float) request('size_price', 0);

        // السلة والعنصر الحالي
        $oldCart = \Session::has('cart') ? \Session::get('cart') : null;

        if (!$oldCart || !isset($oldCart->items[$itemid])) {
            return 0;
        }

        // المنتج (هوية فقط)
        $prod = \App\Models\Product::find($id, ['id','slug','name','photo','type','attributes']);
        if (!$prod) { return 0; }

        // معلومات الصف من السلة (Vendor-aware)
        $row  = $oldCart->items[$itemid];
        $item = $row['item'] ?? null;

        // استخراج merchant_product_id من السلة (NO FALLBACK - يجب أن يكون موجوداً)
        $mpId = $this->extractMerchantProductId($row);
        if (!$mpId) {
            \Log::warning('addbyone: merchant_product_id not found in cart row', ['itemid' => $itemid]);
            return 0;
        }

        // جلب عرض البائع مباشرة بـ ID (NO QUERY by product_id+vendor_id)
        $mp = MerchantProduct::find($mpId);
        if (!$mp || (int)$mp->status !== 1) {
            \Log::warning('addbyone: MerchantProduct not found or inactive', ['mp_id' => $mpId]);
            return 0;
        }

        // تأكيد تطابق البائع (STRICT ASSERTION)
        $vendorId = (int)$mp->user_id;

        // احسب المخزون الفعلي للمقاس الحالي
        $size = (string)($row['size'] ?? '');
        $effStock = $this->effectiveStock($mp, $size);

        $currentQty = (int)($row['qty'] ?? 0);
        $newQty = $currentQty + 1;

        // التحقق من المخزون
        // effStock = المخزون بعد خصم الحجوزات السابقة
        // نحتاج التحقق إذا كان هناك وحدة واحدة متاحة على الأقل
        $stockCheckEnabled = (int)($mp->stock_check ?? 0) === 1;
        $preordered = (int)($mp->preordered ?? 0);

        if ($stockCheckEnabled && !$preordered) {
            if ($effStock <= 0) {
                return 0; // لا يوجد مخزون متاح
            }
        }

        // حجز الوحدة الإضافية وتحديث جدول الحجوزات
        $sizeVal = $row['size'] ?? '';
        $sizeStr = is_array($sizeVal) ? ($sizeVal[0] ?? '') : (string)$sizeVal;
        $colorVal = $row['color'] ?? '';
        $colorStr = is_array($colorVal) ? ($colorVal[0] ?? '') : (string)$colorVal;
        $cartKey = $this->generateCartKey($mpId, $sizeStr ?: null, $colorStr ?: null);

        if (!$preordered && $effStock > 0) {
            try {
                // حجز وحدة واحدة إضافية
                $this->reserveStock($mpId, 1, $sizeStr ?: null, null);
                // تحديث سجل الحجز بالكمية الجديدة (أو إنشاءه إذا لم يكن موجوداً)
                $this->updateReservationQty($cartKey, $newQty, $mpId, $sizeStr ?: null);
            } catch (\Exception $e) {
                \Log::warning('addbyone: Stock reserve failed', ['mp_id' => $mpId, 'error' => $e->getMessage()]);
                return 0;
            }
        }

        // حساب خصم الجملة الجديد باستخدام VendorCartService
        $bulkDiscount = VendorCartService::calculateBulkDiscount($mp->id, $newQty);

        // حقن سياق البائع داخل كائن المنتج
        $prod->user_id             = $vendorId;
        $prod->vendor_user_id      = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $bulkDiscount['discounted_price']; // استخدام السعر بعد خصم الجملة
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = $effStock;

        // تمرير مقاسات البائع
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);

        // إضافة أسعار الخصائص المفعّلة
        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }

        // الزيادة (+1)
        $cart = new Cart($oldCart);
        $cart->adding($prod, $itemid, $size_qty, $size_price);

        // تطبيق خصم الجملة المحسوب
        if ($bulkDiscount['has_discount']) {
            $cart->items[$itemid]['discount'] = $bulkDiscount['discount_percent'];
            $cart->items[$itemid]['price'] = $bulkDiscount['total_price'];
        }

        // إعادة احتساب الإجمالي
        $this->recomputeTotals($cart);

        \Session::put('cart', $cart);

        // جلب بيانات الأبعاد باستخدام VendorCartService
        $dimensions = VendorCartService::getProductDimensions($mp->id);

        // جلب المخزون المحدث
        $mp->refresh();
        $updatedStock = $this->effectiveStock($mp, $sizeStr);

        // تجهيز الاستجابة مع بيانات إضافية
        $data = [];
        $data[0] = \PriceHelper::showCurrencyPrice($cart->totalPrice * $curr->value);
        $data[1] = $cart->items[$itemid]['qty'];
        $data[2] = \PriceHelper::showCurrencyPrice($cart->items[$itemid]['price'] * $curr->value);
        $data[3] = $data[0];
        $data[4] = $bulkDiscount['has_discount'] ? '(' . $bulkDiscount['discount_percent'] . '% ' . __('Off') . ')' : '';
        $data['bulk_discount'] = $bulkDiscount;
        $data['dimensions'] = $dimensions;
        $data['row_weight'] = $dimensions['weight'] ? $dimensions['weight'] * $newQty : null;
        $data['stock'] = $updatedStock; // المخزون المتبقي
        $data['qty'] = $newQty;

        return response()->json($data);
    }


    public function reducebyone()
    {
        if (\Session::has('coupon')) {
            \Session::forget('coupon');
        }

        $curr       = $this->curr;
        $id         = (int) request('id', 0);
        $itemid     = (string) request('itemid', '');
        $size_qty   = request('size_qty');
        $size_price = (float) request('size_price', 0);

        $oldCart = \Session::has('cart') ? \Session::get('cart') : null;
        if (!$oldCart || !isset($oldCart->items[$itemid])) {
            return 0;
        }

        $prod = \App\Models\Product::find($id, ['id','slug','name','photo','type','attributes']);
        if (!$prod) { return 0; }

        $row  = $oldCart->items[$itemid];

        // استخراج merchant_product_id من السلة (NO FALLBACK - يجب أن يكون موجوداً)
        $mpId = $this->extractMerchantProductId($row);
        if (!$mpId) {
            \Log::warning('reducebyone: merchant_product_id not found in cart row', ['itemid' => $itemid]);
            return 0;
        }

        // جلب عرض البائع مباشرة بـ ID (NO QUERY by product_id+vendor_id)
        $mp = MerchantProduct::find($mpId);
        if (!$mp || (int)$mp->status !== 1) {
            \Log::warning('reducebyone: MerchantProduct not found or inactive', ['mp_id' => $mpId]);
            return 0;
        }

        // تأكيد تطابق البائع (STRICT ASSERTION)
        $vendorId = (int)$mp->user_id;

        // التحقق من الحد الأدنى للكمية قبل التنقيص
        $currentQty = (int)($row['qty'] ?? 0);
        $minQty = (int)($mp->minimum_qty ?? 1);
        if ($minQty < 1) $minQty = 1;

        if ($currentQty <= $minQty) {
            return 0; // لا يمكن التنقيص - وصلنا للحد الأدنى
        }

        $newQty = $currentQty - 1;

        // إرجاع وحدة واحدة للمخزون وتحديث جدول الحجوزات
        $sizeVal = $row['size'] ?? '';
        $sizeStr = is_array($sizeVal) ? ($sizeVal[0] ?? '') : (string)$sizeVal;
        $colorVal = $row['color'] ?? '';
        $colorStr = is_array($colorVal) ? ($colorVal[0] ?? '') : (string)$colorVal;
        $cartKey = $this->generateCartKey($mpId, $sizeStr ?: null, $colorStr ?: null);

        $preordered = (int)($mp->preordered ?? 0);
        if (!$preordered) {
            // إرجاع وحدة واحدة للمخزون (بدون حذف الحجز)
            $this->returnStock($mpId, 1, $sizeStr ?: null, null);
            // تحديث سجل الحجز بالكمية الجديدة (أو إنشاءه إذا لم يكن موجوداً)
            $this->updateReservationQty($cartKey, $newQty, $mpId, $sizeStr ?: null);
        }

        // حساب خصم الجملة الجديد باستخدام VendorCartService
        $bulkDiscount = VendorCartService::calculateBulkDiscount($mp->id, $newQty);

        // حقن سياق البائع
        $prod->user_id             = $vendorId;
        $prod->vendor_user_id      = $vendorId;
        $prod->merchant_product_id = $mp->id;
        $prod->price               = $bulkDiscount['discounted_price']; // استخدام السعر بعد خصم الجملة
        $prod->previous_price      = $mp->previous_price;
        $prod->stock               = (int)($mp->stock ?? 0);
        $prod->minimum_qty         = $minQty;
        $prod->preordered          = (int)($mp->preordered ?? 0);
        $prod->setAttribute('size',       $mp->size);
        $prod->setAttribute('size_qty',   $mp->size_qty);
        $prod->setAttribute('size_price', $mp->size_price);

        // خصائص المنتج
        if (!empty($prod->attributes)) {
            $attrArr = json_decode($prod->attributes, true);
            if (!empty($attrArr)) {
                foreach ($attrArr as $attrKey => $attrVal) {
                    if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                        foreach ($attrVal['values'] as $optionKey => $optionVal) {
                            $prod->price += $attrVal['prices'][$optionKey];
                            break;
                        }
                    }
                }
            }
        }

        $cart = new Cart($oldCart);
        $cart->reducing($prod, $itemid, $size_qty, $size_price);

        // تطبيق خصم الجملة المحسوب
        if ($bulkDiscount['has_discount']) {
            $cart->items[$itemid]['discount'] = $bulkDiscount['discount_percent'];
            $cart->items[$itemid]['price'] = $bulkDiscount['total_price'];
        } else {
            // إذا لم يعد هناك خصم، إعادة حساب السعر بدون خصم
            $cart->items[$itemid]['discount'] = 0;
            $cart->items[$itemid]['price'] = $bulkDiscount['total_price'];
        }

        // إعادة احتساب الإجمالي
        $this->recomputeTotals($cart);

        \Session::put('cart', $cart);

        // جلب بيانات الأبعاد باستخدام VendorCartService
        $dimensions = VendorCartService::getProductDimensions($mp->id);

        // جلب المخزون المحدث
        $mp->refresh();
        $updatedStock = $this->effectiveStock($mp, $sizeStr);

        // تجهيز الاستجابة مع بيانات إضافية
        $data = [];
        $data[0] = \PriceHelper::showCurrencyPrice($cart->totalPrice * $curr->value);
        $data[1] = $cart->items[$itemid]['qty'];
        $data[2] = \PriceHelper::showCurrencyPrice($cart->items[$itemid]['price'] * $curr->value);
        $data[3] = $data[0];
        $data[4] = $bulkDiscount['has_discount'] ? '(' . $bulkDiscount['discount_percent'] . '% ' . __('Off') . ')' : '';
        $data['bulk_discount'] = $bulkDiscount;
        $data['dimensions'] = $dimensions;
        $data['row_weight'] = $dimensions['weight'] ? $dimensions['weight'] * $newQty : null;
        $data['stock'] = $updatedStock; // المخزون المتبقي
        $data['qty'] = $newQty;

        return response()->json($data);
    }

    /* ===================== remove ===================== */
    public function removecart(Request $request, $id)
    {
        $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!Session::has('cart')) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cart is empty.')
                ]);
            }
            return back();
        }

        $oldCart = Session::get('cart');
        $cart    = new Cart($oldCart);

        $rowKey = $request->input('row', $id);
        if (!is_array($cart->items) || !array_key_exists($rowKey, $cart->items)) {
            $productId = (int) $id;
            $vendorId  = $request->has('user') ? (int)$request->input('user') : null;
            $sizeKey   = $request->input('size_key');
            $size      = $request->input('size');
            $color     = $request->input('color');
            $values    = $request->input('values');
            $rowKey = $this->findRowKeyInCart($cart, $productId, $vendorId, $sizeKey, $size, $color, $values);
            if (!$rowKey) {
                foreach ((array)$cart->items as $k => $row) {
                    if ((int)($row['item']->id ?? 0) === $productId) { $rowKey = $k; break; }
                }
            }
        }

        $removed = false;
        if ($rowKey && isset($cart->items[$rowKey])) {
            // إرجاع المخزون قبل الحذف
            $rowData = $cart->items[$rowKey];
            $item    = $rowData['item'] ?? null;
            if ($item) {
                $mpId = (int)($item->merchant_product_id ?? 0);
                $sizeVal = $rowData['size'] ?? '';
                $size = is_array($sizeVal) ? ($sizeVal[0] ?? '') : (string)$sizeVal;
                $colorVal = $rowData['color'] ?? '';
                $color = is_array($colorVal) ? ($colorVal[0] ?? '') : (string)$colorVal;
                $qty  = (int)($rowData['qty'] ?? 0);
                if ($mpId > 0 && $qty > 0) {
                    $cartKey = $this->generateCartKey($mpId, $size, $color);
                    $this->returnStock($mpId, $qty, $size, $cartKey);
                }
            }
            $cart->removeItem($rowKey);
            $removed = true;
        }

        foreach (['already','coupon','coupon_total','coupon_total1','coupon_percentage'] as $k) {
            Session::forget($k);
        }
        $this->recomputeTotals($cart);
        Session::put('cart', $cart);
        if (empty($cart->items)) { Session::forget('cart'); }

        // AJAX response
        if ($isAjax) {
            $newTotal = $cart->totalPrice ?? 0;
            $itemCount = count($cart->items ?? []);

            // Group by vendor for updated counts
            $vendorCounts = [];
            $vendorTotals = [];
            foreach ((array)($cart->items ?? []) as $item) {
                $vid = $item['user_id'] ?? ($item['item']->user_id ?? 0);
                if (!isset($vendorCounts[$vid])) {
                    $vendorCounts[$vid] = 0;
                    $vendorTotals[$vid] = 0;
                }
                $vendorCounts[$vid]++;
                $vendorTotals[$vid] += ($item['price'] ?? 0);
            }

            return response()->json([
                'success' => true,
                'removed' => $removed,
                'message' => $removed ? __('Item has been removed from cart.') : __('Item not found in cart.'),
                'total' => $newTotal,
                'itemCount' => $itemCount,
                'vendorCounts' => $vendorCounts,
                'vendorTotals' => $vendorTotals
            ]);
        }

        return back()->with('success', __('Item has been removed from cart.'));
    }

    /* ===================== tax ===================== */
    /**
     * Calculate tax based on location
     *
     * السيناريو:
     * - البيانات تأتي من الجداول (Cache من Google Maps + Tryoto)
     * - country_id و state_id و city_id محفوظة في الـ Cache
     * - الضريبة تُحسب من الـ Cache
     *
     * الأولوية:
     * 1. City tax (إذا موجود)
     * 2. State tax (إذا موجود)
     * 3. Country tax
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function country_tax(Request $request)
    {
        $data = [];
        $tax = 0;
        $tax_location = '';
        $tax_location_ar = '';
        $tax_type = 'none';
        $location_id = 0;

        // ==========================================
        // الأولوية 1: City (إذا موجود)
        // ==========================================
        if ($request->filled('city_id') && $request->city_id != 0) {
            $city = \App\Models\City::find($request->city_id);

            if ($city) {
                // Check if city has tax defined
                if (isset($city->tax) && $city->tax > 0) {
                    $tax = $city->tax;
                    $tax_type = 'city_tax';
                    $location_id = $city->id;
                    $tax_location = $city->city_name;
                    $tax_location_ar = $city->city_name_ar ?? $city->city_name;
                } else {
                    // City has no tax, fallback to state
                    $request->merge(['state_id' => $city->state_id]);
                }
            }
        }

        // ==========================================
        // الأولوية 2: State (إذا لم يوجد city tax)
        // ==========================================
        if ($tax == 0 && $request->filled('state_id') && $request->state_id != 0) {
            $state = State::find($request->state_id);

            if ($state && $state->tax > 0) {
                $tax = $state->tax;
                $tax_type = 'state_tax';
                $location_id = $state->id;
                $tax_location = $state->state;
                $tax_location_ar = $state->state_ar ?? $state->state;

                // Add country name
                $country = Country::find($state->country_id);
                if ($country) {
                    $tax_location .= ', ' . $country->country_name;
                    $tax_location_ar .= ', ' . ($country->country_name_ar ?? $country->country_name);
                }
            } elseif ($state) {
                // State has no tax, fallback to country
                $request->merge(['country_id' => $state->country_id]);
            }
        }

        // ==========================================
        // الأولوية 3: Country (إذا لم يوجد state tax)
        // ==========================================
        if ($tax == 0 && $request->filled('country_id') && $request->country_id != 0) {
            $country = Country::find($request->country_id);

            if ($country && $country->tax > 0) {
                $tax = $country->tax;
                $tax_type = 'country_tax';
                $location_id = $country->id;
                $tax_location = $country->country_name;
                $tax_location_ar = $country->country_name_ar ?? $country->country_name;
            }
        }

        // ==========================================
        // حساب الضريبة
        // ==========================================
        Session::put('is_tax', $tax);

        $total = max(0, (float) $request->input('total', 0));
        $shipping_cost = max(0, (float) $request->input('shipping_cost', 0));

        // Apply coupon discount before tax calculation
        $coupon_discount = Session::has('coupon') ? (float) Session::get('coupon') : 0;
        $subtotal_after_coupon = max(0, $total - $coupon_discount);

        // Calculate tax on (subtotal after coupon + shipping)
        $taxable_amount = $subtotal_after_coupon + $shipping_cost;
        $tax_amount = ($taxable_amount * $tax) / 100;
        $converted_tax = $tax_amount * $this->curr->value;

        Session::put('current_tax', $converted_tax);
        Session::put('current_tax_amount', $tax_amount);

        $final_total = $taxable_amount + $tax_amount;

        // ==========================================
        // إعداد الـ Response
        // ==========================================
        $data[0] = round($final_total, 2);           // Final total
        $data[1] = $tax;                              // Tax percentage
        $data[2] = round($converted_tax, 2);          // Converted tax amount
        $data[3] = $tax_location;                     // Tax location (EN)
        $data[11] = $location_id;                     // Location ID
        $data[12] = $tax_type;                        // Tax type (city_tax, state_tax, country_tax)

        // Additional data for better frontend handling
        $data['tax_location_ar'] = $tax_location_ar;  // Tax location (AR)
        $data['tax_percentage'] = $tax;
        $data['tax_amount'] = round($tax_amount, 2);
        $data['taxable_amount'] = round($taxable_amount, 2);

        return response()->json($data);
    }

    /* =====================================================================
     * NEW UNIFIED CART SYSTEM (v2) - يستخدم CartHelper
     * =====================================================================
     * هذه الدوال تستخدم CartHelper الجديد الذي يوفر:
     * 1. cartKey موحد في كل مكان
     * 2. ربط كامل بـ merchant_products
     * 3. دعم المخزون والحد الأدنى والألوان والمقاسات
     * 4. دعم تعدد التجار
     * ===================================================================== */

    /**
     * V2: إضافة عنصر للسلة
     */
    public function v2AddItem(Request $request, $mpId)
    {
        $qty = max(1, (int) $request->input('qty', 1));
        $size = (string) $request->input('size', '');
        $color = (string) $request->input('color', '');
        $values = (string) $request->input('values', '');
        $keys = $request->input('keys');
        if (!is_array($keys)) {
            $keys = (is_string($keys) && $keys !== '') ? explode(',', $keys) : [];
        }

        $result = CartHelper::addItem((int)$mpId, $qty, $size, $color, $values, $keys);

        if (!$result['success']) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => $result['message'],
                'error' => $result['message']
            ], 400);
        }

        $cart = $result['cart'];

        return response()->json([
            'ok' => true,
            'status' => 'success',
            'success' => $result['message'],
            'cart_count' => CartHelper::getItemCount(),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            'totalQty' => $cart['totalQty'],
            'totalPrice' => $cart['totalPrice']
        ]);
    }

    /**
     * V2: زيادة كمية عنصر
     */
    public function v2IncreaseQty(Request $request)
    {
        $cartKey = (string) $request->input('cart_key', '');

        if (!$cartKey) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => __('Invalid cart key')
            ], 400);
        }

        $result = CartHelper::increaseQty($cartKey);

        if (!$result['success']) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => $result['message']
            ], 400);
        }

        $item = $result['item'];
        $cart = $result['cart'];

        // بناء الاستجابة بنفس تنسيق النظام القديم للتوافق
        return response()->json([
            'ok' => true,
            'status' => 'success',
            // التنسيق القديم (array indices)
            0 => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value), // cart total
            1 => $item['qty'], // new qty
            2 => \PriceHelper::showCurrencyPrice($item['total_price'] * $this->curr->value), // row total
            3 => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value), // cart total again
            4 => $item['discount'] > 0 ? '(' . $item['discount'] . '% ' . __('Off') . ')' : '', // discount text
            // التنسيق الجديد
            'cart_key' => $cartKey,
            'qty' => $item['qty'],
            'row_total' => \PriceHelper::showCurrencyPrice($item['total_price'] * $this->curr->value),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            'totalQty' => $cart['totalQty'],
            'totalPrice' => $cart['totalPrice'],
            'discount' => $item['discount']
        ]);
    }

    /**
     * V2: إنقاص كمية عنصر
     */
    public function v2DecreaseQty(Request $request)
    {
        $cartKey = (string) $request->input('cart_key', '');

        if (!$cartKey) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => __('Invalid cart key')
            ], 400);
        }

        $result = CartHelper::decreaseQty($cartKey);

        if (!$result['success']) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => $result['message']
            ], 400);
        }

        $item = $result['item'];
        $cart = $result['cart'];

        return response()->json([
            'ok' => true,
            'status' => 'success',
            // التنسيق القديم
            0 => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            1 => $item['qty'],
            2 => \PriceHelper::showCurrencyPrice($item['total_price'] * $this->curr->value),
            3 => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            4 => $item['discount'] > 0 ? '(' . $item['discount'] . '% ' . __('Off') . ')' : '',
            // التنسيق الجديد
            'cart_key' => $cartKey,
            'qty' => $item['qty'],
            'row_total' => \PriceHelper::showCurrencyPrice($item['total_price'] * $this->curr->value),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            'totalQty' => $cart['totalQty'],
            'totalPrice' => $cart['totalPrice'],
            'discount' => $item['discount']
        ]);
    }

    /**
     * V2: حذف عنصر من السلة
     */
    public function v2RemoveItem(Request $request, $cartKey = null)
    {
        $cartKey = $cartKey ?: (string) $request->input('cart_key', '');

        if (!$cartKey) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'msg' => __('Invalid cart key')
            ], 400);
        }

        $result = CartHelper::removeItem($cartKey);

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'msg' => $result['message']
                ], 400);
            }
            return back()->with('unsuccess', $result['message']);
        }

        $cart = $result['cart'];

        if ($request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => 'success',
                'success' => $result['message'],
                'cart_count' => CartHelper::getItemCount(),
                'cart_total' => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
                'totalQty' => $cart['totalQty'],
                'totalPrice' => $cart['totalPrice']
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * V2: ملخص السلة
     */
    public function v2Summary()
    {
        $cart = CartHelper::getCart();

        return response()->json([
            'ok' => true,
            'cart_count' => CartHelper::getItemCount(),
            'cart_total' => \PriceHelper::showCurrencyPrice($cart['totalPrice'] * $this->curr->value),
            'totalQty' => $cart['totalQty'],
            'totalPrice' => $cart['totalPrice'],
            'items' => $cart['items']
        ]);
    }

    /**
     * V2: صفحة السلة (view)
     */
    public function v2CartPage(Request $request)
    {
        if (!CartHelper::hasCart()) {
            return view('frontend.cart');
        }

        // مسح بيانات الكوبون القديمة
        foreach (['already', 'coupon', 'coupon_total', 'coupon_total1', 'coupon_percentage'] as $k) {
            if (Session::has($k)) Session::forget($k);
        }

        $cart = CartHelper::getCart();
        $products = $cart['items'];
        $totalPrice = $cart['totalPrice'];
        $mainTotal = $totalPrice;
        $productsByVendor = CartHelper::groupByVendor();

        if ($request->ajax()) {
            return view('frontend.ajax.cart-page-v2', compact('products', 'totalPrice', 'mainTotal', 'productsByVendor'));
        }

        return view('frontend.cart', compact('products', 'totalPrice', 'mainTotal', 'productsByVendor'));
    }
}
