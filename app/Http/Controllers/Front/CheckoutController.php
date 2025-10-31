<?php

/**
 * ====================================================================
 * MULTI-VENDOR CHECKOUT CONTROLLER
 * ====================================================================
 *
 * This controller handles checkout operations in a multi-vendor system:
 *
 * Key Features:
 * 1. VENDOR-SPECIFIC CHECKOUT ROUTES:
 *    - checkoutVendor($vendorId): Step 1 - Customer info for specific vendor
 *    - checkoutVendorStep1(): Save customer data to vendor_step1_{vendor_id}
 *    - checkoutVendorStep2($vendorId): Step 2 - Show shipping for this vendor ONLY
 *    - checkoutVendorStep2Submit(): Save shipping to vendor_step2_{vendor_id}
 *    - checkoutVendorStep3($vendorId): Step 3 - Show payment gateways for this vendor ONLY
 *
 * 2. VENDOR ISOLATION:
 *    - Filters cart items to show only products from specified vendor
 *    - Shows ONLY shipping companies where user_id = vendor_id
 *    - Shows ONLY payment gateways where user_id = vendor_id
 *    - NO FALLBACK to global/admin shipping or payment methods
 *
 * 3. SESSION MANAGEMENT:
 *    - checkout_vendor_id: Current vendor being processed
 *    - vendor_step1_{vendor_id}: Customer data per vendor
 *    - vendor_step2_{vendor_id}: Shipping data per vendor
 *
 * 4. ERROR HANDLING:
 *    - If no payment methods exist for vendor: Show error message
 *    - If no products for vendor: Redirect to cart
 *
 * Flow:
 * Cart → /checkout/vendor/{id} → Step1 → Step2 → Step3 → Payment Controller → Order
 *
 * Modified: 2025-01-XX for Multi-Vendor Checkout System
 * ====================================================================
 */

namespace App\Http\Controllers\Front;

use App\Helpers\PriceHelper;
use App\Http\Controllers\MyFatoorahController;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\State;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

class CheckoutController extends FrontBaseController
{
    // Loading Payment Gateways





    public function loadpayment($slug1, $slug2)
    {
//
        // $slug2 =19;
//        dd($slug1, $slug2);
        $curr = $this->curr;
        $payment = $slug1;
        $pay_id = $slug2;
        $gateway = '';
        if ($pay_id != 0) {
            $gateway = PaymentGateway::findOrFail($pay_id);
        }
//        dd($slug1, $slug2 ,$gateway);
        return view('load.payment', compact('payment', 'pay_id', 'gateway', 'curr'));
    }

    // Wallet Amount Checking

    public function walletcheck()
    {
        $amount = (float) $_GET['code'];
        $total = (float) $_GET['total'];
        $balance = Auth::user()->balance;
        if ($amount <= $balance) {
            if ($amount > 0 && $amount <= $total) {
                $total -= $amount;
                $data[0] = $total;
                $data[1] = $amount;
                $data[2] = \PriceHelper::showCurrencyPrice($total);
                $data[3] = \PriceHelper::showCurrencyPrice($amount);
                return response()->json($data);
            } else {
                return response()->json(0);
            }
        } else {
            return response()->json(0);
        }
    }

    public function checkout()
    {

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }
        $dp = 1;
        $vendor_shipping_id = 0;
        $vendor_packing_id = 0;
        $curr = $this->curr;
        $pickups = DB::table('pickups')->get();
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $products = $cart->items;
      
