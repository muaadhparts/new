<?php

namespace App\Helpers;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Package;
use App\Models\Shipping;
use App\Models\State;
use DB;
use Session;

class PriceHelper
{

    public static function showPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        if (is_numeric($price) && floor($price) != $price) {
            return number_format($price, 2, $gs->decimal_separator, $gs->thousand_separator);
        } else {
            return number_format($price, 0, $gs->decimal_separator, $gs->thousand_separator);
        }
    }

    public static function apishowPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        if (is_numeric($price) && floor($price) != $price) {
            return round($price, 2);
        } else {
            return round($price, 0);
        }
    }

    public static function showCurrencyPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $new_price = 0;
        if (is_numeric($price) && floor($price) != $price) {
            $new_price = number_format($price, 2, $gs->decimal_separator, $gs->thousand_separator);
        } else {
            $new_price = number_format($price, 0, $gs->decimal_separator, $gs->thousand_separator);
        }
        if (Session::has('currency')) {
            $curr = Currency::find(Session::get('currency'));
        } else {
            $curr = Currency::where('is_default', '=', 1)->first();
        }

        if ($gs->currency_format == 0) {
            return $curr->sign . $new_price;
        } else {
            return $new_price . $curr->sign;
        }
    }

    public static function showAdminCurrencyPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $new_price = 0;
        if (is_numeric($price) && floor($price) != $price) {
            $new_price = number_format($price, 2, $gs->decimal_separator, $gs->thousand_separator);
        } else {
            $new_price = number_format($price, 0, $gs->decimal_separator, $gs->thousand_separator);
        }

        $curr = Currency::where('is_default', '=', 1)->first();

        if ($gs->currency_format == 0) {
            return $curr->sign . $new_price;
        } else {
            return $new_price . $curr->sign;
        }
    }

    public static function showOrderCurrencyPrice($price, $currency)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $new_price = 0;
        if (is_numeric($price) && floor($price) != $price) {
            $new_price = number_format($price, 2, $gs->decimal_separator, $gs->thousand_separator);
        } else {
            $new_price = number_format($price, 0, $gs->decimal_separator, $gs->thousand_separator);
        }

        if ($gs->currency_format == 0) {
            return $currency . $new_price;
        } else {
            return $new_price . $currency;
        }
    }

    public static function ImageCreateName($image)
    {
        $name = time() . preg_replace('/[^A-Za-z0-9\-]/', '', $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
        return $name;
    }

//     public static function getOrderTotal($input, $cart)
//     {
//         try {
//             $vendor_ids = [];
//             foreach ($cart->items as $item) {
//                 if (!in_array($item['item']['user_id'], $vendor_ids)) {
//                     $vendor_ids[] = $item['item']['user_id'];
//                 }
//             }

//             $gs = DB::table('generalsettings')->first();

//             $totalAmount = $cart->totalPrice;
//             $tax_amount = 0;
//             if ($input['tax'] && @$input['tax_type']) {
//                 if (@$input['tax_type'] == 'state_tax') {
//                     $tax = State::findOrFail($input['tax'])->tax;
//                 } else {
//                     $tax = Country::findOrFail($input['tax'])->tax;
//                 }
//                 $tax_amount = ($totalAmount / 100) * $tax;
//                 $totalAmount = $totalAmount + $tax_amount;

//             }

//             if ($gs->multiple_shipping == 0) {
//                 $vendor_shipping_ids = [];
//                 $vendor_packing_ids = [];
//                 foreach ($vendor_ids as $vendor_id) {
//                     $vendor_shipping_ids[$vendor_id] = isset($input['shipping_id']) && $input['shipping_id'] != 0 ? $input['shipping_id'] : null;
//                     $vendor_packing_ids[$vendor_id] = isset($input['packaging_id']) && $input['packaging_id'] != 0 ? $input['packaging_id'] : null;
//                 }


//                 $shipping = isset($input['shipping_id']) && $input['shipping_id'] != 0 ? Shipping::findOrFail($input['shipping_id']) : null;


//                 $packeing = isset($input['packaging_id']) && $input['packaging_id'] != 0 ? Package::findOrFail($input['packaging_id']) : null;

//                 $totalAmount = $totalAmount+@$shipping->price+@$packeing->price;

//                 if (isset($input['coupon_id']) && !empty($input['coupon_id'])) {
//                     $totalAmount = $totalAmount - $input['coupon_discount'];
//                 }


//                 return [
//                     'total_amount' => $totalAmount,
//                     'shipping' => $shipping,
//                     'packeing' => $packeing,
//                     'is_shipping' => 0,
//                     'tax'            => $tax_amount, // ✅ أُضيفت قيمة الضريبة
//                     'vendor_shipping_ids' => @json_encode($vendor_shipping_ids),
//                     'vendor_packing_ids' => @json_encode($vendor_packing_ids),
//                     'vendor_ids' => @json_encode($vendor_ids),
//                     'success' => true,
//                 ];

//             } else {

//                 if (isset($input['shipping']) && gettype($input['shipping']) == 'string') {
//                     $shippingData = json_decode($input['shipping'], true);
// //                    dd($shippingData);
//                 } else {
//                     $shippingData = isset($input['shipping']) ? $input['shipping'] : null;
// //                    dd($shippingData);
//                 }
// //                dd($input);
//                 $shipping_cost = 0;
//                 $packaging_cost = 0;
//                 $vendor_ids = [];
//                 if (isset($input['shipping']) && $input['shipping'] != 0 && is_array($shippingData)) {
// //                    dd($shippingData);
//                     foreach ($shippingData as $key => $shipping_id) {
// //                        dd($input['shipping'] ,$shipping_id);
//                         $shipping = Shipping::findOrFail($shipping_id);

//                             if($shipping->id===16){
//                                 $shipping_cost += $input['shipping_cost'];
// //                                dd($shipping ,$input['shipping_cost']);
//                             }else{
//                                 $shipping_cost += $shipping->price;
//                             }


//                         if (!in_array($shipping->user_id, $vendor_ids)) {
//                             $vendor_ids[] = $shipping->user_id;
//                         }
//                     }
//                 }

//                 if (isset($input['packeging']) && gettype($input['packeging']) == 'string') {
//                     $packegingData = json_decode($input['packeging'], true);
//                 } else {
//                     $packegingData = isset($input['packeging']) ? $input['packeging'] : null;
//                 }

//                 if (isset($input['packeging']) && $input['packeging'] != 0 && is_array($packegingData)) {
//                     foreach ($packegingData as $key => $packaging_id) {
//                         $packeing = Package::findOrFail($packaging_id);
//                         $packaging_cost += $packeing->price;
//                         if (!in_array($packeing->user_id, $vendor_ids)) {
//                             $vendor_ids[] = $packeing->user_id;
//                         }
//                     }
//                 }

//                 $totalAmount = $totalAmount + $shipping_cost + $packaging_cost;
//                 if (isset($input['coupon_id']) && !empty($input['coupon_id'])) {
//                     $totalAmount = $totalAmount - $input['coupon_discount'];
//                 }

//                 return [
//                     'total_amount' => $totalAmount,
//                     'shipping' => isset($shipping) ? $shipping : null,
//                     'packeing' => isset($packeing) ? $packeing : null,
//                     'is_shipping' => 1,
//                     'tax' => $tax_amount,
//                     'vendor_shipping_ids' => @json_encode($input['shipping']),
//                     'vendor_packing_ids' => @json_encode($input['packeging']),
//                     'vendor_ids' => @json_encode($vendor_ids),
//                     'shipping_cost' => $shipping_cost,
//                     'packing_cost' => $packaging_cost,
//                     'success' => true,
//                 ];
//             }
//         } catch (\Exception $e) {
//             dd($e->getMessage());
//             return [
//                 'success' => false,
//                 'message' => $e->getMessage(),
//             ];
//         }
//     }
    public static function getOrderTotal($input, $cart)
    {
        try {
            // اجمع vendor_ids من محتوى السلة
            $vendor_ids = [];
            foreach ($cart->items as $item) {
                if (!in_array($item['item']['user_id'], $vendor_ids)) {
                    $vendor_ids[] = $item['item']['user_id'];
                }
            }

            $gs = DB::table('generalsettings')->first();

            $totalAmount = (float) $cart->totalPrice;

            // الضريبة
            $tax_amount = 0.0;
            if (!empty($input['tax']) && !empty($input['tax_type'])) {
                if ($input['tax_type'] === 'state_tax') {
                    $tax = (float) State::findOrFail($input['tax'])->tax;
                } else {
                    $tax = (float) Country::findOrFail($input['tax'])->tax;
                }
                $tax_amount  = ($totalAmount * $tax) / 100.0;
                $totalAmount += $tax_amount;
            }

            // شحن مفرد
            if ((int)$gs->multiple_shipping === 0) {
                $vendor_shipping_ids = [];
                $vendor_packing_ids  = [];

                foreach ($vendor_ids as $vendor_id) {
                    $vendor_shipping_ids[$vendor_id] = (!empty($input['shipping_id']) && (int)$input['shipping_id'] !== 0) ? (int)$input['shipping_id'] : null;
                    $vendor_packing_ids[$vendor_id]  = (!empty($input['packaging_id']) && (int)$input['packaging_id'] !== 0) ? (int)$input['packaging_id'] : null;
                }

                $shipping = (!empty($input['shipping_id']) && (int)$input['shipping_id'] !== 0)
                    ? Shipping::find((int)$input['shipping_id']) : null;

                $packeing = (!empty($input['packaging_id']) && (int)$input['packaging_id'] !== 0)
                    ? Package::find((int)$input['packaging_id']) : null;

                $shipping_cost = $shipping ? (float)$shipping->price : 0.0;
                $packaging_cost = $packeing ? (float)$packeing->price : 0.0;

                $totalAmount += $shipping_cost + $packaging_cost;

                // كوبون
                if (!empty($input['coupon_id'])) {
                    $totalAmount -= (float)($input['coupon_discount'] ?? 0);
                }

                // // dd('single', $input, $shipping_cost, $packaging_cost, $tax_amount, $totalAmount);

                return [
                    'total_amount'        => $totalAmount,
                    'shipping'            => $shipping,
                    'packeing'            => $packeing,
                    'is_shipping'         => 0,
                    'tax'                 => $tax_amount,
                    'vendor_shipping_ids' => @json_encode($vendor_shipping_ids),
                    'vendor_packing_ids'  => @json_encode($vendor_packing_ids),
                    'vendor_ids'          => @json_encode($vendor_ids),
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
                    foreach ($shippingData as $vendorKey => $val) {
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
                                    $shipping_cost += (float)$ship->price;
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

            // كوبون
            if (!empty($input['coupon_id'])) {
                $totalAmount -= (float)($input['coupon_discount'] ?? 0);
            }

            // // dd('multi', $input, $shipping_cost, $packaging_cost, $tax_amount, $totalAmount);

            return [
                'total_amount'        => $totalAmount,
                'shipping'            => null, // متعدد: لا يوجد واحد محدد
                'packeing'            => null,
                'is_shipping'         => 1,
                'tax'                 => $tax_amount,
                'vendor_shipping_ids' => @json_encode($input['shipping'] ?? []),
                'vendor_packing_ids'  => @json_encode($input['packeging'] ?? []),
                'vendor_ids'          => @json_encode($vendor_ids),
                'shipping_cost'       => $shipping_cost,
                'packing_cost'        => $packaging_cost,
                'success'             => true,
            ];
        } catch (\Exception $e) {
            dd($e->getMessage()); // //
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // public static function getOrderTotalAmount($input, $cart)
    // {

    //     if (Session::has('currency')) {
    //         $curr = cache()->remember('session_currency', now()->addDay(), function () {
    //             return Currency::find(Session::get('currency'));
    //         });
    //     } else {
    //         $curr = cache()->remember('default_currency', now()->addDay(), function () {
    //             return Currency::where('is_default', '=', 1)->first();
    //         });
    //     }

    //     try {
    //         $vendor_ids = [];
    //         foreach ($cart->items as $item) {
    //             if (!in_array($item['item']['user_id'], $vendor_ids)) {
    //                 $vendor_ids[] = $item['item']['user_id'];
    //             }
    //         }

    //         $gs = cache()->remember('generalsettings', now()->addDay(), function () {
    //             return DB::table('generalsettings')->first();
    //         });
    //         $totalAmount = $input['total'];

    //         if ($input['tax'] && @$input['tax_type']) {
    //             if (@$input['tax_type'] == 'state_tax') {
    //                 $tax = State::findOrFail($input['tax'])->tax;
    //             } else {
    //                 $tax = Country::findOrFail($input['tax'])->tax;
    //             }
    //             $tax_amount = ($totalAmount / 100) * $tax;
    //             $totalAmount = $totalAmount + $tax_amount;
    //         }

    //         if ($gs->multiple_shipping == 0) {
    //             $vendor_shipping_ids = [];
    //             $vendor_packing_ids = [];
    //             foreach ($vendor_ids as $vendor_id) {
    //                 $vendor_shipping_ids[$vendor_id] = $input['shipping_id'];
    //                 $vendor_packing_ids[$vendor_id] = $input['packaging_id'];
    //             }

    //             $shipping = Shipping::findOrFail($input['shipping_id']);
    //             $packeing = Package::findOrFail($input['packaging_id']);
    //             $totalAmount = $totalAmount + $shipping->price + $packeing->price;
    //             return round($totalAmount / $curr->value, 2);
    //         } else {

    //             $shipping_cost = 0;
    //             $packaging_cost = 0;
    //             $vendor_ids = [];
    //             if ($input['shipping']) {
    //                 foreach ($input['shipping'] as $key => $shipping_id) {
    //                     $shipping = Shipping::findOrFail($shipping_id);
    //                     $shipping_cost += $shipping->price;
    //                     if (!in_array($shipping->user_id, $vendor_ids)) {
    //                         $vendor_ids[] = $shipping->user_id;
    //                     }
    //                 }
    //             }
    //             if ($input['packeging']) {
    //                 foreach ($input['packeging'] as $key => $packaging_id) {
    //                     $packeing = Package::findOrFail($packaging_id);
    //                     $packaging_cost += $packeing->price;
    //                     if (!in_array($packeing->user_id, $vendor_ids)) {
    //                         $vendor_ids[] = $packeing->user_id;
    //                     }
    //                 }
    //             }

    //             $totalAmount = $totalAmount + $shipping_cost + $packaging_cost;

    //             return round($totalAmount * $curr->value, 2);
    //         }
    //     } catch (\Exception $e) {
    //         dd($e->getMessage());
    //         return [
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ];
    //     }
    // }
    public static function getOrderTotalAmount($input, $cart)
    {
        // العملة
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }

        try {
            // vendor_ids من السلة
            $vendor_ids = [];
            foreach ($cart->items as $item) {
                if (!in_array($item['item']['user_id'], $vendor_ids)) {
                    $vendor_ids[] = $item['item']['user_id'];
                }
            }

            $gs = cache()->remember('generalsettings', now()->addDay(), function () {
                return DB::table('generalsettings')->first();
            });

            $totalAmount = (float) ($input['total'] ?? 0);

            // الضريبة
            if (!empty($input['tax']) && !empty($input['tax_type'])) {
                if ($input['tax_type'] === 'state_tax') {
                    $tax = (float) State::findOrFail($input['tax'])->tax;
                } else {
                    $tax = (float) Country::findOrFail($input['tax'])->tax;
                }
                $tax_amount  = ($totalAmount * $tax) / 100.0;
                $totalAmount += $tax_amount;
            }

            // شحن مفرد
            if ((int)$gs->multiple_shipping === 0) {
                $shipping = Shipping::findOrFail((int)$input['shipping_id']);
                $packeing = Package::findOrFail((int)$input['packaging_id']);
                $totalAmount += (float)$shipping->price + (float)$packeing->price;

                // ملاحظة: نحافظ على نفس سلوكك السابق (قسمة) لعدم كسر بقية المنطق
                return round($totalAmount / (float)$curr->value, 2);
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
                                if ($ship) $shipping_cost += (float)$ship->price;
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

            // ملاحظة: نن保持 سلوكك القديم هنا (الضرب) لتفادي كسر أجزاء أخرى تعتمد عليه
            return round($totalAmount * (float)$curr->value, 2);

        } catch (\Exception $e) {
            dd($e->getMessage()); // //
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

}
