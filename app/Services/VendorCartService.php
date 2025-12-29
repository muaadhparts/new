<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * خدمة موحدة لإدارة سلة التاجر
 *
 * المبدأ الأساسي: كل تاجر = سلة مستقلة 100%
 *
 * هذه الخدمة هي المصدر الوحيد لـ:
 * - حساب سعر المنتج
 * - حساب خصم الجملة
 * - حساب الوزن والأبعاد
 * - حساب الشحن
 * - تطبيق الكوبون (حسب التاجر)
 */
class VendorCartService
{
    /**
     * ======================================
     * حساب خصم الجملة (Bulk Discount)
     * المصدر الوحيد - يُستدعى من كل مكان
     * ======================================
     */
    public static function calculateBulkDiscount(int $mpId, int $qty): array
    {
        $mp = MerchantProduct::find($mpId);

        if (!$mp) {
            return [
                'discount_percent' => 0,
                'unit_price' => 0,
                'discounted_price' => 0,
                'total_price' => 0,
                'has_discount' => false,
            ];
        }

        $unitPrice = (float) $mp->vendorSizePrice();
        $discountPercent = 0;

        // جلب خصومات الجملة من MerchantProduct
        $wsQty = self::toArray($mp->whole_sell_qty);
        $wsDiscount = self::toArray($mp->whole_sell_discount);

        if (!empty($wsQty) && !empty($wsDiscount) && count($wsQty) === count($wsDiscount)) {
            // ترتيب تصاعدي حسب الكمية
            $combined = array_combine($wsQty, $wsDiscount);
            ksort($combined, SORT_NUMERIC);

            // البحث عن أعلى مستوى خصم ينطبق
            foreach ($combined as $threshold => $discount) {
                if ($qty >= (int) $threshold) {
                    $discountPercent = (float) $discount;
                }
            }
        }

        $discountedPrice = $unitPrice;
        if ($discountPercent > 0) {
            $discountedPrice = $unitPrice * (1 - $discountPercent / 100);
        }

        $totalPrice = $discountedPrice * $qty;

        return [
            'discount_percent' => $discountPercent,
            'unit_price' => round($unitPrice, 2),
            'discounted_price' => round($discountedPrice, 2),
            'total_price' => round($totalPrice, 2),
            'has_discount' => $discountPercent > 0,
        ];
    }

    /**
     * ======================================
     * حساب الوزن والأبعاد للمنتج
     * المصدر الوحيد - بدون قيم ثابتة
     * ======================================
     *
     * الوزن مطلوب (من products.weight أو merchant_products.weight)
     * المقاسات اختيارية - تستخدم لحساب الوزن الحجمي إذا توفرت
     */
    public static function getProductDimensions(int $mpId): array
    {
        $mp = MerchantProduct::with('product')->find($mpId);

        if (!$mp || !$mp->product) {
            return [
                'weight' => null,
                'length' => null,
                'width' => null,
                'height' => null,
                'has_weight' => false,
                'has_dimensions' => false,
                'is_complete' => false,
                'missing_fields' => ['weight'],
            ];
        }

        $product = $mp->product;

        // الأولوية: merchant_products ثم products
        // بدون أي fallback لقيم ثابتة
        $weight = $mp->weight ?? $product->weight ?? null;
        $length = $mp->length ?? $product->length ?? null;
        $width = $mp->width ?? $product->width ?? null;
        $height = $mp->height ?? $product->height ?? null;

        // الوزن مطلوب فقط - المقاسات اختيارية
        $hasWeight = $weight !== null && $weight > 0;
        $hasDimensions = ($length !== null && $length > 0)
                      && ($width !== null && $width > 0)
                      && ($height !== null && $height > 0);

        $missingFields = [];
        if (!$hasWeight) $missingFields[] = 'weight';

        return [
            'weight' => $hasWeight ? (float) $weight : null,
            'length' => ($length !== null && $length > 0) ? (float) $length : null,
            'width' => ($width !== null && $width > 0) ? (float) $width : null,
            'height' => ($height !== null && $height > 0) ? (float) $height : null,
            'has_weight' => $hasWeight,
            'has_dimensions' => $hasDimensions,
            'is_complete' => $hasWeight, // الوزن كافي للشحن
            'missing_fields' => $missingFields,
        ];
    }