        if (Auth::check()) {

            // Shipping Method

            if ($this->gs->multiple_shipping == 1) {
                $ship_data = Order::getShipData($cart);
                $shipping_data = $ship_data['shipping_data'];
                $vendor_shipping_id = $ship_data['vendor_shipping_id'];
            } else {
                $shipping_data = DB::table('shippings')->whereUserId(0)->get();
            }

            // Packaging

            if ($this->gs->multiple_shipping == 1) {
                $pack_data = Order::getPackingData($cart);
                $package_data = $pack_data['package_data'];
                $vendor_packing_id = $pack_data['vendor_packing_id'];
            } else {
                $package_data = DB::table('packages')->whereUserId(0)->get();
            }
            foreach ($products as $prod) {
                if ($prod['item']['type'] == 'Physical') {
                    $dp = 0;
                    break;
                }
            }
            $total = $cart->totalPrice;
            $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

            if (!Session::has('coupon_total')) {
                $total = $total - $coupon;
                $total = $total + 0;
            } else {
                $total = Session::get('coupon_total');
                $total = str_replace(',', '', str_replace($curr->sign, '', $total));
            }

//            dd($total ,$cart->items);

            return view('frontend.checkout.step1', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id]);
        } else {

            if ($this->gs->guest_checkout == 1) {
                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Order::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $vendor_shipping_id = $ship_data['vendor_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_shipping == 1) {
                    $pack_data = Order::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $vendor_packing_id = $pack_data['vendor_packing_id'];
                } else {
                    $package_data = DB::table('packages')->whereUserId('0')->get();

                }

                foreach ($products as $prod) {
                    if ($prod['item']['type'] == 'Physical') {
                        $dp = 0;
                        break;
                    }
                }
                $total = $cart->totalPrice;
                $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

                if (!Session::has('coupon_total')) {
                    $total = $total - $coupon;
                    $total = $total + 0;
                } else {
                    $total = Session::get('coupon_total');
                    $total = str_replace($curr->sign, '', $total) + round(0 * $curr->value, 2);
                }
                foreach ($products as $prod) {
                    if ($prod['item']['type'] != 'Physical') {
                        if (!Auth::check()) {
                            $ck = 1;
                            return view('frontend.checkout.step1', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id]);
                        }
                    }
                }
                return view('frontend.checkout.step1', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id]);
            }

            // If guest checkout is Deactivated then display pop up form with proper error message

            else {

                // Shipping Method

                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Order::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $vendor_shipping_id = $ship_data['vendor_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_packaging == 1) {
                    $pack_data = Order::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $vendor_packing_id = $pack_data['vendor_packing_id'];
                } else {
                    $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
                }

                $total = $cart->totalPrice;
                $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

                if (!Session::has('coupon_total')) {
                    $total = $total - $coupon;
                    $total = $total + 0;
                } else {
                    $total = Session::get('coupon_total');
                    $total = $total;
                }
                $ck = 1;
                return view('frontend.checkout.step1', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,  'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id]);
            }
        }
    }

    public function checkoutstep2()
    {

        if (!Session::has('step1')) {
            return redirect()->route('front.checkout')->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

//        dd(Session::get('cart'));

        $step1 = (object) Session::get('step1');
//        dd($step1);
        $dp = 1;
        $vendor_shipping_id = 0;
        $vendor_packing_id = 0;
        $curr = $this->curr;
        $pickups = DB::table('pickups')->get();
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $products = $cart->items;

//        dd($products);
        if (Auth::check()) {

            // Shipping Method

            if ($this->gs->multiple_shipping == 1) {
                $ship_data = Order::getShipData($cart);
                $shipping_data = $ship_data['shipping_data'];
                $vendor_shipping_id = $ship_data['vendor_shipping_id'];
            } else {
                $shipping_data = DB::table('shippings')->whereUserId(0)->get();
            }

            // Packaging

            if ($this->gs->multiple_shipping == 1) {
                $pack_data = Order::getPackingData($cart);
                $package_data = $pack_data['package_data'];
                $vendor_packing_id = $pack_data['vendor_packing_id'];
            } else {
                $package_data = DB::table('packages')->whereUserId(0)->get();
            }
            foreach ($products as $prod) {
                if ($prod['item']['type'] == 'Physical') {
                    $dp = 0;
                    break;
                }
            }
            $total = $cart->totalPrice;
            $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

            if (!Session::has('coupon_total')) {
                $total = $total - $coupon;
                $total = $total + 0;
            } else {
                $total = Session::get('coupon_total');
                $total = str_replace(',', '', str_replace($curr->sign, '', $total));
            }
//            dd($cart->items);

            return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1]);
        } else {

            if ($this->gs->guest_checkout == 1) {
                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Order::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $vendor_shipping_id = $ship_data['vendor_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_shipping == 1) {
                    $pack_data = Order::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $vendor_packing_id = $pack_data['vendor_packing_id'];
                } else {
                    $package_data = DB::table('packages')->whereUserId('0')->get();

                }

                foreach ($products as $prod) {
                    if ($prod['item']['type'] == 'Physical') {
                        $dp = 0;
                        break;
                    }
                }
                $total = $cart->totalPrice;
                $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

                if (!Session::has('coupon_total')) {
                    $total = $total - $coupon;
                    $total = $total + 0;
                } else {
                    $total = Session::get('coupon_total');
                    $total = str_replace($curr->sign, '', $total) + round(0 * $curr->value, 2);
                }
                foreach ($products as $prod) {
                    if ($prod['item']['type'] != 'Physical') {
                        if (!Auth::check()) {
                            $ck = 1;
                            return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id]);
                        }
                    }
                }
//                dd($cart->items);
                return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1]);
            }

            // If guest checkout is Deactivated then display pop up form with proper error message

            else {

                // Shipping Method

                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Order::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $vendor_shipping_id = $ship_data['vendor_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_packaging == 1) {
                    $pack_data = Order::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $vendor_packing_id = $pack_data['vendor_packing_id'];
                } else {
                    $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
                }

                $total = $cart->totalPrice;
                $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

                if (!Session::has('coupon_total')) {
                    $total = $total - $coupon;
                    $total = $total + 0;
                } else {
                    $total = Session::get('coupon_total');
                    $total = $total;
                }
                $ck = 1;
//                dd($cart->items);
                return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1]);
            }
        }
    }









    public function checkoutStep1(Request $request)
    {
        $step1 = $request->all();

//        dd($step1);
        $validator = Validator::make($step1, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'customer_country' => 'required|string|max:255',
            'customer_state' => 'required|string|max:255',
//            'customer_city' => 'required|string|max:255',
 //            'shipping_name' => 'nullable|string|max:255',
//            'shipping_phone' => 'nullable|regex:/^[0-9]{10,15}$/',
//            'shipping_address' => 'nullable|string|max:255',
//            'shipping_zip' => 'nullable|string|max:20',
//            'shipping_city' => 'nullable|string|max:255',
//            'shipping_state' => 'nullable|string|max:255',
        ]);


