<?php

namespace App\Helpers;

use App\Models\Country;
use App\Models\MonetaryUnit;
use App\Models\Package;
use App\Models\Shipping;
use DB;
use Session;

class PriceHelper
{

    public static function showPrice($price)
    {
        $gs = cache()->remember('muaadhsettings', now()->addDay(), function () {
            return DB::table('muaadhsettings')->first();
        });
        if (is_numeric($price) && floor($price) != $price) {
            return number_format($price, 2, $gs->decimal_separator, $gs->thousand_separator);
        } else {
            return number_format($price, 0, $gs->decimal_separator, $gs->thousand_separator);
        }
    }

    public static function apishowPrice($price)
    {
        $gs = cache()->remember('muaadhsettings', now()->addDay(), function () {
            return DB::table('muaadhsettings')->first();
        });
        if (is_numeric($price) && floor($price) != $price) {
            return round($price, 2);
        } else {
            return round($price, 0);
        }
    }

    public static function showCurrencyPrice($price)
    {
        // Use centralized MonetaryUnitService
        return monetaryUnit()->format((float) $price);
    }

    public static function showAdminCurrencyPrice($price)
    {
        // Use centralized MonetaryUnitService with base/default currency
        return monetaryUnit()->formatBase((float) $price);
    }

    public static function showOrderCurrencyPrice($price, $currency)
    {
        // Use centralized MonetaryUnitService with custom sign
        return monetaryUnit()->formatWith((float) $price, $currency);
    }

