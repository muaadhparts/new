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
use App\Services\VendorCartService;
use App\Services\CheckoutDataService;
use App\Services\ShippingCalculatorService;
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
                // No global packaging - empty collection
                $package_data = collect();
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
                // No global packaging - empty collection
                $package_data = collect();
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

            // N+1 FIX: Pre-load all vendor data
            $step2Data = $this->prepareStep2VendorData($cart->items, $step1);

            return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1, 'vendorData' => $step2Data['vendorData'], 'preloadedCountry' => $step2Data['country']]);
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
                // N+1 FIX: Pre-load all vendor data
                $step2Data = $this->prepareStep2VendorData($cart->items, $step1 ?? null);

                foreach ($products as $prod) {
                    if ($prod['item']['type'] != 'Physical') {
                        if (!Auth::check()) {
                            $ck = 1;
                            return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'vendorData' => $step2Data['vendorData'], 'preloadedCountry' => $step2Data['country']]);
                        }
                    }
                }
//                dd($cart->items);
                return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1, 'vendorData' => $step2Data['vendorData'], 'preloadedCountry' => $step2Data['country']]);
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
                // N+1 FIX: Pre-load all vendor data
                $step2Data = $this->prepareStep2VendorData($cart->items, $step1);

                return view('frontend.checkout.step2', ['products' => $cart->items, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'vendor_shipping_id' => $vendor_shipping_id, 'vendor_packing_id' => $vendor_packing_id, 'step1' => $step1, 'vendorData' => $step2Data['vendorData'], 'preloadedCountry' => $step2Data['country']]);
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

        // Validation rules - Support both map selection (IDs) and legacy (names)
        $validator = Validator::make($step1, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            // Location - either from map (IDs) or legacy (names)
            'country_id' => 'nullable|numeric|exists:countries,id',
            'city_id' => 'nullable|numeric|exists:cities,id',
            'customer_country' => 'required_without:country_id|nullable|string|max:255',
            'customer_state' => 'nullable|string|max:255',
            'customer_city' => 'required_without:city_id|nullable|numeric',
            // Coordinates from Google Maps
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'customer_name.required' => 'الاسم مطلوب',
            'customer_email.required' => 'البريد الإلكتروني مطلوب',
            'customer_email.email' => 'البريد الإلكتروني غير صحيح',
            'customer_phone.required' => 'رقم الهاتف مطلوب',
            'customer_address.required' => 'العنوان مطلوب',
            'customer_country.required_without' => 'الدولة مطلوبة',
            'customer_city.required_without' => 'المدينة مطلوبة',
            'country_id.exists' => 'الدولة غير صحيحة',
            'city_id.exists' => 'المدينة غير صحيحة',
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
        // CALCULATE TAX BASED ON COUNTRY/CITY
        // ====================================================================
        $taxRate = 0;
        $taxLocation = '';

        // Get country - try by ID first (from map), then by name (legacy)
        $country = null;
        if (!empty($step1['country_id'])) {
            $country = \App\Models\Country::find($step1['country_id']);
        }
        if (!$country && !empty($step1['customer_country'])) {
            $country = \App\Models\Country::where('country_name', $step1['customer_country'])->first();
        }

        if ($country && $country->tax > 0) {
            $taxRate = $country->tax;
            $taxLocation = $country->country_name;
        }

        // Check if city has specific tax (overrides country tax)
        $city = null;
        if (!empty($step1['city_id'])) {
            $city = \App\Models\City::find($step1['city_id']);
            if ($city && $city->tax > 0) {
                $taxRate = $city->tax;
                $taxLocation = $city->city_name . ', ' . ($country->country_name ?? '');
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

        // ✅ Calculate products_total and total_with_tax for unified price display
        // IMPORTANT: products_total = السعر الأصلي بدون خصم الكوبون
        // الكوبون يُطرح في الحساب النهائي فقط
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $productsTotal = $cart->totalPrice; // السعر الأصلي - لا نطرح الكوبون هنا!

        // Save for price summary component
        $step1['products_total'] = $productsTotal;          // السعر الأصلي
        $step1['tax_amount'] = $totalTaxAmount;
        $step1['total_with_tax'] = $productsTotal + $totalTaxAmount;  // قبل الكوبون

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

        // ✅ Track original shipping cost for free shipping display
        $original_shipping_cost = 0.0;
        $is_free_shipping = false;

        // حالة الشحن المتعدد: shipping[vendor_id] = {id أو deliveryOptionId#Company#price}
        if (isset($step2['shipping']) && is_array($step2['shipping'])) {

            foreach ($step2['shipping'] as $vendorId => $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // تنسيق Tryoto: deliveryOptionId#CompanyName#price
                    $parts   = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price   = (float)($parts[2] ?? 0);
                    $original_shipping_cost += $price;

                    // ✅ تطبيق free_above من إعدادات Tryoto للتاجر
                    $vendorTryotoShipping = \App\Models\Shipping::where('user_id', $vendorId)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $vendorTryotoShipping ? (float)$vendorTryotoShipping->free_above : 0;

                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        // الشحن مجاني
                        $shipping_names[] = $company . ' (Free Shipping)';
                        $is_free_shipping = true;
                    } else {
                        $shipping_cost_total += $price;
                        if ($company !== '') {
                            $shipping_names[] = $company;
                        }
                    }
                } else {
                    // تنسيق ID عادي من جدول shippings - تطبيق منطق free_above
                    $id = (int)$val;
                    if ($id > 0) {
                        $ship = \App\Models\Shipping::find($id);
                        if ($ship) {
                            $original_shipping_cost += (float)$ship->price;
                            // تطبيق منطق free_above
                            $freeAbove = (float)($ship->free_above ?? 0);
                            if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                                // الشحن مجاني - لا نضيف السعر
                                $shipping_names[] = $ship->title . ' (Free Shipping)';
                                $is_free_shipping = true;
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
                    $original_shipping_cost = (float)$ship->price;
                    // تطبيق منطق free_above
                    $freeAbove = (float)($ship->free_above ?? 0);
                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        // الشحن مجاني - لا نضيف السعر
                        $shipping_names[] = $ship->title . ' (Free Shipping)';
                        $is_free_shipping = true;
                    } else {
                        $shipping_cost_total += (float)$ship->price;
                        $shipping_names[] = $ship->title;
                    }
                }
            }
        }

        // دمج الأسماء (إن وجدت)
        $shipping_name = count($shipping_names) ? implode(' + ', array_unique($shipping_names)) : null;
        $free_shipping_discount = $is_free_shipping ? $original_shipping_cost : 0;

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

        // ✅ Get coupon data (supports both regular and vendor checkout)
        $checkoutVendorId = Session::get('checkout_vendor_id');
        if ($checkoutVendorId) {
            $couponAmount = Session::get('coupon_vendor_' . $checkoutVendorId, 0);
            $couponCode = Session::get('coupon_code_vendor_' . $checkoutVendorId, '');
            $couponId = Session::get('coupon_id_vendor_' . $checkoutVendorId, null);
            $couponPercentage = Session::get('coupon_percentage_vendor_' . $checkoutVendorId, '');
        } else {
            $couponAmount = Session::get('coupon', 0);
            $couponCode = Session::get('coupon_code', '');
            $couponId = Session::get('coupon_id', null);
            $couponPercentage = Session::get('coupon_percentage', '');
        }

        // ✅ Calculate totals
        // subtotal_before_coupon = products + tax + shipping + packing
        $subtotalBeforeCoupon = $baseAmount + $taxAmount + $shipping_cost_total + $packing_cost_total;

        // final_total = subtotal - coupon
        $finalTotal = $subtotalBeforeCoupon - $couponAmount;

        // ✅ حفظ ملخص الشحن والتغليف والضريبة في step2 لاستخدامه في step3
        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost']    = $shipping_cost_total;
        $step2['original_shipping_cost'] = $original_shipping_cost;  // السعر قبل الخصم
        $step2['is_free_shipping'] = $is_free_shipping;              // هل الشحن مجاني
        $step2['free_shipping_discount'] = $free_shipping_discount;  // قيمة خصم الشحن المجاني
        $step2['packing_company']  = $packing_name;
        $step2['packing_cost']     = $packing_cost_total;
        $step2['tax_rate']         = $taxRate;
        $step2['tax_amount']       = $taxAmount;
        $step2['tax_location']     = $taxLocation;

        // ✅ Coupon data saved in step2 for step3 display
        $step2['coupon_amount']    = $couponAmount;
        $step2['coupon_code']      = $couponCode;
        $step2['coupon_id']        = $couponId;
        $step2['coupon_percentage'] = $couponPercentage;
        $step2['coupon_applied']   = $couponAmount > 0;  // Flag to prevent double subtraction

        // ✅ Totals
        $step2['subtotal_before_coupon'] = $subtotalBeforeCoupon;  // قبل طرح الكوبون
        $step2['total']            = $finalTotal;  // Backward compatibility
        $step2['final_total']      = $finalTotal;  // الإجمالي النهائي بعد كل شيء

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
            $shipping_data = collect(); // No global shipping
        }

        if ($this->gs->multiple_shipping == 1) {
            $pack_data = Order::getPackingData($cart);
            $package_data = $pack_data['package_data'];
            $vendor_packing_id = $pack_data['vendor_packing_id'];
        } else {
            $package_data = collect(); // No global packaging
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
        // ✅ N+1 FIX: Pre-load country for step3
        $preloadedCountry = CheckoutDataService::loadCountry($step1);

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
            'preloadedCountry'    => $preloadedCountry,
        ]);
    }

    public function getState($country_id)
    {
        // States table removed - return empty states
        $html_states = '<option value="" > Select State </option>';
        return response()->json(["data" => $html_states, "state" => 0]);
    }

    public function getCity(Request $request)
    {
        $cities = City::where('country_id', $request->country_id)->get();

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
        $cities = City::where('country_id', $request->country_id)->get();

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

        // Ensure vendorId is integer for comparison
        $vendorId = (int)$vendorId;

        // Filter products for this vendor only
        $vendorProducts = [];
        foreach ($cart->items as $rowKey => $product) {
            // ✅ Get vendor ID - Cart stores 'user_id' at root level (see Cart::add/addnum)
            // Priority: direct user_id > item.user_id > item.vendor_user_id
            $productVendorId = 0;

            // Path 1: direct user_id (Cart model stores this at root level)
            if (isset($product['user_id'])) {
                $productVendorId = (int)$product['user_id'];
            }
            // Path 2: item.user_id (Product object property)
            elseif (isset($product['item']) && is_object($product['item']) && isset($product['item']->user_id)) {
                $productVendorId = (int)$product['item']->user_id;
            }
            // Path 3: item as array
            elseif (isset($product['item']) && is_array($product['item']) && isset($product['item']['user_id'])) {
                $productVendorId = (int)$product['item']['user_id'];
            }
            // Path 4: vendor_user_id (fallback)
            elseif (isset($product['item']) && is_object($product['item']) && isset($product['item']->vendor_user_id)) {
                $productVendorId = (int)$product['item']->vendor_user_id;
            }

            if ($productVendorId === $vendorId) {
                // إضافة بيانات الخصم والأبعاد باستخدام VendorCartService
                $mpId = data_get($product, 'item.merchant_product_id')
                    ?? data_get($product, 'merchant_product_id')
                    ?? 0;
                $qty = (int)($product['qty'] ?? 1);

                if ($mpId) {
                    // حساب خصم الجملة
                    $bulkDiscount = VendorCartService::calculateBulkDiscount($mpId, $qty);
                    $product['bulk_discount'] = $bulkDiscount;

                    // جلب الأبعاد (بدون fallback)
                    $dimensions = VendorCartService::getProductDimensions($mpId);
                    $product['dimensions'] = $dimensions;
                    $product['row_weight'] = $dimensions['weight'] ? $dimensions['weight'] * $qty : null;
                }

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
            if (data_get($prod, 'item.type') == 'Physical') {
                $dp = 0;
                break;
            }
        }

        // حساب بيانات الشحن للتاجر باستخدام VendorCartService
        $shippingData = VendorCartService::calculateVendorShipping($vendorId, $cart->items);

        // Return filtered data (Cart instance is discarded - no session modification)
        return [
            'vendorProducts' => $vendorProducts,
            'totalPrice' => $totalPrice,
            'totalQty' => $totalQty,
            'digital' => $dp,
            'shipping_data' => $shippingData,
            'has_complete_shipping_data' => $shippingData['has_complete_data'],
            'missing_shipping_data' => $shippingData['missing_data'],
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
        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        // ====================================================================
        // ✅ مسح بيانات التاجر السابق عند بدء checkout لتاجر جديد
        // ====================================================================
        $previousVendorId = Session::get('checkout_vendor_id');
        if ($previousVendorId && $previousVendorId != $vendorId) {
            // مسح tempcart و temporder من التاجر السابق
            Session::forget(['tempcart', 'temporder']);
            // مسح بيانات steps التاجر السابق
            Session::forget([
                'vendor_step1_' . $previousVendorId,
                'vendor_step2_' . $previousVendorId,
                'coupon_vendor_' . $previousVendorId,
                'coupon_total_vendor_' . $previousVendorId,
            ]);
        }

        // ====================================================================
        // ✅ FIXED: Save vendor_id FIRST - then check auth
        // ====================================================================
        // This prevents losing vendor_id when redirecting to login
        Session::put('checkout_vendor_id', $vendorId);
        Session::save(); // Force save immediately

        // Check if user is authenticated OR guest checkout is enabled
        if (!Auth::check() && $this->gs->guest_checkout != 1) {
            return redirect()->route('user.login')->with('unsuccess', __('Please login to continue.'));
        }

        // Clean old step data for THIS vendor to allow form refresh
        Session::forget(['vendor_step1_' . $vendorId, 'vendor_step2_' . $vendorId]);

        // Get vendor cart data using helper method (avoids code duplication)
        $cartData = $this->getVendorCartData($vendorId);
        $vendorProducts = $cartData['vendorProducts'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];
        $vendorShippingData = $cartData['shipping_data'];

        if (empty($vendorProducts)) {
            return redirect()->route('front.cart')->with('unsuccess', __("No products found for this vendor."));
        }

        // جلب طرق الشحن الخاصة بهذا التاجر فقط (vendor-specific only)
        $shipping_data = \App\Models\Shipping::forVendor($vendorId)->get();

        // جلب طرق التغليف الخاصة بهذا التاجر فقط (vendor-specific only)
        $package_data = DB::table('packages')->where('user_id', $vendorId)->get();
        // No fallback to user 0 - if vendor has no packages, collection will be empty

        // productsTotal = RAW price (no coupon deduction)
        $productsTotal = $totalPrice;

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        return view('frontend.checkout.step1', [
            'products' => $vendorProducts,
            'productsTotal' => $productsTotal, // ✅ RAW products total (no coupon)
            'totalPrice' => $productsTotal, // Backward compatibility
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
            'vendor_id' => $vendorId,
            // بيانات الشحن الموحدة من VendorCartService
            'vendor_shipping_data' => $vendorShippingData,
            'has_complete_shipping_data' => $cartData['has_complete_shipping_data'],
            'missing_shipping_data' => $cartData['missing_shipping_data'],
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

        // BASE VALIDATION - Support both map selection (IDs) and legacy (names)
        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            // Location - either from map (IDs) or legacy (names)
            'country_id' => 'nullable|numeric|exists:countries,id',
            'city_id' => 'nullable|numeric|exists:cities,id',
            'customer_country' => 'required_without:country_id|nullable|string|max:255',
            'customer_state' => 'nullable|string|max:255',
            'customer_city' => 'required_without:city_id|nullable|numeric',
            // Coordinates from Google Maps
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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
            $user->city_id = $step1['customer_city']; // Already numeric

            $user->password = bcrypt($step1['password']);
            $user->email_verified = 'Yes'; // Auto-verify during checkout
            $user->affilate_code = null;
            $user->is_provider = 0;
            $user->save();

            // LOGIN THE USER IMMEDIATELY WITHOUT SESSION REGENERATION
            // Using 'false' as second parameter to prevent session regeneration
            // This prevents CSRF token mismatch during checkout
            Auth::login($user, true);
        }

        // ====================================================================
        // CALCULATE TAX BASED ON COUNTRY/CITY
        // ====================================================================
        $taxRate = 0;
        $taxLocation = '';

        // Get country - try by ID first (from map), then by name (legacy)
        $country = null;
        if (!empty($step1['country_id'])) {
            $country = \App\Models\Country::find($step1['country_id']);
        }
        if (!$country && !empty($step1['customer_country'])) {
            $country = \App\Models\Country::where('country_name', $step1['customer_country'])->first();
        }

        if ($country && $country->tax > 0) {
            $taxRate = $country->tax;
            $taxLocation = $country->country_name;
        }

        // Check if city has specific tax (overrides country tax)
        $city = null;
        if (!empty($step1['city_id'])) {
            $city = \App\Models\City::find($step1['city_id']);
            if ($city && $city->tax > 0) {
                $taxRate = $city->tax;
                $taxLocation = $city->city_name . ', ' . ($country->country_name ?? '');
            }
        }

        // Calculate tax amount for this vendor only
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $vendorSubtotal = 0;

        // Calculate subtotal for this vendor's products only
        // Cart stores 'user_id' at root level AND item can be object or array
        foreach ($cart->items as $product) {
            // First check direct user_id (Cart model stores this at root level)
            $productVendorId = 0;
            if (isset($product['user_id'])) {
                $productVendorId = (int)$product['user_id'];
            } elseif (isset($product['item']) && is_object($product['item']) && isset($product['item']->user_id)) {
                $productVendorId = (int)$product['item']->user_id;
            } elseif (isset($product['item']) && is_array($product['item']) && isset($product['item']['user_id'])) {
                $productVendorId = (int)$product['item']['user_id'];
            }

            if ($productVendorId == (int)$vendorId) {
                $vendorSubtotal += (float)($product['price'] ?? 0);
            }
        }

        $taxAmount = ($vendorSubtotal * $taxRate) / 100;

        // ========================================================================
        // ✅ UNIFIED PRICE CALCULATION - DO NOT SUBTRACT COUPON HERE!
        // ========================================================================
        // products_total = RAW sum of products (NEVER changes)
        // Coupon is handled separately and displayed as discount line

        // Save tax data to step1
        $step1['tax_rate'] = $taxRate;
        $step1['tax_location'] = $taxLocation;
        $step1['tax_amount'] = $taxAmount;
        $step1['vendor_subtotal'] = $vendorSubtotal;

        // ✅ products_total = Original price WITHOUT coupon deduction
        $step1['products_total'] = $vendorSubtotal;
        $step1['total_with_tax'] = $vendorSubtotal + $taxAmount;

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
            return redirect()->route('front.checkout.vendor', $vendorId)->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
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
        // No fallback to user 0 - if vendor has no packages, collection will be empty

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        // N+1 FIX: Pre-load all vendor data
        $step2VendorData = $this->prepareStep2VendorData($vendorProducts, $step1);
        $country = $step2VendorData['country'];
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
            'country' => $country, // For tax calculation (N+1 FIX)
            'preloadedCountry' => $country, // Alias for Blade
            'isState' => $isState, // For tax calculation
            'is_vendor_checkout' => true,
            'vendor_id' => $vendorId,
            'vendorData' => $step2VendorData['vendorData'], // N+1 FIX
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

        // ========================================================================
        // ✅ UNIFIED: products_total is the RAW total (no coupon subtracted)
        // ========================================================================
        $productsTotal = $vendorTotal;

        // Get coupon data (but DON'T subtract from productsTotal!)
        $couponDiscount = 0;
        $couponCode = '';
        $couponPercentage = '';
        $couponId = null;

        if (Session::has('coupon_vendor_' . $vendorId)) {
            $couponDiscount = (float)Session::get('coupon_vendor_' . $vendorId, 0);
            $couponCode = Session::get('coupon_code_vendor_' . $vendorId, '');
            $couponPercentage = Session::get('coupon_percentage_vendor_' . $vendorId, '');
            $couponId = Session::get('coupon_id_vendor_' . $vendorId);
        } elseif (Session::has('coupon')) {
            $couponDiscount = (float)Session::get('coupon', 0);
            $couponCode = Session::get('coupon_code', '');
            $couponPercentage = Session::get('coupon_percentage', '');
            $couponId = Session::get('coupon_id');
        }

        // For free_above calculation, use products total (before coupon)
        $baseAmount = $productsTotal;

        $shipping_cost_total = 0.0;
        $shipping_names = [];

        // ✅ Track original shipping cost for free shipping display
        $original_shipping_cost = 0.0;
        $is_free_shipping = false;

        if (isset($step2['shipping']) && is_array($step2['shipping'])) {
            foreach ($step2['shipping'] as $vid => $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // تنسيق Tryoto
                    $parts = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price = (float)($parts[2] ?? 0);
                    $original_shipping_cost += $price;

                    // ✅ تطبيق free_above من إعدادات Tryoto للتاجر
                    $vendorTryotoShipping = \App\Models\Shipping::where('user_id', $vid)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $vendorTryotoShipping ? (float)$vendorTryotoShipping->free_above : 0;

                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        $shipping_names[] = $company . ' (Free Shipping)';
                        $is_free_shipping = true;
                        // Don't add to shipping_cost_total - it's free!
                    } else {
                        $shipping_cost_total += $price;
                        if ($company !== '') {
                            $shipping_names[] = $company;
                        }
                    }
                } else {
                    $id = (int)$val;
                    if ($id > 0) {
                        $ship = \App\Models\Shipping::find($id);
                        if ($ship) {
                            $original_shipping_cost += (float)$ship->price;
                            $freeAbove = (float)($ship->free_above ?? 0);
                            if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                                $shipping_names[] = $ship->title . ' (Free Shipping)';
                                $is_free_shipping = true;
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
                    $original_shipping_cost = (float)$ship->price;
                    $freeAbove = (float)($ship->free_above ?? 0);
                    if ($freeAbove > 0 && $baseAmount >= $freeAbove) {
                        $shipping_names[] = $ship->title . ' (Free Shipping)';
                        $is_free_shipping = true;
                    } else {
                        $shipping_cost_total += (float)$ship->price;
                        $shipping_names[] = $ship->title;
                    }
                }
            }
        }

        $shipping_name = count($shipping_names) ? implode(' + ', array_unique($shipping_names)) : null;
        $free_shipping_discount = $is_free_shipping ? $original_shipping_cost : 0;

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
        $step1Data = Session::get('vendor_step1_' . $vendorId);
        $taxAmount = $step1Data['tax_amount'] ?? 0;
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';

        // ========================================================================
        // ✅ UNIFIED PRICE CALCULATION
        // ========================================================================
        // products_total = RAW products (no coupon)
        // subtotal = products_total - coupon_discount
        // grand_total = subtotal + tax + shipping + packing
        // subtotal_before_coupon = products_total + tax + shipping + packing (for coupon recalculation)

        $subtotal = $productsTotal - $couponDiscount;
        $grandTotal = $subtotal + $taxAmount + $shipping_cost_total + $packing_cost_total;
        $subtotalBeforeCoupon = $productsTotal + $taxAmount + $shipping_cost_total + $packing_cost_total;

        // Save all data to step2
        $step2['products_total'] = $productsTotal;                   // ✅ RAW products total
        $step2['coupon_discount'] = $couponDiscount;                 // ✅ Coupon amount
        $step2['coupon_code'] = $couponCode;                         // ✅ Coupon code
        $step2['coupon_percentage'] = $couponPercentage;             // ✅ Coupon percentage
        $step2['coupon_id'] = $couponId;                             // ✅ Coupon ID
        $step2['shipping_company'] = $shipping_name;
        $step2['shipping_cost'] = $shipping_cost_total;
        $step2['original_shipping_cost'] = $original_shipping_cost;  // ✅ السعر قبل الخصم
        $step2['is_free_shipping'] = $is_free_shipping;              // ✅ هل الشحن مجاني
        $step2['free_shipping_discount'] = $free_shipping_discount;  // ✅ قيمة الخصم
        $step2['packing_company'] = $packing_name;
        $step2['packing_cost'] = $packing_cost_total;
        $step2['tax_rate'] = $taxRate;
        $step2['tax_amount'] = $taxAmount;
        $step2['tax_location'] = $taxLocation;
        $step2['subtotal_before_coupon'] = $subtotalBeforeCoupon;    // ✅ For coupon recalculation
        $step2['grand_total'] = $grandTotal;                         // ✅ Final amount
        $step2['total'] = $grandTotal;                               // Backward compatibility
        $step2['final_total'] = $grandTotal;                         // ✅ Alias

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
        // No fallback to user 0 - if vendor has no packages, collection will be empty

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        $paystack = PaymentGateway::whereKeyword('paystack')->first();
        $paystackData = $paystack ? $paystack->convertAutoData() : [];

        // CRITICAL: Use total from step2 (products + shipping + any adjustments)
        // step2 already calculated: vendorTotal + shipping_cost
        // This is the ONLY source of truth for final total
        $finalTotal = $step2->total ?? $productsTotal;

        // ✅ N+1 FIX: Pre-load country for step3
        $preloadedCountry = CheckoutDataService::loadCountry($step1);

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
            'vendor_id' => $vendorId,
            'preloadedCountry' => $preloadedCountry,
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
     * ========================================================================
     * N+1 OPTIMIZATION: Prepare vendor data for step2 view
     * ========================================================================
     * Pre-loads all vendor data (shipping, packaging, vendor info) in bulk
     * to avoid N+1 queries inside Blade template.
     *
     * @param array $products Cart products
     * @param object|null $step1 Step1 session data
     * @return array [vendorData, country]
     */
    protected function prepareStep2VendorData(array $products, $step1 = null): array
    {
        // Pre-load all vendor data using CheckoutDataService
        $vendorData = CheckoutDataService::loadVendorData($products);

        // Pre-load country data
        $country = CheckoutDataService::loadCountry($step1);

        return [
            'vendorData' => $vendorData,
            'country' => $country,
        ];
    }
}