//        dd($validator ,$validator->errors()->all());
        if ($validator->fails()) {
            return  back()->withErrors($validator->errors());
        }


//        dd($step1 ,$validator->errors());
//            Session::forget(['step1', 'step2','step3','cart']);
////        dd($step1);
//        $oldCart = Session::get('cart');
//
//        // Update cart details with shipping information
//        $oldCart->totalPrice = 60;
//
//
//        // Create a new cart instance
//        $cart = new Cart($oldCart);
//
//        dd($step1);
        Session::put('step1', $step1);
        return redirect()->route('front.checkout.step2');
    }

//     public function checkoutStep2Submit(Request $request)
//     {
//         $step2 = $request->all();
//         $oldCart = Session::get('cart');
//         $input = Session::get('step1') +$step2;

//         // Update cart details with shipping information
// //        $oldCart->totalPrice = 60;


//         // Create a new cart instance
// //        $cart = new Cart($oldCart);
// //        dd($step2 ,$step2['shipping'][0] ==="1");
//         $shipping_cost = 0;
//         if($step2['shipping'][0] !=="1"){
//         list($shippingId, $shipping_company, $shipping_cost) = explode('#', $step2['shipping'][0]);
//         $shipping_cost = (float) $shipping_cost; // Ensure the shipping cost is numeric
// //        dd($shippingId ,$shipping_cost);
//     // Retrieve the current cart from the session
//         $oldCart = Session::get('cart');
// //            $orderCalculate = PriceHelper::getOrderTotal($input, $oldCart);
// //
// //            dd($orderCalculate);
//     // Update cart details with shipping information
// //            $oldCart->totalPrice += $shipping_cost;
//             $oldCart->shipping_name = $shipping_company;
//             $oldCart->shipping_cost = $shipping_cost;

//     // Create a new cart instance
//             $cart = new Cart($oldCart);

//     // Update step2 with shipping details
//         $step2['shipping_company'] = $shipping_company;
//         $step2['shipping_cost'] = $shipping_cost;

//         }
//         $step2['shipping_cost'] = $shipping_cost;

// //        dd(  $step2 ,$cart ,$shipping_cost);
//         Session::put('step2', $step2);
//         return redirect()->route('front.checkout.step3');
//     }

    /**
     * تم إزالة saveShippingSelection - الحفظ الآن يتم فقط عند Submit
     */

    public function checkoutStep2Submit(Request $request)
    {
        $step2  = $request->all();
        $oldCart = Session::get('cart');
        $input  = Session::get('step1') + $step2;

        // حساب المبلغ الأساسي (قبل الضريبة والشحن) لتطبيق free_above
        $cart = new Cart($oldCart);
        $baseAmount = $cart->totalPrice;
        $coupon = Session::has('coupon') ? Session::get('coupon') : 0;
        $baseAmount = $baseAmount - $coupon;

        // إجمالي تكلفة الشحن + أسماء الشركات (قد يكون متعدد البائعين)
        $shipping_cost_total = 0.0;
        $shipping_names = [];

        // حالة الشحن المتعدد: shipping[vendor_id] = {id أو deliveryOptionId#Company#price}
        if (isset($step2['shipping']) && is_array($step2['shipping'])) {

            foreach (array_values($step2['shipping']) as $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // تنسيق Tryoto: deliveryOptionId#CompanyName#price
                    $parts   = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price   = (float)($parts[2] ?? 0);

                    // Tryoto لا يدعم free_above حالياً - يتم إضافة السعر مباشرة
                    $shipping_cost_total += $price;
                    if ($company !== '') {
                        $shipping_names[] = $company;
                    }
                } else {
                    // تنسيق ID عادي من جدول shippings - تطبيق منطق free_above
                    $id = (int)$val;
                    if ($id > 0) {
                        $ship = \App\Models\Shipping::find($id);
                        if ($ship) {
                            // تطبيق منطق free_above
                            $freeAbove = (float)($ship->free_above ?? 0);
                            if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                                // الشحن مجاني - لا نضيف السعر
                                $shipping_names[] = $ship->title . ' (Free Shipping)';
                            } else {
                                $shipping_cost_total += (float)$ship->price;
                                $shipping_names[] = $ship->title;
                            }
                        }
                    }
                }
            }

        // حالة الشحن المفرد: name="shipping_id"
        } elseif (isset($step2['shipping_id'])) {
            $id = (int)$step2['shipping_id'];
            if ($id > 0) {
                $ship = \App\Models\Shipping::find($id);
                if ($ship) {
                    // تطبيق منطق free_above
                    $freeAbove = (float)($ship->free_above ?? 0);
                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        // الشحن مجاني - لا نضيف السعر
                        $shipping_names[] = $ship->title . ' (Free Shipping)';
                    } else {
                        $shipping_cost_total += (float)$ship->price;
                        $shipping_names[] = $ship->title;
                    }
                }
            }
        }

        // دمج الأسماء (إن وجدت)
        $shipping_name = count($shipping_names) ? implode(' + ', array_unique($shipping_names)) : null;

        // تحديث الكارت في السيشن
        if ($oldCart) {
            $oldCart->shipping_name = $shipping_name;
            $oldCart->shipping_cost = $shipping_cost_total;
            $cart = new Cart($oldCart);
            Session::put('cart', $cart);
        }

        // حفظ ملخص الشحن في step2 لاستخدامه في step3
        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost']    = $shipping_cost_total;

        Session::put('step2', $step2);

        return redirect()->route('front.checkout.step3');
    }

    public function checkoutstep3()
    {
        if (!Session::has('step1')) {
            return redirect()->route('front.checkout')->with('success', __("Please fill up step 1."));
        }
        if (!Session::has('step2')) {
            return redirect()->route('front.checkout.step2')->with('success', __("Please fill up step 2."));
        }
        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $step1 = (object) Session::get('step1');
        $step2 = (object) Session::get('step2');
        $dp = 1;
        $vendor_shipping_id = 0;
        $vendor_packing_id = 0;
        $curr = $this->curr;
        $gateways = PaymentGateway::scopeHasGateway($this->curr->id);
        $pickups = DB::table('pickups')->get();

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $products = $cart->items;

        $paystack = PaymentGateway::whereKeyword('paystack')->first();
        $paystackData = $paystack ? $paystack->convertAutoData() : [];

        // اكتشف هل بالسلة منتج فيزيائي
        foreach ($products as $prod) {
            if ($prod['item']['type'] == 'Physical') { $dp = 0; break; }
        }

        // شحن وتغليف
        if ($this->gs->multiple_shipping == 1) {
            $ship_data = Order::getShipData($cart);
            $shipping_data = $ship_data['shipping_data'];
            $vendor_shipping_id = $ship_data['vendor_shipping_id'];
        } else {
            $shipping_data = DB::table('shippings')->whereUserId(0)->get();
        }

        if ($this->gs->multiple_shipping == 1) {
            $pack_data = Order::getPackingData($cart);
            $package_data = $pack_data['package_data'];
            $vendor_packing_id = $pack_data['vendor_packing_id'];
        } else {
            $package_data = DB::table('packages')->whereUserId(0)->get();
        }

        // الإجمالي مع الكوبون
        $total = $cart->totalPrice;
        $coupon = Session::has('coupon') ? Session::get('coupon') : 0;
        if (!Session::has('coupon_total')) {
            $total = $total - $coupon;
        } else {
            $total = Session::get('coupon_total');
        }

        // أعرض صفحة وسائل الدفع (Step 3)
        return view('frontend.checkout.step3', [
            'products'            => $cart->items,
            'totalPrice'          => $total,
            'pickups'             => $pickups,
            'totalQty'            => $cart->totalQty,
            'gateways'            => $gateways,
            'shipping_cost'       => $step2->shipping_cost ?? 0,
            'digital'             => $dp,
            'curr'                => $curr,
            'shipping_data'       => $shipping_data,
            'package_data'        => $package_data,
            'vendor_shipping_id'  => $vendor_shipping_id,
            'vendor_packing_id'   => $vendor_packing_id,
            'paystack'            => $paystackData,
            'step2'               => $step2,
            'step1'               => $step1,
        ]);
    }

