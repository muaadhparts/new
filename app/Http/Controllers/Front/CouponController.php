<?php

namespace App\Http\Controllers\Front;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Session;

class CouponController extends FrontBaseController
{
    /**
     * استخراج vendor_id من عنصر السلة
     * هيكل السلة: $cart->items[$key] = [
     *    'user_id' => int,           // vendor_id مخزن مباشرة
     *    'merchant_product_id' => int,
     *    'item' => Product object,   // كائن المنتج مع user_id/vendor_user_id
     *    'qty' => int,
     *    'price' => float,
     *    ...
     * ]
     */
    private function getItemVendorId($item)
    {
        // الأولوية 1: user_id المخزن مباشرة في صف السلة (الطريقة الصحيحة)
        if (isset($item['user_id']) && $item['user_id']) {
            return (int) $item['user_id'];
        }

        // الأولوية 2: من كائن المنتج المخزن
        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                // كائن Product مع vendor_user_id أو user_id
                return (int) ($itemData->vendor_user_id ?? $itemData->user_id ?? 0);
            }
            if (is_array($itemData)) {
                return (int) ($itemData['vendor_user_id'] ?? $itemData['user_id'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * استخراج product_id من عنصر السلة
     */
    private function getItemProductId($item)
    {
        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                return (int) ($itemData->id ?? 0);
            }
            if (is_array($itemData)) {
                return (int) ($itemData['id'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * التحقق من أن المنتج يطابق شرط الفئة في الكوبون
     */
    private function productMatchesCouponCategory($product, $coupon)
    {
        if ($coupon->coupon_type == 'category') {
            return $product->category_id == $coupon->category;
        } elseif ($coupon->coupon_type == 'sub_category') {
            return $product->subcategory_id == $coupon->sub_category;
        } elseif ($coupon->coupon_type == 'child_category') {
            return $product->childcategory_id == $coupon->child_category;
        }
        return false;
    }

    /**
     * حساب إجمالي المنتجات المؤهلة للكوبون
     * - إذا كان الكوبون خاص بتاجر: يحسب فقط منتجات هذا التاجر التي تطابق الفئة
     * - إذا كان كوبون عام (Admin): يحسب جميع المنتجات التي تطابق الفئة
     */
    private function calculateEligibleTotal($cart, $coupon)
    {
        $eligibleTotal = 0;
        $eligibleItems = [];

        foreach ($cart->items as $key => $item) {
            $itemVendorId = $this->getItemVendorId($item);
            $productId = $this->getItemProductId($item);

            // إذا كان الكوبون خاص بتاجر، تحقق من أن المنتج يخص هذا التاجر
            if ($coupon->user_id && $itemVendorId != $coupon->user_id) {
                continue; // تخطي المنتجات التي لا تخص التاجر
            }

            // جلب بيانات المنتج للتحقق من الفئة
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            // التحقق من أن المنتج يطابق شرط الفئة
            if ($this->productMatchesCouponCategory($product, $coupon)) {
                $eligibleTotal += (float) ($item['price'] ?? 0);
                $eligibleItems[] = $key;
            }
        }

        return [
            'total' => $eligibleTotal,
            'items' => $eligibleItems
        ];
    }

    /**
     * التحقق من صلاحية الكوبون (التاريخ، الحالة، عدد الاستخدامات)
     */
    private function validateCoupon($coupon)
    {
        // التحقق من حالة الكوبون
        if ($coupon->status != 1) {
            return ['valid' => false, 'error' => 'inactive'];
        }

        // التحقق من عدد الاستخدامات
        if ($coupon->times !== null && $coupon->times == "0") {
            return ['valid' => false, 'error' => 'exhausted'];
        }

        // التحقق من التاريخ
        $today = date('Y-m-d');
        $from = date('Y-m-d', strtotime($coupon->start_date));
        $to = date('Y-m-d', strtotime($coupon->end_date));

        \Log::info('Coupon date validation', [
            'today' => $today,
            'start_date' => $coupon->start_date,
            'end_date' => $coupon->end_date,
            'from_parsed' => $from,
            'to_parsed' => $to,
            'is_started' => $from <= $today,
            'is_not_expired' => $to >= $today
        ]);

        if ($from > $today || $to < $today) {
            return ['valid' => false, 'error' => 'expired_or_not_started', 'details' => [
                'today' => $today,
                'from' => $from,
                'to' => $to
            ]];
        }

        return ['valid' => true];
    }

    /**
     * تطبيق الكوبون القديم (للتوافق مع الكود القديم)
     * يُستخدم عندما يكون الكوبون يُطبق على كامل السلة
     */
    public function coupon()
    {
        $code = $_GET['code'] ?? '';
        $cartTotal = (float) preg_replace('/[^0-9\.]/ui', '', $_GET['total'] ?? 0);

        $coupon = Coupon::where('code', '=', $code)->first();

        if (!$coupon) {
            return response()->json(0);
        }

        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return response()->json(0);
        }

        $curr = $this->curr;

        // حساب المنتجات المؤهلة للخصم
        $eligible = $this->calculateEligibleTotal($cart, $coupon);

        if (empty($eligible['items'])) {
            // لا توجد منتجات مؤهلة للكوبون
            return response()->json(0);
        }

        // التحقق من صلاحية الكوبون
        $validation = $this->validateCoupon($coupon);
        if (!$validation['valid']) {
            return response()->json(0);
        }

        // التحقق من عدم استخدام الكوبون مسبقاً
        $alreadyUsed = Session::get('already');
        if ($alreadyUsed == $code) {
            return response()->json(2); // الكوبون مستخدم مسبقاً
        }

        // المبلغ المؤهل للخصم
        $eligibleAmount = $eligible['total'];

        // حساب الخصم
        if ($coupon->type == 0) {
            // خصم بالنسبة المئوية
            $discountPercent = (int) $coupon->price;

            if ($discountPercent >= 100) {
                return response()->json(3); // الخصم أكبر من المبلغ
            }

            $discountAmount = ($eligibleAmount * $discountPercent) / 100;
            $newTotal = $cartTotal - $discountAmount;

            Session::put('already', $code);
            Session::put('coupon', round($discountAmount, 2));
            Session::put('coupon_code', $code);
            Session::put('coupon_id', $coupon->id);
            Session::put('coupon_total', \PriceHelper::showCurrencyPrice($newTotal));
            Session::put('coupon_percentage', $discountPercent . "%");

            // حفظ معرف التاجر إذا كان الكوبون خاص بتاجر
            if ($coupon->user_id) {
                Session::put('coupon_vendor_id', $coupon->user_id);
            }

            $data[0] = \PriceHelper::showCurrencyPrice($newTotal);
            $data[1] = $code;
            $data[2] = round($discountAmount, 2);
            $data[3] = $coupon->id;
            $data[4] = $discountPercent . "%";
            $data[5] = 1;

            return response()->json($data);
        } else {
            // خصم بمبلغ ثابت
            $discountAmount = round($coupon->price * $curr->value, 2);

            if ($discountAmount >= $eligibleAmount) {
                return response()->json(3); // الخصم أكبر من المبلغ
            }

            $newTotal = $cartTotal - $discountAmount;

            Session::put('already', $code);
            Session::put('coupon', $discountAmount);
            Session::put('coupon_code', $code);
            Session::put('coupon_id', $coupon->id);
            Session::put('coupon_total', $newTotal);
            Session::put('coupon_percentage', 0);

            // حفظ معرف التاجر إذا كان الكوبون خاص بتاجر
            if ($coupon->user_id) {
                Session::put('coupon_vendor_id', $coupon->user_id);
            }

            $data[0] = \PriceHelper::showCurrencyPrice($newTotal);
            $data[1] = $code;
            $data[2] = $discountAmount;
            $data[3] = $coupon->id;
            $data[4] = \PriceHelper::showCurrencyPrice($discountAmount);
            $data[5] = 1;

            return response()->json($data);
        }
    }

    /**
     * تطبيق الكوبون مع التحقق من الفئات
     * يُستخدم للكوبونات المحدودة بفئة معينة
     * يدعم كلاً من Checkout العادي و Vendor Checkout
     */
    public function couponcheck(Request $request)
    {
        $code = $_GET['code'] ?? '';
        $requestTotal = (float) ($request->total ?? 0);

        $coupon = Coupon::where('code', '=', $code)->first();

        if (!$coupon) {
            return response()->json(0);
        }

        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return response()->json(0);
        }

        $curr = $this->curr;

        // التحقق إذا كان vendor checkout
        $checkoutVendorId = Session::get('checkout_vendor_id');
        $isVendorCheckout = !empty($checkoutVendorId);

        // تحويل إلى integer للمقارنة الصحيحة
        $checkoutVendorId = $checkoutVendorId ? (int) $checkoutVendorId : null;

        // Log للتصحيح
        \Log::info('Coupon Check Debug', [
            'code' => $code,
            'coupon_id' => $coupon->id,
            'coupon_user_id' => $coupon->user_id,
            'checkout_vendor_id' => $checkoutVendorId,
            'is_vendor_checkout' => $isVendorCheckout,
            'coupon_type' => $coupon->coupon_type,
            'coupon_category' => $coupon->category,
        ]);

        // إذا كان vendor checkout وكوبون تاجر، تأكد أنهما نفس التاجر
        if ($isVendorCheckout && $coupon->user_id && (int)$coupon->user_id != $checkoutVendorId) {
            \Log::info('Coupon rejected: vendor mismatch', [
                'coupon_user_id' => $coupon->user_id,
                'checkout_vendor_id' => $checkoutVendorId
            ]);
            // الكوبون لتاجر آخر غير الذي نشتري منه
            return response()->json(0);
        }

        // حساب المنتجات المؤهلة للخصم
        // في vendor checkout: نحسب فقط منتجات التاجر المحدد
        $eligible = $this->calculateEligibleTotalForCheckout($cart, $coupon, $checkoutVendorId);

        if (empty($eligible['items'])) {
            // لا توجد منتجات مؤهلة للكوبون
            return response()->json(0);
        }

        // التحقق من صلاحية الكوبون
        $validation = $this->validateCoupon($coupon);
        \Log::info('Coupon validation result', $validation);
        if (!$validation['valid']) {
            \Log::info('Coupon rejected: validation failed', ['error' => $validation['error'] ?? 'unknown']);
            return response()->json(0);
        }

        // التحقق من عدم استخدام الكوبون مسبقاً
        $alreadyKey = $isVendorCheckout ? 'already_vendor_' . $checkoutVendorId : 'already';
        $alreadyUsed = Session::get($alreadyKey);
        \Log::info('Checking if coupon already used', ['alreadyKey' => $alreadyKey, 'alreadyUsed' => $alreadyUsed, 'code' => $code]);
        if ($alreadyUsed == $code) {
            \Log::info('Coupon rejected: already used');
            return response()->json(2); // الكوبون مستخدم مسبقاً
        }

        // المبلغ المؤهل للخصم
        $eligibleAmount = $eligible['total'];
        \Log::info('Proceeding to apply discount', ['eligibleAmount' => $eligibleAmount, 'requestTotal' => $requestTotal]);

        // حساب الخصم وحفظه
        return $this->applyDiscount($coupon, $eligibleAmount, $requestTotal, $code, $isVendorCheckout, $checkoutVendorId);
    }

    /**
     * حساب المنتجات المؤهلة للكوبون مع دعم vendor checkout
     */
    private function calculateEligibleTotalForCheckout($cart, $coupon, $checkoutVendorId = null)
    {
        $eligibleTotal = 0;
        $eligibleItems = [];

        \Log::info('calculateEligibleTotalForCheckout: Starting', [
            'checkoutVendorId' => $checkoutVendorId,
            'coupon_user_id' => $coupon->user_id,
            'coupon_type' => $coupon->coupon_type,
            'coupon_category' => $coupon->category,
            'cart_items_count' => count($cart->items ?? [])
        ]);

        foreach ($cart->items as $key => $item) {
            $itemVendorId = $this->getItemVendorId($item);
            $productId = $this->getItemProductId($item);

            \Log::info('Processing cart item', [
                'key' => $key,
                'itemVendorId' => $itemVendorId,
                'productId' => $productId,
                'item_price' => $item['price'] ?? 0
            ]);

            // في vendor checkout: تخطي منتجات التجار الآخرين
            if ($checkoutVendorId && $itemVendorId != $checkoutVendorId) {
                \Log::info('Skipping: not checkout vendor', ['itemVendorId' => $itemVendorId, 'checkoutVendorId' => $checkoutVendorId]);
                continue;
            }

            // إذا كان الكوبون خاص بتاجر، تحقق من أن المنتج يخص هذا التاجر
            if ($coupon->user_id && $itemVendorId != (int)$coupon->user_id) {
                \Log::info('Skipping: not coupon vendor', ['itemVendorId' => $itemVendorId, 'coupon_user_id' => $coupon->user_id]);
                continue;
            }

            // جلب بيانات المنتج للتحقق من الفئة
            $product = Product::find($productId);
            if (!$product) {
                \Log::info('Skipping: product not found', ['productId' => $productId]);
                continue;
            }

            \Log::info('Product found', [
                'productId' => $productId,
                'product_category_id' => $product->category_id,
                'product_subcategory_id' => $product->subcategory_id,
                'product_childcategory_id' => $product->childcategory_id
            ]);

            // التحقق من أن المنتج يطابق شرط الفئة
            $matches = $this->productMatchesCouponCategory($product, $coupon);
            \Log::info('Category match result', ['matches' => $matches]);

            if ($matches) {
                $eligibleTotal += (float) ($item['price'] ?? 0);
                $eligibleItems[] = $key;
                \Log::info('Item is eligible', ['key' => $key, 'price' => $item['price'] ?? 0]);
            }
        }

        \Log::info('calculateEligibleTotalForCheckout: Result', [
            'eligibleTotal' => $eligibleTotal,
            'eligibleItemsCount' => count($eligibleItems)
        ]);

        return [
            'total' => $eligibleTotal,
            'items' => $eligibleItems
        ];
    }

    /**
     * تطبيق الخصم وحفظه في Session
     */
    private function applyDiscount($coupon, $eligibleAmount, $requestTotal, $code, $isVendorCheckout, $checkoutVendorId)
    {
        $curr = $this->curr;

        if ($coupon->type == 0) {
            // خصم بالنسبة المئوية
            $discountPercent = (int) $coupon->price;

            if ($discountPercent >= 100) {
                return response()->json(3);
            }

            $discountAmount = ($eligibleAmount * $discountPercent) / 100;
            $newTotal = $requestTotal - $discountAmount;

            $this->saveCouponToSession($code, $coupon, round($discountAmount, 2), round($newTotal, 2), $discountPercent . "%", $isVendorCheckout, $checkoutVendorId);

            $data[0] = \PriceHelper::showCurrencyPrice($newTotal);
            $data[1] = $code;
            $data[2] = round($discountAmount, 2);
            $data[3] = $coupon->id;
            $data[4] = $discountPercent . "%";
            $data[5] = 1;
            $data[6] = round($newTotal, 2);

            return response()->json($data);
        } else {
            // خصم بمبلغ ثابت
            $discountAmount = round($coupon->price * $curr->value, 2);

            if ($discountAmount >= $eligibleAmount) {
                return response()->json(3);
            }

            $newTotal = $requestTotal - $discountAmount;

            $this->saveCouponToSession($code, $coupon, $discountAmount, round($newTotal, 2), 0, $isVendorCheckout, $checkoutVendorId);

            $data[0] = \PriceHelper::showCurrencyPrice($newTotal);
            $data[1] = $code;
            $data[2] = $discountAmount;
            $data[3] = $coupon->id;
            $data[4] = \PriceHelper::showCurrencyPrice($discountAmount);
            $data[5] = 1;
            $data[6] = round($newTotal, 2);

            return response()->json($data);
        }
    }

    /**
     * حفظ بيانات الكوبون في Session
     * يدعم كلاً من Checkout العادي و Vendor Checkout
     */
    private function saveCouponToSession($code, $coupon, $discountAmount, $newTotal, $percentage, $isVendorCheckout, $checkoutVendorId)
    {
        if ($isVendorCheckout && $checkoutVendorId) {
            // Vendor Checkout - حفظ بمفتاح خاص بالتاجر
            Session::put('already_vendor_' . $checkoutVendorId, $code);
            Session::put('coupon_vendor_' . $checkoutVendorId, $discountAmount);
            Session::put('coupon_code_vendor_' . $checkoutVendorId, $code);
            Session::put('coupon_id_vendor_' . $checkoutVendorId, $coupon->id);
            Session::put('coupon_total_vendor_' . $checkoutVendorId, $newTotal);
            Session::put('coupon_percentage_vendor_' . $checkoutVendorId, $percentage);
        } else {
            // Checkout العادي - حفظ بالمفاتيح العامة
            Session::put('already', $code);
            Session::put('coupon', $discountAmount);
            Session::put('coupon_code', $code);
            Session::put('coupon_id', $coupon->id);
            Session::put('coupon_total1', $newTotal);
            Session::forget('coupon_total');
            Session::put('coupon_percentage', $percentage);

            // حفظ معرف التاجر إذا كان الكوبون خاص بتاجر
            if ($coupon->user_id) {
                Session::put('coupon_vendor_id', $coupon->user_id);
            }
        }
    }

    /**
     * إلغاء/حذف الكوبون المطبق
     * يدعم كلاً من Checkout العادي و Vendor Checkout
     */
    public function removeCoupon(Request $request)
    {
        $vendorId = $request->vendor_id ?? Session::get('checkout_vendor_id');
        $isVendorCheckout = $request->is_vendor_checkout == 1 || !empty($vendorId);

        // حفظ قيمة الخصم قبل الحذف لحساب السعر الأصلي
        $couponAmount = 0;
        if ($isVendorCheckout && $vendorId) {
            $couponAmount = Session::get('coupon_vendor_' . $vendorId, 0);
        } else {
            $couponAmount = Session::get('coupon', 0);
        }

        // جلب الإجمالي الحالي من step2 session
        $step2 = Session::get('step2');
        $currentTotal = $step2->final_total ?? $step2->total ?? 0;

        // حساب السعر الأصلي (قبل الخصم)
        $originalTotal = $currentTotal + $couponAmount;

        \Log::info('Removing coupon', [
            'vendor_id' => $vendorId,
            'is_vendor_checkout' => $isVendorCheckout,
            'coupon_amount' => $couponAmount,
            'current_total' => $currentTotal,
            'original_total' => $originalTotal
        ]);

        if ($isVendorCheckout && $vendorId) {
            // إلغاء كوبون Vendor Checkout
            Session::forget('already_vendor_' . $vendorId);
            Session::forget('coupon_vendor_' . $vendorId);
            Session::forget('coupon_code_vendor_' . $vendorId);
            Session::forget('coupon_id_vendor_' . $vendorId);
            Session::forget('coupon_total_vendor_' . $vendorId);
            Session::forget('coupon_percentage_vendor_' . $vendorId);
        } else {
            // إلغاء كوبون Checkout العادي
            Session::forget('already');
            Session::forget('coupon');
            Session::forget('coupon_code');
            Session::forget('coupon_id');
            Session::forget('coupon_total');
            Session::forget('coupon_total1');
            Session::forget('coupon_percentage');
            Session::forget('coupon_vendor_id');
        }

        return response()->json([
            'success' => true,
            'message' => __('Coupon removed successfully'),
            'original_total' => round($originalTotal, 2)
        ]);
    }
}
