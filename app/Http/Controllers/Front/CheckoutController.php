<?php

namespace App\Http\Controllers\Front;

use App\Helpers\PriceHelper;
use App\Http\Controllers\MyFatoorahController;
use App\Models\Cart;use App\Models\City;
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
     * حفظ اختيار الشحن في Session (يُستدعى عبر AJAX)
     */
    public function saveShippingSelection(Request $request)
    {
        $shippingSelection = $request->input('shipping_selection', []);
        Session::put('shipping_selection', $shippingSelection);

        return response()->json(['success' => true]);
    }

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

        $html_states = '<option value="" > Select State </option>';
        foreach ($states as $state) {
            if ($state->id == $user_state) {
                $check = 'selected';
            } else {

                $check = '';
            }
            // $html_states .= '<option value="' . $state->id . '"   rel="' . $state->country->id . '" ' . $check . ' >' . $state->state . '</option>';
            $html_states .= '<option value="' . $state->id . '" rel="' . $state->country->id . '" ' . $check . ' >' 
              . __('states.' . $state->state) . '</option>';

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

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            if ($city->id == $user_city) {
                $check = 'selected';
            } else {
                $check = '';
            }
            // $html_cities .= '<option value="' . $city->city_name . '"   ' . $check . ' >' . $city->city_name . '</option>';
            $html_cities .= '<option value="' . $city->city_name . '" ' . $check . ' >' 
              . __('cities.' . $city->city_name) . '</option>';

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

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            if ($city->id == $user_city) {
                $check = 'selected';
            } else {
                $check = '';
            }
            // $html_cities .= '<option value="' . $city->id . '"   ' . $check . ' >' . $city->city_name . '</option>';
            $html_cities .= '<option value="' . $city->id . '" ' . $check . ' >' 
              . __('cities.' . $city->city_name) . '</option>';

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
}