//     public function checkoutstep3()
//     {
//         $my =  new MyFatoorahController();
//         return $my->index();
// //        dd($my->index());
//     return redirect()->route('front.myfatoorah.submit');

// //        dd(Session::get('step2'));
//         if (!Session::has('step1')) {
//             return redirect()->route('front.checkout')->with('success', __("Please fill up step 1."));
//         }
//         if (!Session::has('step2')) {
//             return redirect()->route('front.checkout.step2')->with('success', __("Please fill up step 2."));
//         }

//         if (!Session::has('cart')) {
//             return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
//         }

//         $step1 = (object) Session::get('step1');
//         $step2 = (object) Session::get('step2');
//         $dp = 1;
//         $vendor_shipping_id = 0;
//         $vendor_packing_id = 0;
//         $curr = $this->curr;
//         $gateways = PaymentGateway::scopeHasGateway($this->curr->id);
//         $pickups = DB::table('pickups')->get();
//         $oldCart = Session::get('cart');
// //        dd($oldCart);
//         $cart = new Cart($oldCart);
//         $products = $cart->items;
//         $paystack = PaymentGateway::whereKeyword('paystack')->first();
//         $paystackData = $paystack->convertAutoData();
// //        dd($cart ,Session::get('step2'));
//         if (Auth::check()) {

//             // Shipping Method

//             if ($this->gs->multiple_shipping == 1) {
//                 $ship_data = Order::getShipData($cart);
// //                dd($ship_data);
//                 $shipping_data = $ship_data['shipping_data'];
//                 $vendor_shipping_id = $ship_data['vendor_shipping_id'];
//             } else {
//                 $shipping_data = DB::table('shippings')->whereUserId(0)->get();
//             }

//             // Packaging

//             if ($this->gs->multiple_shipping == 1) {
//                 $pack_data = Order::getPackingData($cart);
//                 $package_data = $pack_data['package_data'];
//                 $vendor_packing_id = $pack_data['vendor_packing_id'];
//             } else {
//                 $package_data = DB::table('packages')->whereUserId(0)->get();
//             }
//             foreach ($products as $prod) {
//                 if ($prod['item']['type'] == 'Physical') {
//                     $dp = 0;
//                     break;
//                 }
//             }
//             $total = $cart->totalPrice;
//             $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

