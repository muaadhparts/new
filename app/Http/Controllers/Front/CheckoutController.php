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









    /**
     * ========================================================================
     * CHECKOUT STEP 1 - PROCESS AND SAVE CUSTOMER DATA
     * ========================================================================
     * NO CONFLICTS - NO DUPLICATION - SINGLE SOURCE OF TRUTH
     *
     * منطق معالجة البيانات:
     * 1. يستقبل جميع البيانات من النموذج (يدوية أو من الخريطة)
     * 2. يتحقق من صحة البيانات
     * 3. ينظف Session من خطوات سابقة
     * 4. يحفظ البيانات كمصدر وحيد للحقيقة
     * ========================================================================
     */
    public function checkoutStep1(Request $request)
    {
        // Get all submitted data (manual OR from Google Maps - no conflict)
        $step1 = $request->all();

        // Validation rules
        $validator = Validator::make($step1, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'customer_country' => 'required|string|max:255',
            'customer_state' => 'required|string|max:255',
            'customer_city' => 'required|numeric',
            // Coordinates from Google Maps (optional)
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'customer_name.required' => 'الاسم مطلوب',
            'customer_email.required' => 'البريد الإلكتروني مطلوب',
            'customer_email.email' => 'البريد الإلكتروني غير صحيح',
            'customer_phone.required' => 'رقم الهاتف مطلوب',
            'customer_address.required' => 'العنوان مطلوب',
            'customer_country.required' => 'الدولة مطلوبة',
            'customer_state.required' => 'الولاية مطلوبة',
            'customer_city.required' => 'المدينة مطلوبة',
            'customer_city.numeric' => 'المدينة غير صحيحة',
            'latitude.between' => 'خط العرض غير صحيح',
            'longitude.between' => 'خط الطول غير صحيح',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        // Validate coordinates consistency
        if (($request->filled('latitude') && !$request->filled('longitude')) ||
            (!$request->filled('latitude') && $request->filled('longitude'))) {
            return back()->withErrors([
                'coordinates' => 'يجب إدخال كلا الإحداثيتين معاً'
            ])->withInput();
        }

        // ====================================================================
        // CALCULATE TAX BASED ON COUNTRY/STATE
        // ====================================================================
        $taxRate = 0;
        $taxLocation = '';

        // Get country tax
        $country = \App\Models\Country::where('country_name', $step1['customer_country'])->first();
        if ($country && $country->tax > 0) {
            $taxRate = $country->tax;
            $taxLocation = $country->country_name;
        }

        // Check if state has specific tax (overrides country tax)
        if (!empty($step1['customer_state'])) {
            $state = \App\Models\State::where('state', $step1['customer_state'])
                ->where('country_id', $country->id ?? 0)
                ->first();
            if ($state && $state->tax > 0) {
                $taxRate = $state->tax;
                $taxLocation = $state->state . ', ' . ($country->country_name ?? '');
            }
        }

        // Calculate tax amount per vendor
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $vendorTaxData = [];
        foreach ($cart->items as $product) {
            $vendorId = $product['item']['user_id'] ?? 0;

            if (!isset($vendorTaxData[$vendorId])) {
                $vendorTaxData[$vendorId] = [
                    'subtotal' => 0,
                    'tax_rate' => $taxRate,
                    'tax_amount' => 0,
                ];
            }

            $vendorTaxData[$vendorId]['subtotal'] += (float)($product['price'] ?? 0);
        }

        // Calculate tax amount for each vendor
        foreach ($vendorTaxData as $vendorId => &$taxData) {
            $taxData['tax_amount'] = ($taxData['subtotal'] * $taxData['tax_rate']) / 100;
        }

        // Save tax data to step1
        $step1['tax_rate'] = $taxRate;
        $step1['tax_location'] = $taxLocation;
        $step1['vendor_tax_data'] = $vendorTaxData;

        // Calculate total tax amount
        $totalTaxAmount = array_sum(array_column($vendorTaxData, 'tax_amount'));
        $step1['total_tax_amount'] = $totalTaxAmount;

        // ✅ ADD: Calculate products_total and total_with_tax for unified price display
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $productsTotal = $cart->totalPrice;

        // Apply coupon if exists
        $coupon = Session::has('coupon') ? Session::get('coupon') : 0;
        $productsTotal = $productsTotal - $coupon;

        // Save for price summary component
        $step1['products_total'] = $productsTotal;
        $step1['tax_amount'] = $totalTaxAmount;
        $step1['total_with_tax'] = $productsTotal + $totalTaxAmount;

        // ====================================================================
        // CLEAN SESSION - Prevent any data duplication
        // ====================================================================
        Session::forget(['step2', 'step3']);

        // ====================================================================
        // SAVE TO SESSION - Single Source of Truth
        // ====================================================================
        // All data (manual or from maps) saved here
        // Step 2 and Step 3 will ONLY use this data
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

        // ✅ Calculate packing cost (same logic as vendor checkout)
        $packing_cost_total = 0.0;
        $packing_names = [];

        // Check for array format (multi-vendor) or single value
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            // Multi-vendor format: packeging[vendor_id] = package_id
            foreach ($step2['packeging'] as $vendorId => $packageId) {
                $packId = (int)$packageId;
                if ($packId > 0) {
                    $package = \App\Models\Package::find($packId);
                    if ($package) {
                        $packing_cost_total += (float)$package->price;
                        $packing_names[] = $package->title;
                    }
                }
            }
        } elseif (isset($step2['packeging_id']) && $step2['packeging_id']) {
            // Single vendor format
            $packId = (int)$step2['packeging_id'];
            if ($packId > 0) {
                $package = \App\Models\Package::find($packId);
                if ($package) {
                    $packing_cost_total = (float)$package->price;
                    $packing_names[] = $package->title;
                }
            }
        } elseif (isset($step2['packaging_id']) && $step2['packaging_id']) {
            // Alternative field name
            $packId = (int)$step2['packaging_id'];
            if ($packId > 0) {
                $package = \App\Models\Package::find($packId);
                if ($package) {
                    $packing_cost_total = (float)$package->price;
                    $packing_names[] = $package->title;
                }
            }
        }

        $packing_name = count($packing_names) ? implode(' + ', array_unique($packing_names)) : null;

        // Get tax data from step1
        $step1 = Session::get('step1');
        $taxAmount = $step1['total_tax_amount'] ?? 0;
        $taxRate = $step1['tax_rate'] ?? 0;
        $taxLocation = $step1['tax_location'] ?? '';

        // Calculate final total: products + tax + shipping + packing
        $finalTotal = $baseAmount + $taxAmount + $shipping_cost_total + $packing_cost_total;

        // ✅ حفظ ملخص الشحن والتغليف والضريبة في step2 لاستخدامه في step3
        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost']    = $shipping_cost_total;
        $step2['packing_company']  = $packing_name;
        $step2['packing_cost']     = $packing_cost_total;
        $step2['tax_rate']         = $taxRate;
        $step2['tax_amount']       = $taxAmount;
        $step2['tax_location']     = $taxLocation;
        $step2['total']            = $finalTotal;  // Backward compatibility
        $step2['final_total']      = $finalTotal;  // ✅ Unified naming

        // Save shipping/packing selections for restoration on refresh/back
        $step2['saved_shipping_selections'] = $step2['shipping'] ?? [];
        $step2['saved_packing_selections'] = $step2['packeging'] ?? [];

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

        // ✅ CHECKOUT REQUIREMENT: Always start empty - no auto-selection
        // User must manually select state even if logged in
        $user_state = 0;

        $html_states = '<option value="" > Select State </option>';
        foreach ($states as $state) {
            // ✅ Never pre-select - always empty
            $check = '';

            // تحديد اسم الولاية بناءً على اللغة النشطة باستخدام app()->getLocale()
            $stateDisplayName = (app()->getLocale() == 'ar')
                ? ($state->state_ar ?: $state->state)
                : $state->state;

            $html_states .= '<option value="' . $state->id . '" rel="' . $state->country->id . '" ' . $check . ' >'
              . $stateDisplayName . '</option>';
        }

        return response()->json(["data" => $html_states, "state" => $user_state]);
    }

    public function getCity(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();

        // ✅ CHECKOUT REQUIREMENT: Always start empty - no auto-selection
        // User must manually select city even if logged in
        $user_city = 0;

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            // ✅ Never pre-select - always empty
            $check = '';

            // تحديد اسم المدينة بناءً على اللغة النشطة باستخدام app()->getLocale()
            $cityDisplayName = (app()->getLocale() == 'ar')
                ? ($city->city_name_ar ?: $city->city_name)
                : $city->city_name;

            // تغيير value من city_name إلى city->id
            $html_cities .= '<option value="' . $city->id . '" ' . $check . ' >'
              . $cityDisplayName . '</option>';
        }

        return response()->json(["data" => $html_cities, "city" => $user_city]);
    }

    public function getCityUser(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();

        // ✅ CHECKOUT REQUIREMENT: Always start empty - no auto-selection
        // User must manually select city even if logged in
        $user_city = 0;

        $html_cities = '<option value="" > Select City </option>';
        foreach ($cities as $city) {
            // ✅ Never pre-select - always empty
            $check = '';

            // تحديد اسم المدينة بناءً على اللغة النشطة باستخدام app()->getLocale()
            $cityDisplayName = (app()->getLocale() == 'ar')
                ? ($city->city_name_ar ?: $city->city_name)
                : $city->city_name;

            $html_cities .= '<option value="' . $city->id . '" ' . $check . ' >'
              . $cityDisplayName . '</option>';
        }

        return response()->json(["data" => $html_cities, "city" => $user_city]);
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
    /**
     * Get vendor cart data - READ-ONLY helper method
     *
     * CRITICAL SAFETY:
     * 1. Creates Cart instance for READ-ONLY access
     * 2. Filters products by vendor_id
     * 3. Calculates vendor-specific totals
     * 4. Does NOT modify session
     * 5. Cart instance is discarded after use
     * 6. Returns filtered data as array
     *
     * @param int $vendorId
     * @return array ['vendorProducts', 'totalPrice', 'totalQty', 'digital']
     */
    private function getVendorCartData($vendorId): array
    {
        // READ-ONLY: Get cart from session without modification
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        // Filter products for this vendor only
        $vendorProducts = [];
        foreach ($cart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId == $vendorId) {
                $vendorProducts[$rowKey] = $product;
            }
        }

        // Calculate totals for vendor products only
        $totalPrice = 0;
        $totalQty = 0;
        foreach ($vendorProducts as $product) {
            $totalPrice += (float)($product['price'] ?? 0);
            $totalQty += (int)($product['qty'] ?? 1);
        }

        // Check if all vendor products are digital
        $dp = 1;
        foreach ($vendorProducts as $prod) {
            if ($prod['item']['type'] == 'Physical') {
                $dp = 0;
                break;
            }
        }

        // Return filtered data (Cart instance is discarded - no session modification)
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
     * MULTI-VENDOR SIMPLIFIED LOGIC:
     * 1. Checks authentication (logged-in or guest checkout enabled)
     * 2. Saves vendor_id in session for tracking (checkout_vendor_id)
     * 3. Filters cart to show ONLY this vendor's products (getVendorCartData)
     * 4. Gets ONLY vendor-specific shipping methods (no general cart shipping)
     * 5. Gets ONLY vendor-specific packaging methods (no general cart packaging)
     * 6. Calculates total for THIS vendor only (with vendor-specific coupon)
     * 7. Does NOT call Order::getShipData or Order::getPackingData (avoids cart-wide logic)
     * 8. Does NOT modify auth state - only reads Auth::check()
     *
     * @param int $vendorId The vendor's user_id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkoutVendor($vendorId)
    {
        // ====================================================================
        // ✅ DIAGNOSTIC LOGGING: Track session and auth state at entry
        // ====================================================================
        \Log::info('checkoutVendor: ENTRY POINT', [
            'vendor_id' => $vendorId,
            'session_id' => Session::getId(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'has_cart' => Session::has('cart'),
            'cart_items_count' => Session::has('cart') ? count(Session::get('cart')->items ?? []) : 0,
            'checkout_vendor_id_in_session' => Session::get('checkout_vendor_id'),
            'all_session_keys' => array_keys(Session::all())
        ]);

        if (!Session::has('cart')) {
            \Log::warning('checkoutVendor: No cart in session - redirecting', ['vendor_id' => $vendorId]);
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        // ====================================================================
        // ✅ FIXED: Save vendor_id FIRST - then check auth
        // ====================================================================
        // This prevents losing vendor_id when redirecting to login
        Session::put('checkout_vendor_id', $vendorId);
        Session::save(); // Force save immediately

        \Log::info('checkoutVendor: Saved checkout_vendor_id', [
            'vendor_id' => $vendorId,
            'session_id' => Session::getId(),
            'verification' => Session::get('checkout_vendor_id')
        ]);

        // Check if user is authenticated OR guest checkout is enabled
        if (!Auth::check() && $this->gs->guest_checkout != 1) {
            // ✅ Session already saved above - vendor_id will persist through login redirect
            \Log::warning('checkoutVendor: Not authenticated - Redirecting to login', [
                'vendor_id' => $vendorId,
                'session_id' => Session::getId(),
                'guest_checkout_enabled' => $this->gs->guest_checkout
            ]);
            return redirect()->route('user.login')->with('unsuccess', __('Please login to continue.'));
        }

        // ====================================================================
        // ✅ FIXED: Clean ONLY old steps for THIS vendor (not checkout_vendor_id)
        // ====================================================================
        // Remove only old step data for THIS vendor to allow form refresh
        // Do NOT remove checkout_vendor_id - it must persist
        Session::forget(['vendor_step1_' . $vendorId, 'vendor_step2_' . $vendorId]);

        \Log::info('checkoutVendor: Proceeding to checkout page', [
            'vendor_id' => $vendorId,
            'session_id' => Session::getId(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'user_email' => Auth::check() ? Auth::user()->email : null,
            'checkout_vendor_id_verified' => Session::get('checkout_vendor_id') == $vendorId
        ]);


        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        if (empty($vendorProducts)) {
            return redirect()->route('front.cart')->with('unsuccess', __("No products found for this vendor."));
        }

        // جلب طرق الشحن الخاصة بهذا التاجر فقط (vendor-specific only)
        $shipping_data = \App\Models\Shipping::forVendor($vendorId)->get();

        // جلب طرق التغليف الخاصة بهذا التاجر فقط (vendor-specific only)
        $package_data = DB::table('packages')->where('user_id', $vendorId)->get();
        if ($package_data->isEmpty()) {
            $package_data = DB::table('packages')->where('user_id', 0)->get();
        }

        // Calculate total with vendor-specific coupon
        $total = $totalPrice;
        $coupon = Session::has('coupon_vendor_' . $vendorId) ? Session::get('coupon_vendor_' . $vendorId) : 0;

        if (!Session::has('coupon_total_vendor_' . $vendorId)) {
            $total = $total - $coupon;
        } else {
            $total = Session::get('coupon_total_vendor_' . $vendorId);
            $total = str_replace(',', '', str_replace($this->curr->sign, '', $total));
        }

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        return view('frontend.checkout.step1', [
            'products' => $vendorProducts,
            'productsTotal' => $total, // Products only - no shipping/tax yet
            'totalPrice' => $total, // Backward compatibility
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

        // ✅ BASE VALIDATION
        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'customer_country' => 'required|string|max:255',
            'customer_state' => 'required|string|max:255',
            'customer_city' => 'required|numeric',
        ];

        // ✅ IF USER WANTS TO CREATE ACCOUNT - ADD PASSWORD VALIDATION
        if (isset($step1['create_account']) && $step1['create_account'] == 1) {
            $rules['password'] = 'required|string|min:6|confirmed';
            $rules['password_confirmation'] = 'required|string|min:6';
        }

        $validator = Validator::make($step1, $rules, [
            'password.required' => __('Password is required when creating an account'),
            'password.min' => __('Password must be at least 6 characters'),
            'password.confirmed' => __('Password confirmation does not match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        // ====================================================================
        // ✅ CREATE ACCOUNT DURING CHECKOUT (IF REQUESTED)
        // ====================================================================
        if (isset($step1['create_account']) && $step1['create_account'] == 1 && !Auth::check()) {
            // Check if email already exists
            $existingUser = \App\Models\User::where('email', $step1['customer_email'])->first();

            if ($existingUser) {
                return back()->with('unsuccess', __('An account with this email already exists. Please login.'))->withInput();
            }

            // Create new user account
            $user = new \App\Models\User();
            $user->name = $step1['customer_name'];
            $user->email = $step1['customer_email'];
            $user->phone = $step1['customer_phone'];
            $user->address = $step1['customer_address'];
            $user->zip = $step1['customer_zip'];
            $user->country = $step1['customer_country'];

            // Get state_id and city_id from names
            $country = \App\Models\Country::where('country_name', $step1['customer_country'])->first();
            if ($country) {
                $state = \App\Models\State::where('state', $step1['customer_state'])
                    ->where('country_id', $country->id)
                    ->first();
                if ($state) {
                    $user->state_id = $state->id;
                }
            }
            $user->city_id = $step1['customer_city']; // Already numeric

            $user->password = bcrypt($step1['password']);
            $user->email_verified = 'Yes'; // Auto-verify during checkout
            $user->affilate_code = null;
            $user->is_provider = 0;
            $user->save();

            // ✅ LOGIN THE USER IMMEDIATELY
            Auth::login($user);

            \Log::info('Checkout: Account created and logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'vendor_id' => $vendorId
            ]);
        }

        // ====================================================================
        // CALCULATE TAX BASED ON COUNTRY/STATE
        // ====================================================================
        $taxRate = 0;
        $taxLocation = '';

        // Get country tax
        $country = \App\Models\Country::where('country_name', $step1['customer_country'])->first();
        if ($country && $country->tax > 0) {
            $taxRate = $country->tax;
            $taxLocation = $country->country_name;
        }

        // Check if state has specific tax (overrides country tax)
        if (!empty($step1['customer_state'])) {
            $state = \App\Models\State::where('state', $step1['customer_state'])
                ->where('country_id', $country->id ?? 0)
                ->first();
            if ($state && $state->tax > 0) {
                $taxRate = $state->tax;
                $taxLocation = $state->state . ', ' . ($country->country_name ?? '');
            }
        }

        // Calculate tax amount for this vendor only
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $vendorSubtotal = 0;
        foreach ($cart->items as $product) {
            $productVendorId = $product['item']['user_id'] ?? 0;
            if ($productVendorId == $vendorId) {
                $vendorSubtotal += (float)($product['price'] ?? 0);
            }
        }

        $taxAmount = ($vendorSubtotal * $taxRate) / 100;

        // Apply coupon if exists (vendor checkout may have coupons too)
        $coupon = Session::has('coupon') ? Session::get('coupon') : 0;
        $productsTotal = $vendorSubtotal - $coupon;

        // Save tax data to step1
        $step1['tax_rate'] = $taxRate;
        $step1['tax_location'] = $taxLocation;
        $step1['tax_amount'] = $taxAmount;
        $step1['vendor_subtotal'] = $vendorSubtotal;

        // ✅ ADD: For unified price display component
        $step1['products_total'] = $productsTotal;
        $step1['total_with_tax'] = $productsTotal + $taxAmount;

        Session::put('vendor_step1_' . $vendorId, $step1);
        Session::save(); // Ensure session is saved before redirect
        return redirect()->route('front.checkout.vendor.step2', $vendorId);
    }

    /**
     * Step 2 - عرض صفحة اختيار الشحن للتاجر المحدد
     *
     * VENDOR-ONLY LOGIC:
     * 1. Loads vendor_step1 data from session (vendor-specific)
     * 2. Filters cart to vendor products only (getVendorCartData)
     * 3. Gets vendor-specific shipping & packaging methods only
     * 4. Does NOT use general cart shipping/packaging logic
     * 5. Preserves auth state - no modifications
     *
     * @param int $vendorId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkoutVendorStep2($vendorId)
    {

        if (!Session::has('vendor_step1_' . $vendorId)) {
            \Log::warning('checkoutVendorStep2: Missing step1 data', ['vendor_id' => $vendorId]);
            return redirect()->route('front.checkout.vendor', $vendorId)->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
            \Log::warning('checkoutVendorStep2: Missing cart', ['vendor_id' => $vendorId]);
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $step1 = (object) Session::get('vendor_step1_' . $vendorId);

        // ✅ Retrieve step2 data if exists (for refresh or back from step3)
        $step2 = Session::has('vendor_step2_' . $vendorId)
            ? (object) Session::get('vendor_step2_' . $vendorId)
            : null;

        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        // جلب طرق الشحن الخاصة بهذا التاجر فقط
        $shipping_data = \App\Models\Shipping::forVendor($vendorId)->get();

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
            'productsTotal' => $totalPrice, // Products only - shipping/packing added dynamically
            'totalPrice' => $totalPrice, // Backward compatibility
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
            'step2' => $step2, // ✅ Pass saved step2 data to view
            'country' => $country, // For tax calculation
            'isState' => $isState, // For tax calculation
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId
        ]);
    }

    /**
     * Step 2 Submit - حفظ بيانات الشحن للتاجر المحدد
     *
     * VENDOR-ONLY LOGIC:
     * 1. Reads cart (READ-ONLY) to calculate vendor total
     * 2. Filters products by vendor_id
     * 3. Calculates shipping cost for this vendor only
     * 4. Saves to vendor_step2_{vendorId} session (not global step2)
     * 5. Does NOT modify cart or auth state
     *
     * @param Request $request
     * @param int $vendorId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkoutVendorStep2Submit(Request $request, $vendorId)
    {
        $step2 = $request->all();
        $oldCart = Session::get('cart');

        // Check if cart contains physical products (digital products don't need shipping)
        $cart = new Cart($oldCart);
        $hasPhysicalProducts = false;
        foreach ($cart->items as $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;
            if ($productVendorId == $vendorId) {
                // Check if this is a physical product (dp = 0)
                if (isset($product['dp']) && $product['dp'] == 0) {
                    $hasPhysicalProducts = true;
                    break;
                }
            }
        }

        // ✅ VALIDATION: Check if shipping method is selected (for physical products only)
        if ($hasPhysicalProducts) {
            $hasShipping = false;

            // Check for array format (multi-vendor shipping)
            if (isset($step2['shipping']) && is_array($step2['shipping'])) {
                // Check if this vendor has a shipping selection
                if (isset($step2['shipping'][$vendorId]) && !empty($step2['shipping'][$vendorId])) {
                    $hasShipping = true;
                }
            }
            // Check for single shipping_id
            elseif (isset($step2['shipping_id']) && !empty($step2['shipping_id'])) {
                $hasShipping = true;
            }

            // If no shipping method selected, redirect back with error
            if (!$hasShipping) {
                return redirect()->back()
                    ->with('unsuccess', __('Please select a shipping method before continuing.'))
                    ->withInput();
            }
        }

        $input = Session::get('vendor_step1_' . $vendorId) + $step2;

        // NOTE: Creating Cart instance for READ-ONLY access
        // This does NOT modify session - only used to read and filter vendor products
        $cart = new Cart($oldCart);

        // تصفية منتجات هذا التاجر فقط (vendor products only)
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

        // ✅ Calculate packing cost
        $packing_cost_total = 0.0;
        $packing_names = [];


        // ✅ FIXED: Check for array format first (vendor checkout multi-vendor mode)
        // Modal sends: packeging[vendor_id] = package_id
        $packId = null;
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            // Format: packeging[vendor_id] = package_id
            $packId = (int)($step2['packeging'][$vendorId] ?? 0);
        } elseif (isset($step2['vendor_packing_id']) && $step2['vendor_packing_id']) {
            $packId = (int)$step2['vendor_packing_id'];
        } elseif (isset($step2['packeging_id']) && $step2['packeging_id']) {
            $packId = (int)$step2['packeging_id'];
        } elseif (isset($step2['packaging_id']) && $step2['packaging_id']) {
            $packId = (int)$step2['packaging_id'];
        }

        if ($packId && $packId > 0) {
            $package = \App\Models\Package::find($packId);
            if ($package) {
                $packing_cost_total = (float)$package->price;
                $packing_names[] = $package->title;
            }
        }

        $packing_name = count($packing_names) ? implode(' + ', array_unique($packing_names)) : null;

        // Get tax data from vendor step1
        $step1 = Session::get('vendor_step1_' . $vendorId);
        $taxAmount = $step1['tax_amount'] ?? 0;
        $taxRate = $step1['tax_rate'] ?? 0;
        $taxLocation = $step1['tax_location'] ?? '';

        // ✅ Calculate COMPLETE total for this vendor (products + tax + shipping + packing)
        // This will be used in step3 and payment
        $vendorProductsTotal = $vendorTotal; // Products total (may have discount already applied)
        $finalTotal = $vendorProductsTotal + $taxAmount + $shipping_cost_total + $packing_cost_total;

        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost'] = $shipping_cost_total;
        $step2['packing_company'] = $packing_name;
        $step2['packing_cost'] = $packing_cost_total;
        $step2['tax_rate'] = $taxRate;
        $step2['tax_amount'] = $taxAmount;
        $step2['tax_location'] = $taxLocation;
        $step2['total'] = $finalTotal;            // Backward compatibility
        $step2['final_total'] = $finalTotal;      // ✅ Unified naming

        // ✅ Save raw shipping/packing selections for restore on refresh/back
        if (isset($step2['shipping']) && is_array($step2['shipping'])) {
            $step2['saved_shipping_selections'] = $step2['shipping'];
        }
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            $step2['saved_packing_selections'] = $step2['packeging'];
        }

        Session::put('vendor_step2_' . $vendorId, $step2);
        Session::save(); // Ensure session is saved before redirect

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
        $shipping_data = \App\Models\Shipping::forVendor($vendorId)->get();

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
            'productsTotal' => $productsTotal, // Products only - ALWAYS for "Total MRP" display
            'totalPrice' => $productsTotal, // Keep same as productsTotal for backward compatibility
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
            'step2' => $step2, // CRITICAL: Contains pre-calculated total (products + tax + shipping)
            'step1' => $step1,
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId
        ]);
    }

    /**
     * Helper: Group products by vendor ID - READ-ONLY
     *
     * SAFETY:
     * 1. Receives array of products (already filtered)
     * 2. Groups them by vendor_id
     * 3. Does NOT access session
     * 4. Does NOT modify cart
     * 5. Pure data transformation
     *
     * Used by checkout steps to organize display.
     *
     * @param array $products Cart items (already filtered if vendor-specific)
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

    /**
     * إزالة منتجات تاجر معين من السلة بعد اكتمال الدفع
     *
     * ⚠️ WARNING: This method MODIFIES cart session
     * ONLY call this AFTER successful payment completion
     *
     * USAGE:
     * 1. Called by payment controllers after payment success
     * 2. Removes vendor's products from cart
     * 3. Keeps other vendors' products intact
     * 4. Cleans up vendor-specific session data
     * 5. Does NOT affect auth state
     *
     * @param int $vendorId معرف التاجر
     * @return void
     */
    public static function removeVendorProductsFromCart($vendorId)
    {
        if (!Session::has('cart')) {
            return;
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        // تصفية المنتجات: الاحتفاظ فقط بمنتجات التجار الآخرين
        $remainingItems = [];
        foreach ($cart->items as $rowKey => $product) {
            $productVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'item.vendor_user_id') ?? 0;

            // إذا لم يكن من نفس التاجر، نحتفظ به
            if ($productVendorId != $vendorId) {
                $remainingItems[$rowKey] = $product;
            }
        }

        // تحديث السلة
        $cart->items = $remainingItems;

        // إعادة حساب الإجماليات
        $totalQty = 0;
        $totalPrice = 0.0;
        foreach ($cart->items as $item) {
            $totalQty += (int)($item['qty'] ?? 0);
            $totalPrice += (float)($item['price'] ?? 0);
        }
        $cart->totalQty = $totalQty;
        $cart->totalPrice = $totalPrice;

        // حفظ أو حذف السلة
        if (empty($cart->items)) {
            Session::forget('cart');
        } else {
            Session::put('cart', $cart);
        }

        // حذف بيانات الخطوات الخاصة بهذا التاجر
        Session::forget('vendor_step1_' . $vendorId);
        Session::forget('vendor_step2_' . $vendorId);
        Session::forget('coupon_vendor_' . $vendorId);
        Session::forget('checkout_vendor_id');
    }

    /* ===================== Tryoto Dynamic Location API ===================== */

    /**
     * Verify city with Tryoto and auto-save if supported
     *
     * POST /tryoto/verify-city
     * Body: { country_id, state_id, city_name }
     */
    public function verifyTryotoCity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
            'state_id' => 'required|integer|exists:states,id',
            'city_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $service = new \App\Services\TryotoLocationService();

        $result = $service->verifyAndSaveLocation(
            $request->country_id,
            $request->state_id,
            $request->city_name
        );

        return response()->json([
            'success' => $result['verified'],
            'data' => $result,
            'message' => $result['message']
        ]);
    }

    /**
     * Verify city by ID (when selected from dropdown)
     *
     * POST /tryoto/verify-city-id
     * Body: { city_id }
     */
    public function verifyTryotoCityById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|integer|exists:cities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $city = City::with(['state', 'country'])->find($request->city_id);

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        $service = new \App\Services\TryotoLocationService();

        // Verify this city is still supported by Tryoto
        $verification = $service->verifyCitySupport($city->city_name);

        return response()->json([
            'success' => $verification['supported'],
            'data' => [
                'city_id' => $city->id,
                'city_name' => $city->city_name,
                'city_name_ar' => $city->city_name_ar,
                'state' => $city->state->state,
                'country' => $city->country->country_name,
                'verified' => $verification['supported'],
                'companies' => $verification['company_count'] ?? 0
            ],
            'message' => $verification['supported']
                ? 'City verified with Tryoto'
                : 'City not supported by Tryoto shipping'
        ]);
    }
}