    /**
     * ======================================
     * حساب ملخص الشحن لتاجر واحد
     * المصدر الوحيد - بدون قيم ثابتة
     * ======================================
     *
     * الوزن مطلوب - المقاسات اختيارية
     * الوزن = وزن القطعة × الكمية
     */
    public static function calculateVendorShipping(int $vendorId, array $cartItems): array
    {
        $totalActualWeight = 0;
        $maxLength = null;
        $maxWidth = null;
        $totalHeight = 0;
        $itemsCount = 0;
        $totalQty = 0;
        $subtotal = 0;
        $missingData = [];
        $hasCompleteData = true;
        $hasDimensionsData = true;

        foreach ($cartItems as $cartKey => $item) {
            $itemVendorId = self::extractVendorId($item);
            if ((int) $itemVendorId !== (int) $vendorId) {
                continue;
            }

            // البحث عن merchant_product_id في مستويات متعددة
            $mpId = $item['merchant_product_id']
                ?? data_get($item, 'item.merchant_product_id')
                ?? 0;

            $qty = (int) ($item['qty'] ?? 1);
            $dimensions = self::getProductDimensions($mpId);

            $itemsCount++;
            $totalQty += $qty;
            $subtotal += (float) ($item['price'] ?? $item['total_price'] ?? 0);

            // ✅ الوزن = وزن القطعة × الكمية
            if ($dimensions['has_weight']) {
                $totalActualWeight += $dimensions['weight'] * $qty;
            } else {
                $hasCompleteData = false;
                $missingData[] = "Item {$cartKey}: missing weight";
            }

            // الأبعاد اختيارية - تستخدم لحساب الوزن الحجمي
            if ($dimensions['has_dimensions']) {
                $maxLength = $maxLength === null ? $dimensions['length'] : max($maxLength, $dimensions['length']);
                $maxWidth = $maxWidth === null ? $dimensions['width'] : max($maxWidth, $dimensions['width']);
                $totalHeight += $dimensions['height'] * $qty;
            } else {
                $hasDimensionsData = false;
            }
        }

        // ✅ حساب الوزن الحجمي فقط إذا توفرت المقاسات
        $volumetricWeight = null;
        if ($hasDimensionsData && $maxLength !== null && $maxWidth !== null && $totalHeight > 0) {
            $volumetricWeight = ($maxLength * $maxWidth * $totalHeight) / 5000;
        }

        // ✅ الوزن القابل للشحن = الأعلى بين الوزن الفعلي والحجمي
        // إذا لم تتوفر المقاسات، نستخدم الوزن الفعلي فقط
        $chargeableWeight = null;
        if ($totalActualWeight > 0) {
            $chargeableWeight = $totalActualWeight;
            if ($volumetricWeight !== null && $volumetricWeight > $totalActualWeight) {
                $chargeableWeight = $volumetricWeight;
            }
        }

        // جلب مدينة التاجر من قاعدة البيانات
        $vendor = User::find($vendorId);
        $vendorCityId = $vendor->city_id ?? null;
        $vendorCity = null;

        if ($vendorCityId) {
            $city = \App\Models\City::find($vendorCityId);
            $vendorCity = $city->city_name ?? $city->name ?? null;
        }

        if (!$vendorCity) {
            $hasCompleteData = false;
            $missingData[] = "Vendor {$vendorId}: missing city";
        }

        return [
            'vendor_id' => $vendorId,
            'vendor_name' => $vendor->shop_name ?? $vendor->name ?? null,
            'vendor_city_id' => $vendorCityId,
            'vendor_city' => $vendorCity,
            'items_count' => $itemsCount,
            'total_qty' => $totalQty,
            'subtotal' => round($subtotal, 2),

            // الوزن
            'actual_weight' => $totalActualWeight > 0 ? round($totalActualWeight, 3) : null,
            'volumetric_weight' => $volumetricWeight !== null ? round($volumetricWeight, 3) : null,
            'chargeable_weight' => $chargeableWeight !== null ? round($chargeableWeight, 3) : null,

            // الأبعاد (اختيارية)
            'dimensions' => [
                'length' => $maxLength !== null ? round($maxLength, 2) : null,
                'width' => $maxWidth !== null ? round($maxWidth, 2) : null,
                'height' => $totalHeight > 0 ? round($totalHeight, 2) : null,
            ],
            'has_dimensions' => $hasDimensionsData,

            // ✅ حالة البيانات - الوزن كافي
            'has_complete_data' => $hasCompleteData,
            'missing_data' => $missingData,
        ];
    }