//             if (!Session::has('coupon_total')) {
//                 $total = $total - $coupon;
//                 $total = $total + 0;
//             } else {
//                 $total = Session::get('coupon_total');
//                 $total = str_replace(',', '', str_replace($curr->sign, '', $total));
//             }
// //            $step2 = Session::get('step2');

// //            $shipping_cost = $step2['shipping_cost'] ?? 0;
// ////            shipping_cost
// //            dd($total ,$cart ,$step2->shipping_cost);

//             return view('frontend.checkout.step3', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'gateways' => $gateways, 'shipping_cost' => $step2->shipping_cost, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData, 'step1' => $step1, 'step2' => $step2]);
//         } else {

//             if ($this->gs->guest_checkout == 1) {
//                 if ($this->gs->multiple_shipping == 1) {
//                     $ship_data = Order::getShipData($cart);
//                     $shipping_data = $ship_data['shipping_data'];
//                     $vendor_shipping_id = $ship_data['vendor_shipping_id'];
//                 } else {
//                     $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
//                 }

// //                dd($shipping_data);
//                 // Packaging

//                 if ($this->gs->multiple_shipping == 1) {
//                     $pack_data = Order::getPackingData($cart);
//                     $package_data = $pack_data['package_data'];
//                     $vendor_packing_id = $pack_data['vendor_packing_id'];
//                 } else {
//                     $package_data = DB::table('packages')->whereUserId('0')->get();

//                 }

//                 foreach ($products as $prod) {
//                     if ($prod['item']['type'] == 'Physical') {
//                         $dp = 0;
//                         break;
//                     }
//                 }
//                 $total = $cart->totalPrice;
//                 $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

//                 if (!Session::has('coupon_total')) {
//                     $total = $total - $coupon;
//                     $total = $total + 0;
//                 } else {
//                     $total = Session::get('coupon_total');
//                     $total = str_replace($curr->sign, '', $total) + round(0 * $curr->value, 2);
//                 }
//                 foreach ($products as $prod) {
//                     if ($prod['item']['type'] != 'Physical') {
//                         if (!Auth::check()) {
//                             $ck = 1;
//                             return view('frontend.checkout.step3', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'gateways' => $gateways, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData, 'step2' => $step2, 'step1' => $step1]);
//                         }
//                     }
//                 }


// //                dd($total ,$step2 ,['products' => $cart->items,
// //                    'totalPrice' => $total,
// //                    'pickups' => $pickups, 'totalQty' => $cart->totalQty,
// //                    'gateways' => $gateways,
// //                    'shipping_cost' => $step2->shipping_cost, 'digital' => $dp,
// //                    'curr' => $curr, 'shipping_data' => $shipping_data,
// //                    'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id,
// //                    'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData,
// //                    'step2' => $step2, 'step1' => $step1]);


//                 return view('frontend.checkout.step3', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,
//                                 'gateways' => $gateways, 'shipping_cost' =>  $step2->shipping_cost, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData, 'step2' => $step2, 'step1' => $step1]);
//             }

//             // If guest checkout is Deactivated then display pop up form with proper error message

//             else {

//                 // Shipping Method

//                 if ($this->gs->multiple_shipping == 1) {
//                     $ship_data = Order::getShipData($cart);
//                     $shipping_data = $ship_data['shipping_data'];
//                     $vendor_shipping_id = $ship_data['vendor_shipping_id'];
//                 } else {
//                     $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
//                 }

//                 // Packaging

//                 if ($this->gs->multiple_packaging == 1) {
//                     $pack_data = Order::getPackingData($cart);
//                     $package_data = $pack_data['package_data'];
//                     $vendor_packing_id = $pack_data['vendor_packing_id'];
//                 } else {
//                     $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
//                 }

//                 $total = $cart->totalPrice;
//                 $coupon = Session::has('coupon') ? Session::get('coupon') : 0;