    public static function ImageCreateName($image)
    {
        $name = time() . preg_replace('/[^A-Za-z0-9\-]/', '', $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
        return $name;
    }

    public static function getPurchaseTotal($input, $cart)
    {
        try {
            // اجمع merchant_ids من محتوى السلة
            $merchant_ids = [];
            foreach ($cart->items as $item) {
                if (!in_array($item['item']['user_id'], $merchant_ids)) {
                    $merchant_ids[] = $item['item']['user_id'];
                }
            }

            $gs = DB::table('muaadhsettings')->first();

            $totalAmount = (float) $cart->totalPrice;

            // الضريبة
            $tax_amount = 0.0;
            if (!empty($input['tax']) && !empty($input['tax_type'])) {
                // States removed - tax only from Country now
                $tax = (float) Country::findOrFail($input['tax'])->tax;
                $tax_amount  = ($totalAmount * $tax) / 100.0;
                $totalAmount += $tax_amount;
            }

            // شحن مفرد
            if ((int)$gs->multiple_shipping === 0) {
                $merchant_shipping_ids = [];
                $merchant_packing_ids  = [];

                foreach ($merchant_ids as $merchant_id) {
                    $merchant_shipping_ids[$merchant_id] = (!empty($input['shipping_id']) && (int)$input['shipping_id'] !== 0) ? (int)$input['shipping_id'] : null;
                    $merchant_packing_ids[$merchant_id]  = (!empty($input['packaging_id']) && (int)$input['packaging_id'] !== 0) ? (int)$input['packaging_id'] : null;
                }

                $shipping = (!empty($input['shipping_id']) && (int)$input['shipping_id'] !== 0)
                    ? Shipping::find((int)$input['shipping_id']) : null;

                $packeing = (!empty($input['packaging_id']) && (int)$input['packaging_id'] !== 0)
                    ? Package::find((int)$input['packaging_id']) : null;

                $shipping_cost = $shipping ? (float)$shipping->price : 0.0;
                $packaging_cost = $packeing ? (float)$packeing->price : 0.0;

                $totalAmount += $shipping_cost + $packaging_cost;

                // كود الخصم
                if (!empty($input['discount_code_id'])) {
                    $totalAmount -= (float)($input['discount_amount'] ?? 0);
                }

                // // dd('single', $input, $shipping_cost, $packaging_cost, $tax_amount, $totalAmount);

                return [
                    'total_amount'        => $totalAmount,
                    'shipping'            => $shipping,
                    'packeing'            => $packeing,
                    'tax'                 => $tax_amount,
                    'merchant_shipping_ids' => @json_encode($merchant_shipping_ids),
                    'merchant_packing_ids'  => @json_encode($merchant_packing_ids),
                    'merchant_ids'          => @json_encode($merchant_ids),
                    'success'             => true,
                ];
            }

            // شحن متعدد
            // 1) الشحن
            $shipping_cost = 0.0;

            // إن كانت الخطوة 2 أعطتنا الإجمالي مباشرةً نستخدمه (الأدق)
            if (isset($input['shipping_cost']) && is_numeric($input['shipping_cost'])) {
                $shipping_cost = (float)$input['shipping_cost'];
            } else {
                // خلاف ذلك نفكّ مصفوفة shipping ونجمع الأسعار سواء كانت IDs أو Tryoto strings
                if (isset($input['shipping']) && is_string($input['shipping'])) {
                    $shippingData = json_decode($input['shipping'], true);
                } else {
                    $shippingData = $input['shipping'] ?? null;
                }

                if (is_array($shippingData)) {
                    foreach ($shippingData as $merchantKey => $val) {
                        // Tryoto: "deliveryOptionId#Company#price"
                        if (is_string($val) && strpos($val, '#') !== false) {
                            $parts = explode('#', $val);
                            $price = (float)($parts[2] ?? 0);
                            $shipping_cost += $price;
                        } else {
                            // ID من جدول shippings
                            $id = (int)$val;
                            if ($id > 0) {
                                $ship = Shipping::find($id);
                                if ($ship) {
                                    // تطبيق منطق free_above
                                    $shippingPrice = (float)$ship->price;
                                    if ($ship->free_above > 0 && $totalAmount >= $ship->free_above) {
                                        $shippingPrice = 0.0; // شحن مجاني
                                    }
                                    $shipping_cost += $shippingPrice;
                                }
                            }
                        }
                    }
                }
            }

            // 2) التغليف
            if (isset($input['packeging']) && is_string($input['packeging'])) {
                $packegingData = json_decode($input['packeging'], true);
            } else {
                $packegingData = $input['packeging'] ?? null;
            }

            $packaging_cost = 0.0;
            if (is_array($packegingData)) {
                foreach ($packegingData as $key => $packaging_id) {
                    $pkgId = (int)$packaging_id;
                    if ($pkgId > 0) {
                        $pack = Package::find($pkgId);
                        if ($pack) {
                            $packaging_cost += (float)$pack->price;
                        }
                    }
                }
            }

            // إجمالي
            $totalAmount += $shipping_cost + $packaging_cost;

            // كود الخصم
            if (!empty($input['discount_code_id'])) {
                $totalAmount -= (float)($input['discount_amount'] ?? 0);
            }

            // // dd('multi', $input, $shipping_cost, $packaging_cost, $tax_amount, $totalAmount);

            return [
                'total_amount'        => $totalAmount,
                'shipping'            => null, // متعدد: لا يوجد واحد محدد
                'packeing'            => null,
                'tax'                 => $tax_amount,
                'merchant_shipping_ids' => @json_encode($input['shipping'] ?? []),
                'merchant_packing_ids'  => @json_encode($input['packeging'] ?? []),
                'merchant_ids'          => @json_encode($merchant_ids),
                'shipping_cost'       => $shipping_cost,
                'packing_cost'        => $packaging_cost,
                'success'             => true,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function getPurchaseTotalAmount($input, $cart)
    {
        // Use centralized MonetaryUnitService
        $curr = monetaryUnit()->getCurrent();
        $currValue = monetaryUnit()->getValue();

        try {
            // merchant_ids من السلة
            $merchant_ids = [];
            foreach ($cart->items as $item) {
                if (!in_array($item['item']['user_id'], $merchant_ids)) {
                    $merchant_ids[] = $item['item']['user_id'];
                }
            }

            $gs = cache()->remember('muaadhsettings', now()->addDay(), function () {
                return DB::table('muaadhsettings')->first();
            });

            $totalAmount = (float) ($input['total'] ?? 0);

            // الضريبة - States removed, tax only from Country now
            if (!empty($input['tax']) && !empty($input['tax_type'])) {
                $tax = (float) Country::findOrFail($input['tax'])->tax;
                $tax_amount  = ($totalAmount * $tax) / 100.0;
                $totalAmount += $tax_amount;
            }

            // شحن مفرد
            if ((int)$gs->multiple_shipping === 0) {
                $shipping = Shipping::findOrFail((int)$input['shipping_id']);
                $packeing = Package::findOrFail((int)$input['packaging_id']);
                $totalAmount += (float)$shipping->price + (float)$packeing->price;

                // ملاحظة: نحافظ على نفس سلوكك السابق (قسمة) لعدم كسر بقية المنطق
                return round($totalAmount / $currValue, 2);
            }

            // شحن متعدد
            $shipping_cost  = 0.0;
            $packaging_cost = 0.0;

            // شحن
            if (isset($input['shipping_cost']) && is_numeric($input['shipping_cost'])) {
                $shipping_cost = (float)$input['shipping_cost'];
            } else {
                $shippingData = isset($input['shipping']) && is_string($input['shipping'])
                    ? json_decode($input['shipping'], true)
                    : ($input['shipping'] ?? null);

                if (is_array($shippingData)) {
                    foreach ($shippingData as $k => $val) {
                        if (is_string($val) && strpos($val, '#') !== false) {
                            $parts = explode('#', $val);
                            $shipping_cost += (float)($parts[2] ?? 0);
                        } else {
                            $id = (int)$val;
                            if ($id > 0) {
                                $ship = Shipping::find($id);
                                if ($ship) {
                                    $shippingPrice = (float)$ship->price;
                                    // تطبيق free_above
                                    if ($ship->free_above > 0 && $totalAmount >= $ship->free_above) {
                                        $shippingPrice = 0.0;
                                    }
                                    $shipping_cost += $shippingPrice;
                                }
                            }
                        }
                    }
                }
            }

            // تغليف
            $packegingData = isset($input['packeging']) && is_string($input['packeging'])
                ? json_decode($input['packeging'], true)
                : ($input['packeging'] ?? null);

            if (is_array($packegingData)) {
                foreach ($packegingData as $k => $packaging_id) {
                    $pkgId = (int)$packaging_id;
                    if ($pkgId > 0) {
                        $pack = Package::find($pkgId);
                        if ($pack) $packaging_cost += (float)$pack->price;
                    }
                }
            }

            $totalAmount += $shipping_cost + $packaging_cost;

            // ملاحظة: نحافظ على سلوكك القديم هنا (الضرب) لتفادي كسر أجزاء أخرى تعتمد عليه
            return round($totalAmount * $currValue, 2);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return JSON response with proper Arabic text encoding
     * @param mixed $data The data to return (string or array)
     * @param int $status HTTP status code (default 200)
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse($data, $status = 200)
    {
        return response()->json($data, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Calculate shipping cost based on size and weight
     * @param array $catalogItems Array of catalogItems with qty and weight
     * @param string $shippingMethod Shipping method (standard, express, etc)
     * @return float Calculated shipping cost
     */
    public static function calculateShippingByWeight($catalogItems, $shippingMethod = 'standard')
    {
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($catalogItems as $catalogItem) {
            $qty = $catalogItem['qty'] ?? 1;
            $weight = (float)($catalogItem['item']['weight'] ?? 1);
            $size = $catalogItem['item']['size'] ?? null;

            // Calculate total weight
            $totalWeight += $qty * $weight;

            // Calculate total volume if size is available
            if ($size && is_array($size) && count($size) >= 3) {
                $length = (float)($size[0] ?? 10);
                $width = (float)($size[1] ?? 10);
                $height = (float)($size[2] ?? 10);
                $volume = ($length * $width * $height) / 1000000; // Convert to cubic meters
                $totalVolume += $qty * $volume;
            }
        }

        // Base shipping calculation
        $baseRate = 5.00; // Base shipping rate
        $weightRate = 0.50; // Per kg
        $volumeRate = 10.00; // Per cubic meter

        // Method multipliers
        $methodMultipliers = [
            'standard' => 1.0,
            'express' => 1.5,
            'overnight' => 2.0,
            'tryoto' => 1.2
        ];

        $multiplier = $methodMultipliers[$shippingMethod] ?? 1.0;

        $shippingCost = ($baseRate + ($totalWeight * $weightRate) + ($totalVolume * $volumeRate)) * $multiplier;

        return round($shippingCost, 2);
    }

    /**
     * Calculate shipping dimensions for catalogItems
     * @param array $catalogItems Array of catalogItems
     * @return array Combined dimensions and weight
     */
    public static function calculateShippingDimensions($catalogItems)
    {
        $totalWeight = 0;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($catalogItems as $catalogItem) {
            $qty = $catalogItem['qty'] ?? 1;
            $weight = (float)($catalogItem['item']['weight'] ?? 1);

            // Try to get dimensions from multiple possible sources
            $size = $catalogItem['item']['size'] ?? null;

            // If size is stored as JSON string, decode it
            if (is_string($size)) {
                $size = json_decode($size, true);
            }

            $totalWeight += $qty * $weight;

            // Handle different dimension formats
            $length = 0;
            $width = 0;
            $height = 0;

            if ($size && is_array($size)) {
                if (count($size) >= 3) {
                    // Array format [length, width, height]
                    $length = (float)($size[0] ?? 0);
                    $width = (float)($size[1] ?? 0);
                    $height = (float)($size[2] ?? 0);
                } elseif (isset($size['length']) || isset($size['width']) || isset($size['height'])) {
                    // Associative array format
                    $length = (float)($size['length'] ?? 0);
                    $width = (float)($size['width'] ?? 0);
                    $height = (float)($size['height'] ?? 0);
                }
            }

            // If no dimensions found, use default values based on weight
            if ($length == 0 && $width == 0 && $height == 0) {
                // Default dimensions based on weight (rough estimation)
                $estimatedVolume = max(0.001, $weight * 0.0005); // 0.5L per kg minimum
                $cubicRoot = pow($estimatedVolume, 1/3);
                $length = $width = $height = max(10, $cubicRoot * 100); // minimum 10cm
            }

            // Use maximum length and width, sum heights for stacking
            $maxLength = max($maxLength, $length);
            $maxWidth = max($maxWidth, $width);
            $totalHeight += $qty * $height;
        }

        // Ensure minimum dimensions for shipping
        $maxLength = max(10, $maxLength);  // minimum 10cm
        $maxWidth = max(10, $maxWidth);    // minimum 10cm
        $totalHeight = max(5, $totalHeight); // minimum 5cm

        return [
            'weight' => max(0.1, $totalWeight), // minimum 100g
            'length' => $maxLength,
            'width' => $maxWidth,
            'height' => $totalHeight,
            'volume' => ($maxLength * $maxWidth * $totalHeight) / 1000000 // cubic meters
        ];
    }

}