    /**
     * ======================================
     * التحقق من صلاحية كود الخصم للتاجر
     * كود الخصم يعمل فقط على منتجات تاجره
     * ======================================
     */
    public static function validateDiscountCodeForVendor(string $code, int $vendorId, float $subtotal): array
    {
        $discountCode = DiscountCode::where('code', $code)
            ->where('status', 1)
            ->first();

        if (!$discountCode) {
            return [
                'valid' => false,
                'error' => 'invalid_code',
                'message' => __('Invalid discount code'),
            ];
        }

        // التحقق من أن كود الخصم للتاجر المحدد
        if ($discountCode->user_id && (int) $discountCode->user_id !== $vendorId) {
            return [
                'valid' => false,
                'error' => 'wrong_vendor',
                'message' => __('This discount code is not valid for this vendor'),
            ];
        }

        // التحقق من الصلاحية الزمنية
        $now = now();
        if ($discountCode->start_date && $now->lt($discountCode->start_date)) {
            return [
                'valid' => false,
                'error' => 'not_started',
                'message' => __('This discount code is not active yet'),
            ];
        }

        if ($discountCode->end_date && $now->gt($discountCode->end_date)) {
            return [
                'valid' => false,
                'error' => 'expired',
                'message' => __('This discount code has expired'),
            ];
        }

        // التحقق من الحد الأدنى للطلب
        if ($discountCode->min_order && $subtotal < (float) $discountCode->min_order) {
            return [
                'valid' => false,
                'error' => 'min_order',
                'message' => __('Minimum order amount is') . ' ' . $discountCode->min_order,
            ];
        }

        // التحقق من عدد الاستخدامات
        if ($discountCode->used >= $discountCode->times) {
            return [
                'valid' => false,
                'error' => 'max_uses',
                'message' => __('This discount code has reached its usage limit'),
            ];
        }

        // حساب قيمة الخصم
        $discountAmount = 0;
        if ($discountCode->type == 0) {
            // خصم بالنسبة المئوية
            $discountAmount = ($subtotal * (float) $discountCode->price) / 100;
        } else {
            // خصم مبلغ ثابت
            $discountAmount = (float) $discountCode->price;
        }

        // التأكد أن الخصم لا يتجاوز المجموع
        $discountAmount = min($discountAmount, $subtotal);

        return [
            'valid' => true,
            'discount_code_id' => $discountCode->id,
            'discount_code' => $discountCode->code,
            'vendor_id' => $discountCode->user_id,
            'discount_type' => $discountCode->type == 0 ? 'percentage' : 'fixed',
            'discount_value' => (float) $discountCode->price,
            'discount_amount' => round($discountAmount, 2),
            'final_subtotal' => round($subtotal - $discountAmount, 2),
        ];
    }