//                 if (!Session::has('coupon_total')) {
//                     $total = $total - $coupon;
//                     $total = $total + 0;
//                 } else {
//                     $total = Session::get('coupon_total');
//                     $total = $total;
//                 }
//                 $ck = 1;
//             //    dd($total ,['products' => $cart->items,
//             //        'totalPrice' => $total,
//             //        'pickups' => $pickups, 'totalQty' => $cart->totalQty,
//             //        'gateways' => $gateways, 'shipping_cost' => 0, 'digital' => $dp,
//             //        'curr' => $curr, 'shipping_data' => $shipping_data,
//             //        'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id,
//             //        'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData,
//             //        'step2' => $step2, 'step1' => $step1]);
//                 return view('frontend.checkout.step3',
//                     ['products' => $cart->items,
//                         'totalPrice' => $total,
//                         'pickups' => $pickups, 'totalQty' => $cart->totalQty,
//                         'gateways' => $gateways, 'shipping_cost' => 0, 'digital' => $dp,
//                         'curr' => $curr, 'shipping_data' => $shipping_data,
//                         'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id,
//                         'vendor_packing_id' => $vendor_packing_id, 'paystack' => $paystackData,
//                         'step2' => $step2, 'step1' => $step1]);
//             }
//         }
//     }

    public function getState($country_id)
    {
        $states = State::where('country_id', $country_id)->get();

        if (Auth::user()) {
            $user_state = Auth::user()->state_id;
        } else {
            $user_state = 0;
        }

        // الحصول على اللغة النشطة
        $currentLang = Session::has('language')
            ? \App\Models\Language::find(Session::get('language'))->name
            : \App\Models\Language::where('is_default', 1)->first()->name;

        $html_states = '<option value="" > Select State </option>';
        foreach ($states as $state) {
            if ($state->id == $user_state) {
                $check = 'selected';
            } else {
                $check = '';
            }

            // تحديد اسم الولاية بناءً على اللغة النشطة
            $stateDisplayName = ($currentLang == 'ar')
                ? ($state->state_ar ?? $state->state)
                : $state->state;

            $html_states .= '<option value="' . $state->id . '" rel="' . $state->country->id . '" ' . $check . ' >'
              . $stateDisplayName . '</option>';
        }

        return response()->json(["data" => $html_states, "state" => $user_state]);
    }

    public function getCity(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();

        if (Auth::user()) {
            $user_city = Auth::user()->city;
        } else {
            $user_city = 0;
        }

        // الحصول على اللغة النشطة
        $currentLang = Session::has('language')
            ? \App\Models\Language::find(Session::get('language'))->name
            : \App\Models\Language::where('is_default', 1)->first()->name;

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            if ($city->id == $user_city) {
                $check = 'selected';
            } else {
                $check = '';
            }

            // تحديد اسم المدينة بناءً على اللغة النشطة
            $cityDisplayName = ($currentLang == 'ar')
                ? ($city->city_name_ar ?? $city->city_name)
                : $city->city_name;

            $html_cities .= '<option value="' . $city->city_name . '" ' . $check . ' >'
              . $cityDisplayName . '</option>';
        }

        return response()->json(["data" => $html_cities, "state" => $user_city]);
    }

    public function getCityUser(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();

        if (Auth::user()) {
            $user_city = Auth::user()->city;
        } else {
            $user_city = 0;
        }

        // الحصول على اللغة النشطة
        $currentLang = Session::has('language')
            ? \App\Models\Language::find(Session::get('language'))->name
            : \App\Models\Language::where('is_default', 1)->first()->name;

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            if ($city->id == $user_city) {
                $check = 'selected';
            } else {
                $check = '';
            }

            // تحديد اسم المدينة بناءً على اللغة النشطة
            $cityDisplayName = ($currentLang == 'ar')
                ? ($city->city_name_ar ?? $city->city_name)
                : $city->city_name;

            $html_cities .= '<option value="' . $city->id . '" ' . $check . ' >'
              . $cityDisplayName . '</option>';
        }

        return response()->json(["data" => $html_cities, "state" => $user_city]);
    }

    // Redirect To Checkout Page If Payment is Cancelled

    public function paycancle()
    {

        return redirect()->route('front.checkout')->with('unsuccess', __('Payment Cancelled.'));
    }

    // Redirect To Success Page If Payment is Comleted

    public function payreturn()
    {

        if (Session::has('tempcart')) {
            $oldCart = Session::get('tempcart');
            $tempcart = new Cart($oldCart);
            $order = Session::get('temporder');
        } else {
            $tempcart = '';
            return redirect()->back();
        }

        return view('frontend.success', compact('tempcart', 'order'));
    }

    /* ===================== Vendor-Specific Checkout ===================== */

    /**
     * Helper: Get vendor products and calculate totals
     *
     * Filters cart to show ONLY vendor's products and calculates:
     * - Total price
     * - Total quantity
     * - Digital/Physical flag
     *
     * @param int $vendorId
     * @return array [vendorProducts, totalPrice, totalQty, digital]
     */
    private function getVendorCartData($vendorId): array
    {
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $vendorProducts = [];
        foreach ($cart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId == $vendorId) {
                $vendorProducts[$rowKey] = $product;
            }
        }

        // Calculate totals
        $totalPrice = 0;
        $totalQty = 0;
        foreach ($vendorProducts as $product) {
            $totalPrice += (float)($product['price'] ?? 0);
            $totalQty += (int)($product['qty'] ?? 1);
        }

        // Check if all products are digital
        $dp = 1;
        foreach ($vendorProducts as $prod) {
            if ($prod['item']['type'] == 'Physical') {
                $dp = 0;
                break;
            }
        }

        return [
            'vendorProducts' => $vendorProducts,
            'totalPrice' => $totalPrice,
            'totalQty' => $totalQty,
            'digital' => $dp
        ];
    }

    /**
     * Step 1 - Display checkout page for a SPECIFIC vendor only
     *
     * MULTI-VENDOR LOGIC:
     * 1. Saves vendor_id in session (checkout_vendor_id)
     * 2. Filters cart to show ONLY this vendor's products
     * 3. Shows ONLY shipping methods where user_id = vendor_id
     * 4. Shows ONLY packaging methods where user_id = vendor_id
     * 5. NO global fallback - vendor must have their own methods
     *
     * @param int $vendorId The vendor's user_id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkoutVendor($vendorId)
    {
        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        // Save vendor_id in session for tracking throughout checkout
        Session::put('checkout_vendor_id', $vendorId);

        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        if (empty($vendorProducts)) {
            return redirect()->route('front.cart')->with('unsuccess', __("No products found for this vendor."));
        }

        // جلب طرق الشحن الخاصة بهذا التاجر فقط
        $shipping_data = DB::table('shippings')->where('user_id', $vendorId)->get();
        if ($shipping_data->isEmpty()) {
            $shipping_data = DB::table('shippings')->where('user_id', 0)->get();
        }

        // جلب طرق التغليف الخاصة بهذا التاجر فقط
        $package_data = DB::table('packages')->where('user_id', $vendorId)->get();
        if ($package_data->isEmpty()) {
            $package_data = DB::table('packages')->where('user_id', 0)->get();
        }

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        return view('frontend.checkout.step1', [
            'products' => $vendorProducts,
            'totalPrice' => $totalPrice,
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'shipping_cost' => 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'vendor_shipping_id' => $vendorId,
            'vendor_packing_id' => $vendorId,
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId
        ]);
    }

    /**
     * Step 1 Submit - Save customer data for specific vendor
     *
     * MULTI-VENDOR LOGIC:
     * - Data saved to vendor_step1_{vendor_id} session (NOT global step1)
     * - Each vendor has independent customer data storage
     *
     * @param Request $request
     * @param int $vendorId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkoutVendorStep1(Request $request, $vendorId)
    {
        $step1 = $request->all();

        $validator = Validator::make($step1, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'customer_country' => 'required|string|max:255',
            'customer_state' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        Session::put('vendor_step1_' . $vendorId, $step1);
        return redirect()->route('front.checkout.vendor.step2', $vendorId);
    }

    /**
     * Step 2 - عرض صفحة اختيار الشحن للتاجر المحدد
     */
    public function checkoutVendorStep2($vendorId)
    {
        if (!Session::has('vendor_step1_' . $vendorId)) {
            return redirect()->route('front.checkout.vendor', $vendorId)->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $step1 = (object) Session::get('vendor_step1_' . $vendorId);

        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        // جلب طرق الشحن الخاصة بهذا التاجر فقط
        $shipping_data = DB::table('shippings')->where('user_id', $vendorId)->get();
        if ($shipping_data->isEmpty()) {
            $shipping_data = DB::table('shippings')->where('user_id', 0)->get();
        }

        // جلب طرق التغليف الخاصة بهذا التاجر فقط
        $package_data = DB::table('packages')->where('user_id', $vendorId)->get();
        if ($package_data->isEmpty()) {
            $package_data = DB::table('packages')->where('user_id', 0)->get();
        }

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        // Get country and state for tax calculation
        $country = Country::where('country_name', $step1->customer_country)->first();
        $isState = isset($step1->customer_state) ? 1 : 0;

        // Group products by vendor (will contain single vendor only)
        // This avoids code duplication in view
        $productsByVendor = $this->groupProductsByVendor($vendorProducts);

        return view('frontend.checkout.step2', [
            'productsByVendor' => $productsByVendor, // Grouped products (single vendor)
            'products' => $vendorProducts, // Keep for backward compatibility
            'totalPrice' => $totalPrice,
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'shipping_cost' => 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'vendor_shipping_id' => $vendorId,
            'vendor_packing_id' => $vendorId,
            'step1' => $step1,
            'country' => $country, // For tax calculation
            'isState' => $isState, // For tax calculation
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId
        ]);
    }

    /**
     * Step 2 Submit - حفظ بيانات الشحن للتاجر المحدد
     */
    public function checkoutVendorStep2Submit(Request $request, $vendorId)
    {
        $step2 = $request->all();
        $oldCart = Session::get('cart');
        $input = Session::get('vendor_step1_' . $vendorId) + $step2;

        // حساب المبلغ الأساسي (قبل الضريبة والشحن) لتطبيق free_above
        $cart = new Cart($oldCart);

        // تصفية منتجات هذا التاجر فقط
        $vendorTotal = 0;
        foreach ($cart->items as $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId == $vendorId) {
                $vendorTotal += (float)($product['price'] ?? 0);
            }
        }

        $baseAmount = $vendorTotal;
        $coupon = Session::has('coupon_vendor_' . $vendorId) ? Session::get('coupon_vendor_' . $vendorId) : 0;
        $baseAmount = $baseAmount - $coupon;

        $shipping_cost_total = 0.0;
        $shipping_names = [];

        if (isset($step2['shipping']) && is_array($step2['shipping'])) {
            foreach (array_values($step2['shipping']) as $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    $parts = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price = (float)($parts[2] ?? 0);
                    $shipping_cost_total += $price;
                    if ($company !== '') {
                        $shipping_names[] = $company;
                    }
                } else {
                    $id = (int)$val;
                    if ($id > 0) {
                        $ship = \App\Models\Shipping::find($id);
                        if ($ship) {
                            $freeAbove = (float)($ship->free_above ?? 0);
                            if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                                $shipping_names[] = $ship->title . ' (Free Shipping)';
                            } else {
                                $shipping_cost_total += (float)$ship->price;
                                $shipping_names[] = $ship->title;
                            }
                        }
                    }
                }
            }
        } elseif (isset($step2['shipping_id'])) {
            $id = (int)$step2['shipping_id'];
            if ($id > 0) {
                $ship = \App\Models\Shipping::find($id);
                if ($ship) {
                    $freeAbove = (float)($ship->free_above ?? 0);
                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        $shipping_names[] = $ship->title . ' (Free Shipping)';
                    } else {
                        $shipping_cost_total += (float)$ship->price;
                        $shipping_names[] = $ship->title;
                    }
                }
            }
        }

        $shipping_name = count($shipping_names) ? implode(' + ', array_unique($shipping_names)) : null;

        // Calculate total for this vendor (products + shipping)
        // This will be used in step3
        $vendorProductsTotal = $vendorTotal; // Products total (may have discount already applied)
        $finalTotal = $vendorProductsTotal + $shipping_cost_total;

        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost'] = $shipping_cost_total;
        $step2['total'] = $finalTotal; // Save total in session for step3

        Session::put('vendor_step2_' . $vendorId, $step2);

        return redirect()->route('front.checkout.vendor.step3', $vendorId);
    }

    /**
     * Step 3 - Display payment methods for specific vendor ONLY
     *
     * CRITICAL MULTI-VENDOR LOGIC:
     * 1. Shows ONLY payment gateways where user_id = vendor_id
     * 2. NO FALLBACK to global/admin payment methods
     * 3. If vendor has no payment methods: ERROR and redirect
     * 4. Filters cart to vendor's products only
     * 5. Calculates totals for this vendor only
     *
     * This ensures each vendor uses ONLY their configured payment methods
     *
     * @param int $vendorId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkoutVendorStep3($vendorId)
    {
        if (!Session::has('vendor_step1_' . $vendorId)) {
            return redirect()->route('front.checkout.vendor', $vendorId)->with('success', __("Please fill up step 1."));
        }
        if (!Session::has('vendor_step2_' . $vendorId)) {
            return redirect()->route('front.checkout.vendor.step2', $vendorId)->with('success', __("Please fill up step 2."));
        }
        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $step1 = (object) Session::get('vendor_step1_' . $vendorId);
        $step2 = (object) Session::get('vendor_step2_' . $vendorId);

        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $productsTotal = $cartData['totalPrice']; // Products only (no shipping)
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        // Get payment gateways for THIS vendor ONLY (NO FALLBACK)
        // CRITICAL: Only show payment methods owned by this vendor
        // scopeHasGateway returns a Collection, so we filter it
        $allGateways = PaymentGateway::scopeHasGateway($this->curr->id);
        $gateways = $allGateways->where('user_id', $vendorId)
            ->where('checkout', 1); // checkout=1 means enabled for checkout

        // If vendor has no payment methods, show error (NO FALLBACK to admin methods)
        if ($gateways->isEmpty()) {
            return redirect()->route('front.cart')->with('unsuccess', __("No payment methods available for this vendor currently."));
        }

        // جلب طرق الشحن
        $shipping_data = DB::table('shippings')->where('user_id', $vendorId)->get();
        if ($shipping_data->isEmpty()) {
            $shipping_data = DB::table('shippings')->where('user_id', 0)->get();
        }

        // جلب طرق التغليف
        $package_data = DB::table('packages')->where('user_id', $vendorId)->get();
        if ($package_data->isEmpty()) {
            $package_data = DB::table('packages')->where('user_id', 0)->get();
        }

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        $paystack = PaymentGateway::whereKeyword('paystack')->first();
        $paystackData = $paystack ? $paystack->convertAutoData() : [];

        // CRITICAL: Use total from step2 (products + shipping + any adjustments)
        // step2 already calculated: vendorTotal + shipping_cost
        // This is the ONLY source of truth for final total
        $finalTotal = $step2->total ?? $productsTotal;

        return view('frontend.checkout.step3', [
            'products' => $vendorProducts,
            'productsTotal' => $productsTotal, // Products only for "Total MRP" display
            'totalPrice' => $finalTotal, // Total including shipping for calculations
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'gateways' => $gateways,
            'shipping_cost' => $step2->shipping_cost ?? 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'vendor_shipping_id' => $vendorId,
            'vendor_packing_id' => $vendorId,
            'paystack' => $paystackData,
            'step2' => $step2,
            'step1' => $step1,
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId
        ]);
    }

    /**
     * Helper: Group products by vendor ID
     *
     * AVOIDS CODE DUPLICATION - This method eliminates the need to repeat
     * vendor grouping logic in Blade views. Used by all checkout steps.
     *
     * @param array $products Cart items
     * @return array Grouped products by vendor_id with metadata
     */
    private function groupProductsByVendor(array $products): array
    {
        $grouped = [];

        foreach ($products as $rowKey => $product) {
            $vendorId = data_get($product, 'item.user_id') ?? 0;

            if (!isset($grouped[$vendorId])) {
                $vendor = \App\Models\User::find($vendorId);
                $grouped[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor ? ($vendor->shop_name ?? $vendor->name) : 'Unknown',
                    'products' => [],
                    'total' => 0,
                    'count' => 0,
                ];
            }

            $grouped[$vendorId]['products'][$rowKey] = $product;
            $grouped[$vendorId]['total'] += (float)($product['price'] ?? 0);
            $grouped[$vendorId]['count'] += (int)($product['qty'] ?? 1);
        }

        return $grouped;
    }
}