    /**
     * ======================================
     * بناء ملخص سلة التاجر الكامل
     * ======================================
     */
    public static function buildVendorCartSummary(int $vendorId, array $cartItems, ?string $discountCodeValue = null): array
    {
        $vendorItems = [];
        $itemsDetails = [];

        foreach ($cartItems as $cartKey => $item) {
            $itemVendorId = self::extractVendorId($item);
            if ((int) $itemVendorId !== (int) $vendorId) {
                continue;
            }

            $mpId = $item['merchant_product_id'] ?? 0;
            $qty = (int) ($item['qty'] ?? 1);

            // حساب الخصم الموحد
            $bulkDiscount = self::calculateBulkDiscount($mpId, $qty);

            // حساب الأبعاد
            $dimensions = self::getProductDimensions($mpId);

            $itemsDetails[$cartKey] = [
                'merchant_product_id' => $mpId,
                'qty' => $qty,
                'bulk_discount' => $bulkDiscount,
                'dimensions' => $dimensions,
                'row_weight' => $dimensions['weight'] !== null ? $dimensions['weight'] * $qty : null,
            ];

            $vendorItems[$cartKey] = $item;
        }

        // حساب الشحن
        $shippingData = self::calculateVendorShipping($vendorId, $cartItems);

        // المجموع الفرعي (بعد خصم الجملة)
        $subtotal = 0;
        foreach ($itemsDetails as $detail) {
            $subtotal += $detail['bulk_discount']['total_price'];
        }

        // تطبيق كود الخصم إن وجد
        $discountData = null;
        $finalSubtotal = $subtotal;

        if ($discountCodeValue) {
            $discountData = self::validateDiscountCodeForVendor($discountCodeValue, $vendorId, $subtotal);
            if ($discountData['valid']) {
                $finalSubtotal = $discountData['final_subtotal'];
            }
        }

        return [
            'vendor_id' => $vendorId,
            'vendor_name' => $shippingData['vendor_name'],
            'vendor_city' => $shippingData['vendor_city'],

            'items' => $vendorItems,
            'items_details' => $itemsDetails,
            'items_count' => count($vendorItems),
            'total_qty' => $shippingData['total_qty'],

            'subtotal_before_discount' => round($subtotal, 2),
            'discount_data' => $discountData,
            'subtotal' => round($finalSubtotal, 2),

            'shipping' => $shippingData,

            'has_complete_data' => $shippingData['has_complete_data'],
            'missing_data' => $shippingData['missing_data'],
        ];
    }

    /**
     * ======================================
     * تجميع السلة حسب التجار
     * كل تاجر = كيان مستقل
     * ======================================
     */
    public static function groupCartByVendor(?Cart $cart = null): array
    {
        if (!$cart && Session::has('cart')) {
            $cart = Session::get('cart');
        }

        if (!$cart || empty($cart->items)) {
            return [];
        }

        $grouped = [];

        foreach ($cart->items as $cartKey => $item) {
            $vendorId = self::extractVendorId($item);

            if (!isset($grouped[$vendorId])) {
                $vendor = User::find($vendorId);
                $grouped[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor->shop_name ?? $vendor->name ?? null,
                    'vendor_city_id' => $vendor->city_id ?? null,
                    'items' => [],
                ];
            }

            $grouped[$vendorId]['items'][$cartKey] = $item;
        }

        // بناء الملخص لكل تاجر
        foreach ($grouped as $vendorId => &$vendorData) {
            $summary = self::buildVendorCartSummary($vendorId, $cart->items);
            $vendorData = array_merge($vendorData, $summary);
        }

        return $grouped;
    }

    /**
     * ======================================
     * Helper: استخراج vendor_id من عنصر السلة
     * ======================================
     */
    private static function extractVendorId(array $item): int
    {
        // الأولوية للـ user_id في المستوى الأول
        if (isset($item['user_id']) && $item['user_id']) {
            return (int) $item['user_id'];
        }

        // ثم من item object/array
        $innerItem = $item['item'] ?? null;
        if ($innerItem) {
            if (is_object($innerItem)) {
                return (int) ($innerItem->user_id ?? $innerItem->vendor_user_id ?? 0);
            }
            if (is_array($innerItem)) {
                return (int) ($innerItem['user_id'] ?? $innerItem['vendor_user_id'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * ======================================
     * Helper: تحويل string إلى array
     * ======================================
     */
    private static function toArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            return array_map('trim', explode(',', $value));
        }
        return [];
    }
}
