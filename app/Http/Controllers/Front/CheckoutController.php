<?php

/**
 * ====================================================================
 * MULTI-MERCHANT CHECKOUT CONTROLLER
 * ====================================================================
 *
 * STRICT POLICY (2025-12):
 * - ALL checkout operations MUST have merchant_id in ROUTE
 * - NO session-based merchant tracking (no checkout_merchant_id in session)
 * - NO POST/hidden inputs for merchant context
 * - Payment routes: /checkout/merchant/{merchantId}/payment/{gateway}
 *
 * Key Features:
 * 1. MERCHANT-SPECIFIC CHECKOUT ROUTES (all include merchantId):
 *    - checkoutMerchant($merchantId): Step 1 - Customer info
 *    - checkoutMerchantStep1($merchantId): Save customer data
 *    - checkoutMerchantStep2($merchantId): Step 2 - Shipping
 *    - checkoutMerchantStep2Submit($merchantId): Save shipping
 *    - checkoutMerchantStep3($merchantId): Step 3 - Payment
 *
 * 2. MERCHANT ISOLATION:
 *    - Filters cart items to show only items from specified merchant
 *    - Shows ONLY shipping companies where user_id = merchant_id
 *    - Shows ONLY payment gateways where user_id = merchant_id
 *    - NO FALLBACK to global/admin shipping or payment methods
 *
 * 3. SESSION MANAGEMENT (merchant_id NOT stored in session):
 *    - merchant_step1_{merchant_id}: Customer data per merchant
 *    - merchant_step2_{merchant_id}: Shipping data per merchant
 *
 * 4. ERROR HANDLING:
 *    - If merchant_id missing from route: Immediate fail (no fallback)
 *    - If no payment methods exist for merchant: Show error message
 *    - If no items for merchant: Redirect to cart
 *
 * Flow:
 * Cart → /checkout/merchant/{id} → Step1 → Step2 → Step3 → Payment → Purchase
 *
 * Terminology:
 * - "item" or "cartItem" refers to an item in the cart
 * - "catalogItem" refers to the CatalogItem model
 * - "merchantItem" refers to the MerchantItem model
 *
 * Modified: 2025-12-28 for Route-Based Merchant Context
 * ====================================================================
 */

namespace App\Http\Controllers\Front;

use App\Helpers\PriceHelper;
use App\Http\Controllers\MyFatoorahController;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\Purchase;
use App\Models\MerchantPayment;
use App\Models\PickupPoint;
use App\Models\CourierServiceArea;
use App\Services\MerchantCartService;
use App\Services\CheckoutDataService;
use App\Services\CheckoutPriceService;
use App\Services\ShippingCalculatorService;
use App\Services\GoogleMapsService;
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
            $gateway = MerchantPayment::findOrFail($pay_id);
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
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }
        $dp = 1;
        $merchant_shipping_id = 0;
        $merchant_packing_id = 0;
        $curr = $this->curr;
        $pickups = DB::table('pickups')->get();
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $cartItems = $cart->items;

        if (Auth::check()) {

            // Shipping Method

            if ($this->gs->multiple_shipping == 1) {
                $ship_data = Purchase::getShipData($cart);
                $shipping_data = $ship_data['shipping_data'];
                $merchant_shipping_id = $ship_data['merchant_shipping_id'];
            } else {
                $shipping_data = DB::table('shippings')->whereUserId(0)->get();
            }

            // Packaging

            if ($this->gs->multiple_shipping == 1) {
                $pack_data = Purchase::getPackingData($cart);
                $package_data = $pack_data['package_data'];
                $merchant_packing_id = $pack_data['merchant_packing_id'];
            } else {
                // No global packaging - empty collection
                $package_data = collect();
            }
            foreach ($cartItems as $cartItem) {
                if ($cartItem['item']['type'] == 'Physical') {
                    $dp = 0;
                    break;
                }
            }
            $total = $cart->totalPrice;
            $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

            if (!Session::has('discount_code_total')) {
                $total = $total - $discountAmount;
                $total = $total + 0;
            } else {
                $total = Session::get('discount_code_total');
                $total = str_replace(',', '', str_replace($curr->sign, '', $total));
            }

//            dd($total ,$cart->items);

            // Note: 'catalogItems' kept for backward compatibility in views
            return view('frontend.checkout.step1', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id]);
        } else {

            if ($this->gs->guest_checkout == 1) {
                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Purchase::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $merchant_shipping_id = $ship_data['merchant_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_shipping == 1) {
                    $pack_data = Purchase::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $merchant_packing_id = $pack_data['merchant_packing_id'];
                } else {
                    $package_data = DB::table('packages')->whereUserId('0')->get();

                }

                foreach ($cartItems as $cartItem) {
                    if ($cartItem['item']['type'] == 'Physical') {
                        $dp = 0;
                        break;
                    }
                }
                $total = $cart->totalPrice;
                $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

                if (!Session::has('discount_code_total')) {
                    $total = $total - $discountAmount;
                    $total = $total + 0;
                } else {
                    $total = Session::get('discount_code_total');
                    $total = str_replace($curr->sign, '', $total) + round(0 * $curr->value, 2);
                }
                foreach ($cartItems as $cartItem) {
                    if ($cartItem['item']['type'] != 'Physical') {
                        if (!Auth::check()) {
                            $ck = 1;
                            // Note: 'catalogItems' kept for backward compatibility in views
                            return view('frontend.checkout.step1', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id]);
                        }
                    }
                }
                // Note: 'catalogItems' kept for backward compatibility in views
                return view('frontend.checkout.step1', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id]);
            }

            // If guest checkout is Deactivated then display pop up form with proper error message

            else {

                // Shipping Method

                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Purchase::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $merchant_shipping_id = $ship_data['merchant_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_packaging == 1) {
                    $pack_data = Purchase::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $merchant_packing_id = $pack_data['merchant_packing_id'];
                } else {
                    $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
                }

                $total = $cart->totalPrice;
                $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

                if (!Session::has('discount_code_total')) {
                    $total = $total - $discountAmount;
                    $total = $total + 0;
                } else {
                    $total = Session::get('discount_code_total');
                    $total = $total;
                }
                $ck = 1;
                // Note: 'catalogItems' kept for backward compatibility in views
                return view('frontend.checkout.step1', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,  'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id]);
            }
        }
    }

    public function checkoutstep2()
    {

        if (!Session::has('step1')) {
            return redirect()->route('front.checkout')->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

//        dd(Session::get('cart'));

        $step1 = (object) Session::get('step1');
//        dd($step1);
        $dp = 1;
        $merchant_shipping_id = 0;
        $merchant_packing_id = 0;
        $curr = $this->curr;
        $pickups = DB::table('pickups')->get();
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $cartItems = $cart->items;

//        dd($cartItems);
        if (Auth::check()) {

            // Shipping Method

            if ($this->gs->multiple_shipping == 1) {
                $ship_data = Purchase::getShipData($cart);
                $shipping_data = $ship_data['shipping_data'];
                $merchant_shipping_id = $ship_data['merchant_shipping_id'];
            } else {
                $shipping_data = DB::table('shippings')->whereUserId(0)->get();
            }

            // Packaging

            if ($this->gs->multiple_shipping == 1) {
                $pack_data = Purchase::getPackingData($cart);
                $package_data = $pack_data['package_data'];
                $merchant_packing_id = $pack_data['merchant_packing_id'];
            } else {
                // No global packaging - empty collection
                $package_data = collect();
            }
            foreach ($cartItems as $cartItem) {
                if ($cartItem['item']['type'] == 'Physical') {
                    $dp = 0;
                    break;
                }
            }
            $total = $cart->totalPrice;
            $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

            if (!Session::has('discount_code_total')) {
                $total = $total - $discountAmount;
                $total = $total + 0;
            } else {
                $total = Session::get('discount_code_total');
                $total = str_replace(',', '', str_replace($curr->sign, '', $total));
            }
//            dd($cartItems);

            // N+1 FIX: Pre-load all merchant data
            $step2Data = $this->prepareStep2MerchantData($cartItems, $step1);

            // Note: 'catalogItems' kept for backward compatibility in views
            return view('frontend.checkout.step2', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id, 'step1' => $step1, 'merchantData' => $step2Data['merchantData'], 'preloadedCountry' => $step2Data['country']]);
        } else {

            if ($this->gs->guest_checkout == 1) {
                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Purchase::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $merchant_shipping_id = $ship_data['merchant_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_shipping == 1) {
                    $pack_data = Purchase::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $merchant_packing_id = $pack_data['merchant_packing_id'];
                } else {
                    $package_data = DB::table('packages')->whereUserId('0')->get();

                }

                foreach ($cartItems as $cartItem) {
                    if ($cartItem['item']['type'] == 'Physical') {
                        $dp = 0;
                        break;
                    }
                }
                $total = $cart->totalPrice;
                $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

                if (!Session::has('discount_code_total')) {
                    $total = $total - $discountAmount;
                    $total = $total + 0;
                } else {
                    $total = Session::get('discount_code_total');
                    $total = str_replace($curr->sign, '', $total) + round(0 * $curr->value, 2);
                }
                // N+1 FIX: Pre-load all merchant data
                $step2Data = $this->prepareStep2MerchantData($cartItems, $step1 ?? null);

                foreach ($cartItems as $cartItem) {
                    if ($cartItem['item']['type'] != 'Physical') {
                        if (!Auth::check()) {
                            $ck = 1;
                            // Note: 'catalogItems' kept for backward compatibility in views
                            return view('frontend.checkout.step2', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id, 'merchantData' => $step2Data['merchantData'], 'preloadedCountry' => $step2Data['country']]);
                        }
                    }
                }
//                dd($cartItems);
                // Note: 'catalogItems' kept for backward compatibility in views
                return view('frontend.checkout.step2', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty, 'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id, 'step1' => $step1, 'merchantData' => $step2Data['merchantData'], 'preloadedCountry' => $step2Data['country']]);
            }

            // If guest checkout is Deactivated then display pop up form with proper error message

            else {

                // Shipping Method

                if ($this->gs->multiple_shipping == 1) {
                    $ship_data = Purchase::getShipData($cart);
                    $shipping_data = $ship_data['shipping_data'];
                    $merchant_shipping_id = $ship_data['merchant_shipping_id'];
                } else {
                    $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();
                }

                // Packaging

                if ($this->gs->multiple_packaging == 1) {
                    $pack_data = Purchase::getPackingData($cart);
                    $package_data = $pack_data['package_data'];
                    $merchant_packing_id = $pack_data['merchant_packing_id'];
                } else {
                    $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
                }

                $total = $cart->totalPrice;
                $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;

                if (!Session::has('discount_code_total')) {
                    $total = $total - $discountAmount;
                    $total = $total + 0;
                } else {
                    $total = Session::get('discount_code_total');
                    $total = $total;
                }
                $ck = 1;
//                dd($cartItems);
                // N+1 FIX: Pre-load all merchant data
                $step2Data = $this->prepareStep2MerchantData($cartItems, $step1);

                // Note: 'catalogItems' kept for backward compatibility in views
                return view('frontend.checkout.step2', ['catalogItems' => $cartItems, 'totalPrice' => $total, 'pickups' => $pickups, 'totalQty' => $cart->totalQty,'shipping_cost' => 0, 'digital' => $dp, 'curr' => $curr, 'shipping_data' => $shipping_data, 'package_data' => $package_data, 'merchant_shipping_id' => $merchant_shipping_id, 'merchant_packing_id' => $merchant_packing_id, 'step1' => $step1, 'merchantData' => $step2Data['merchantData'], 'preloadedCountry' => $step2Data['country']]);
            }
        }
    }

    /**
     * Reset location draft session (called when map modal opens)
     * Only clears location_draft, preserves step1/step2/step3
     */
    public function resetLocation(Request $request)
    {
        $merchantId = $request->input('merchant_id');

        // Clear location draft only (not step1/step2/step3)
        if ($merchantId) {
            Session::forget('location_draft_merchant_' . $merchantId);
        } else {
            Session::forget('location_draft');
        }

        return response()->json([
            'success' => true,
            'message' => 'Location draft cleared'
        ]);
    }

    /**
     * GENERAL CHECKOUT STEP 1 (Legacy - Merchant checkout preferred)
     * Collects customer data, coordinates from map, calculates tax.
     * Step 2 handles city resolution and shipping.
     */
    public function checkoutStep1(Request $request)
    {
        $step1 = $request->all();

        $validator = Validator::make($step1, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'country_id' => 'nullable|numeric|exists:countries,id',
        ], [
            'customer_name.required' => __('Name is required'),
            'customer_email.required' => __('Email is required'),
            'customer_email.email' => __('Invalid email format'),
            'customer_phone.required' => __('Phone is required'),
            'customer_address.required' => __('Address is required'),
            'latitude.required' => __('Please select your location from the map'),
            'longitude.required' => __('Please select your location from the map'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        // Build step1 data (raw data only - geocoding happens in Step 2)
        $step1Data = [
            'customer_name' => $step1['customer_name'],
            'customer_email' => $step1['customer_email'],
            'customer_phone' => $step1['customer_phone'],
            'customer_address' => $step1['customer_address'],
            'customer_zip' => $step1['customer_zip'] ?? null,
            'latitude' => (float) $step1['latitude'],
            'longitude' => (float) $step1['longitude'],
            'country_id' => $step1['country_id'] ?? null,
            'shipping' => $step1['shipping'] ?? 'shipto',
            'pickup_location' => $step1['pickup_location'] ?? null,
            'create_account' => $step1['create_account'] ?? null,
            'password' => $step1['password'] ?? null,
            'password_confirmation' => $step1['password_confirmation'] ?? null,
            'dp' => $step1['dp'] ?? 0,
            'totalQty' => $step1['totalQty'] ?? 0,
            'merchant_shipping_id' => $step1['merchant_shipping_id'] ?? 0,
            'merchant_packing_id' => $step1['merchant_packing_id'] ?? 0,
            'currency_sign' => $step1['currency_sign'] ?? null,
            'currency_name' => $step1['currency_name'] ?? null,
            'currency_value' => $step1['currency_value'] ?? 1,
            'discount_code' => $step1['discount_code'] ?? null,
            'discount_amount' => $step1['discount_amount'] ?? null,
            'discount_code_id' => $step1['discount_code_id'] ?? null,
            'user_id' => $step1['user_id'] ?? null,
        ];

        // Merge location_draft into step1Data (if available)
        $locationDraft = Session::get('location_draft');
        if ($locationDraft) {
            $step1Data = array_merge($step1Data, [
                'country_id' => $locationDraft['country_id'] ?? $step1Data['country_id'],
                'country_name' => $locationDraft['country_name'] ?? null,
                'city_name' => $locationDraft['city_name'] ?? null,
                'state_name' => $locationDraft['state_name'] ?? null,
                // Purchase table fields
                'customer_country' => $locationDraft['country_name'] ?? null,
                'customer_city' => $locationDraft['city_name'] ?? null,
                'customer_state' => $locationDraft['state_name'] ?? null,
                'tax_rate' => $locationDraft['tax_rate'] ?? 0,
                'tax_amount' => $locationDraft['tax_amount'] ?? 0,
                'tax_location' => $locationDraft['tax_location'] ?? '',
            ]);
            // Clear location_draft after merging
            Session::forget('location_draft');
        }

        // Calculate tax from country_id (fallback if no location_draft)
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';
        $country = null;

        if (!empty($step1Data['country_id'])) {
            $country = \App\Models\Country::find($step1Data['country_id']);
            if ($country) {
                // Set customer_country fallback if not set from location_draft
                if (empty($step1Data['customer_country'])) {
                    $step1Data['customer_country'] = $country->country_name;
                }
                // Set tax if not already set from location_draft
                if ($taxRate == 0 && $country->tax > 0) {
                    $taxRate = $country->tax;
                    $taxLocation = $country->country_name;
                }
            }
        }

        // Calculate tax amount per merchant
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $merchantTaxData = [];
        foreach ($cart->items as $cartItem) {
            $merchantId = $cartItem['item']['user_id'] ?? 0;

            if (!isset($merchantTaxData[$merchantId])) {
                $merchantTaxData[$merchantId] = [
                    'subtotal' => 0,
                    'tax_rate' => $taxRate,
                    'tax_amount' => 0,
                ];
            }

            $merchantTaxData[$merchantId]['subtotal'] += (float)($cartItem['price'] ?? 0);
        }

        // Calculate tax amount for each merchant
        foreach ($merchantTaxData as $merchantId => &$taxData) {
            $taxData['tax_amount'] = ($taxData['subtotal'] * $taxData['tax_rate']) / 100;
        }

        // Save tax data
        $step1Data['tax_rate'] = $taxRate;
        $step1Data['tax_location'] = $taxLocation;
        $step1Data['merchant_tax_data'] = $merchantTaxData;

        // Calculate total tax amount
        $totalTaxAmount = array_sum(array_column($merchantTaxData, 'tax_amount'));
        $step1Data['total_tax_amount'] = $totalTaxAmount;

        // Items total for price summary
        $itemsTotal = $cart->totalPrice;
        $step1Data['catalog_items_total'] = $itemsTotal;
        $step1Data['tax_amount'] = $totalTaxAmount;
        $step1Data['total_with_tax'] = $itemsTotal + $totalTaxAmount;

        Session::forget(['step2', 'step3']);
        Session::put('step1', $step1Data);

        return redirect()->route('front.checkout.step2');
    }

    /**
     * GENERAL CHECKOUT STEP 2 SUBMIT (Legacy)
     */
    public function checkoutStep2Submit(Request $request)
    {
        $step2  = $request->all();
        $oldCart = Session::get('cart');
        $input  = Session::get('step1') + $step2;

        // حساب المبلغ الأساسي (قبل الضريبة والشحن) لتطبيق free_above
        $cart = new Cart($oldCart);
        $baseAmount = $cart->totalPrice;
        $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;
        $baseAmount = $baseAmount - $discountAmount;

        // إجمالي تكلفة الشحن + أسماء الشركات (قد يكون متعدد البائعين)
        $shipping_cost_total = 0.0;
        $shipping_names = [];

        // ✅ Track original shipping cost for free shipping display
        $original_shipping_cost = 0.0;
        $is_free_shipping = false;

        // حالة الشحن المتعدد: shipping[merchant_id] = {id أو deliveryOptionId#Company#price}
        if (isset($step2['shipping']) && is_array($step2['shipping'])) {

            foreach ($step2['shipping'] as $merchantId => $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // تنسيق Tryoto: deliveryOptionId#CompanyName#price
                    $parts   = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price   = (float)($parts[2] ?? 0);
                    $original_shipping_cost += $price;

                    // ✅ تطبيق free_above من إعدادات Tryoto للتاجر
                    $merchantTryotoShipping = \App\Models\Shipping::where('user_id', $merchantId)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $merchantTryotoShipping ? (float)$merchantTryotoShipping->free_above : 0;

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

        // ✅ Calculate packing cost (same logic as merchant checkout)
        $packing_cost_total = 0.0;
        $packing_names = [];

        // Check for array format (multi-merchant) or single value
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            // Multi-merchant format: packeging[merchant_id] = package_id
            foreach ($step2['packeging'] as $merchantId => $packageId) {
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
            // Single merchant format
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

        // ✅ Get discount code data (supports both regular and merchant checkout)
        $checkoutMerchantId = Session::get('checkout_merchant_id');
        if ($checkoutMerchantId) {
            $discountAmount = Session::get('discount_code_merchant_' . $checkoutMerchantId, 0);
            $discountCode = Session::get('discount_code_value_merchant_' . $checkoutMerchantId, '');
            $discountCodeId = Session::get('discount_code_id_merchant_' . $checkoutMerchantId, null);
            $discountPercentage = Session::get('discount_percentage_merchant_' . $checkoutMerchantId, '');
        } else {
            $discountAmount = Session::get('discount_code', 0);
            $discountCode = Session::get('discount_code_value', '');
            $discountCodeId = Session::get('discount_code_id', null);
            $discountPercentage = Session::get('discount_percentage', '');
        }

        // ✅ Calculate totals
        // subtotal_before_discount = catalogItems + tax + shipping + packing
        $subtotalBeforeDiscount = $baseAmount + $taxAmount + $shipping_cost_total + $packing_cost_total;

        // final_total = subtotal - discount
        $finalTotal = $subtotalBeforeDiscount - $discountAmount;

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

        // ✅ Discount code data saved in step2 for step3 display
        $step2['discount_amount']    = $discountAmount;
        $step2['discount_code']      = $discountCode;
        $step2['discount_code_id']   = $discountCodeId;
        $step2['discount_percentage'] = $discountPercentage;
        $step2['discount_applied']   = $discountAmount > 0;  // Flag to prevent double subtraction

        // ✅ Totals
        $step2['subtotal_before_discount'] = $subtotalBeforeDiscount;  // قبل طرح الخصم
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
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $step1 = (object) Session::get('step1');
        $step2 = (object) Session::get('step2');
        $dp = 1;
        $merchant_shipping_id = 0;
        $merchant_packing_id = 0;
        $curr = $this->curr;
        $gateways = MerchantPayment::scopeHasGateway($this->curr->id);
        $pickups = DB::table('pickups')->get();

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        $cartItems = $cart->items;

        $paystack = MerchantPayment::whereKeyword('paystack')->first();
        $paystackData = $paystack ? $paystack->convertAutoData() : [];

        // Check if cart has physical items
        foreach ($cartItems as $cartItem) {
            if ($cartItem['item']['type'] == 'Physical') { $dp = 0; break; }
        }

        // شحن وتغليف
        if ($this->gs->multiple_shipping == 1) {
            $ship_data = Purchase::getShipData($cart);
            $shipping_data = $ship_data['shipping_data'];
            $merchant_shipping_id = $ship_data['merchant_shipping_id'];
        } else {
            $shipping_data = collect(); // No global shipping
        }

        if ($this->gs->multiple_shipping == 1) {
            $pack_data = Purchase::getPackingData($cart);
            $package_data = $pack_data['package_data'];
            $merchant_packing_id = $pack_data['merchant_packing_id'];
        } else {
            $package_data = collect(); // No global packaging
        }

        // الإجمالي مع الكوبون
        $total = $cart->totalPrice;
        $discountAmount = Session::has('discount_code') ? Session::get('discount_code') : 0;
        if (!Session::has('discount_code_total')) {
            $total = $total - $discountAmount;
        } else {
            $total = Session::get('discount_code_total');
        }

        // أعرض صفحة وسائل الدفع (Step 3)
        // ✅ N+1 FIX: Pre-load country for step3
        $preloadedCountry = CheckoutDataService::loadCountry($step1);

        return view('frontend.checkout.step3', [
            'catalogItems'            => $cart->items,
            'totalPrice'          => $total,
            'pickups'             => $pickups,
            'totalQty'            => $cart->totalQty,
            'gateways'            => $gateways,
            'shipping_cost'       => $step2->shipping_cost ?? 0,
            'digital'             => $dp,
            'curr'                => $curr,
            'shipping_data'       => $shipping_data,
            'package_data'        => $package_data,
            'merchant_shipping_id'  => $merchant_shipping_id,
            'merchant_packing_id'   => $merchant_packing_id,
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

            // اسم المدينة (إنجليزي فقط - لا يوجد city_name_ar)
            $cityDisplayName = $city->city_name;

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

            // اسم المدينة (إنجليزي فقط - لا يوجد city_name_ar)
            $cityDisplayName = $city->city_name;

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
            $purchase = Session::get('temporder');
        } else {
            $tempcart = '';
            return redirect()->back();
        }

        return view('frontend.success', compact('tempcart', 'purchase'));
    }

    /* ===================== Merchant-Specific Checkout ===================== */

    /**
     * Helper: Get merchant catalogItems and calculate totals
     *
     * Filters cart to show ONLY merchant's catalogItems and calculates:
     * - Total price
     * - Total quantity
     * - Digital/Physical flag
     *
     * @param int $merchantId
     * @return array [merchantItems, totalPrice, totalQty, digital]
     */
    /**
     * Get merchant cart data - READ-ONLY helper method
     *
     * CRITICAL SAFETY:
     * 1. Creates Cart instance for READ-ONLY access
     * 2. Filters catalogItems by merchant_id
     * 3. Calculates merchant-specific totals
     * 4. Does NOT modify session
     * 5. Cart instance is discarded after use
     * 6. Returns filtered data as array
     *
     * @param int $merchantId
     * @return array ['merchantItems', 'totalPrice', 'totalQty', 'digital']
     */
    private function getMerchantCartData($merchantId): array
    {
        // READ-ONLY: Get cart from session without modification
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        // Ensure merchantId is integer for comparison
        $merchantId = (int)$merchantId;

        // Filter catalogItems for this merchant only
        $merchantItems = [];
        foreach ($cart->items as $rowKey => $catalogItem) {
            // ✅ Get merchant ID - Cart stores 'user_id' at root level (see Cart::add/addnum)
            // Priority: direct user_id > item.user_id > item.merchant_user_id
            $itemMerchantId = 0;

            // Path 1: direct user_id (Cart model stores this at root level)
            if (isset($catalogItem['user_id'])) {
                $itemMerchantId = (int)$catalogItem['user_id'];
            }
            // Path 2: item.user_id (CatalogItem object property)
            elseif (isset($catalogItem['item']) && is_object($catalogItem['item']) && isset($catalogItem['item']->user_id)) {
                $itemMerchantId = (int)$catalogItem['item']->user_id;
            }
            // Path 3: item as array
            elseif (isset($catalogItem['item']) && is_array($catalogItem['item']) && isset($catalogItem['item']['user_id'])) {
                $itemMerchantId = (int)$catalogItem['item']['user_id'];
            }
            // Path 4: merchant_user_id (fallback)
            elseif (isset($catalogItem['item']) && is_object($catalogItem['item']) && isset($catalogItem['item']->merchant_user_id)) {
                $itemMerchantId = (int)$catalogItem['item']->merchant_user_id;
            }

            if ($itemMerchantId === $merchantId) {
                // إضافة بيانات الخصم والأبعاد باستخدام MerchantCartService
                $mpId = data_get($catalogItem, 'item.merchant_item_id')
                    ?? data_get($catalogItem, 'merchant_item_id')
                    ?? 0;
                $qty = (int)($catalogItem['qty'] ?? 1);

                if ($mpId) {
                    // حساب خصم الجملة
                    $bulkDiscount = MerchantCartService::calculateBulkDiscount($mpId, $qty);
                    $catalogItem['bulk_discount'] = $bulkDiscount;

                    // جلب الأبعاد (بدون fallback)
                    $dimensions = MerchantCartService::getCatalogItemDimensions($mpId);
                    $catalogItem['dimensions'] = $dimensions;
                    $catalogItem['row_weight'] = $dimensions['weight'] ? $dimensions['weight'] * $qty : null;
                }

                $merchantItems[$rowKey] = $catalogItem;
            }
        }

        // Calculate totals for merchant catalogItems only
        $totalPrice = 0;
        $totalQty = 0;
        foreach ($merchantItems as $cartItem) {
            $totalPrice += (float)($cartItem['price'] ?? 0);
            $totalQty += (int)($cartItem['qty'] ?? 1);
        }

        // Check if all merchant items are digital
        $dp = 1;
        foreach ($merchantItems as $cartItem) {
            if (data_get($cartItem, 'item.type') == 'Physical') {
                $dp = 0;
                break;
            }
        }

        // حساب بيانات الشحن للتاجر باستخدام MerchantCartService
        $shippingData = MerchantCartService::calculateMerchantShipping($merchantId, $cart->items);

        // Return filtered data (Cart instance is discarded - no session modification)
        return [
            'merchantItems' => $merchantItems,
            'totalPrice' => $totalPrice,
            'totalQty' => $totalQty,
            'digital' => $dp,
            'shipping_data' => $shippingData,
            'has_complete_shipping_data' => $shippingData['has_complete_data'],
            'missing_shipping_data' => $shippingData['missing_data'],
        ];
    }

    /**
     * Step 1 - Display checkout page for a SPECIFIC merchant only
     *
     * MULTI-MERCHANT SIMPLIFIED LOGIC:
     * 1. Checks authentication (logged-in or guest checkout enabled)
     * 2. Saves merchant_id in session for tracking (checkout_merchant_id)
     * 3. Filters cart to show ONLY this merchant's catalogItems (getMerchantCartData)
     * 4. Gets ONLY merchant-specific shipping methods (no general cart shipping)
     * 5. Gets ONLY merchant-specific packaging methods (no general cart packaging)
     * 6. Calculates total for THIS merchant only (with merchant-specific discount)
     * 7. Does NOT call Purchase::getShipData or Purchase::getPackingData (avoids cart-wide logic)
     * 8. Does NOT modify auth state - only reads Auth::check()
     *
     * @param int $merchantId The merchant's user_id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    /**
     * POLICY: merchant_id comes from ROUTE only - NOT from session
     */
    public function checkoutMerchant($merchantId)
    {
        // ====================================================================
        // STRICT POLICY: merchant_id FROM ROUTE ONLY
        // No session storage for merchant context
        // ====================================================================
        $merchantId = (int)$merchantId;

        if (!$merchantId) {
            return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        // ====================================================================
        // مسح بيانات temp عند بدء checkout جديد
        // ====================================================================
        Session::forget(['tempcart', 'temporder']);

        // Check if user is authenticated OR guest checkout is enabled
        if (!Auth::check() && $this->gs->guest_checkout != 1) {
            // Redirect to login with return URL that includes merchant_id
            return redirect()->route('user.login')
                ->with('unsuccess', __('Please login to continue.'))
                ->with('return_to', route('front.checkout.merchant', $merchantId));
        }

        // Clean old step data for THIS merchant to allow form refresh
        Session::forget(['merchant_step1_' . $merchantId, 'merchant_step2_' . $merchantId]);

        // Get merchant cart data using helper method (avoids code duplication)
        $cartData = $this->getMerchantCartData($merchantId);
        $merchantItems = $cartData['merchantItems'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];
        $merchantShippingData = $cartData['shipping_data'];

        if (empty($merchantItems)) {
            return redirect()->route('front.cart')->with('unsuccess', __("No catalogItems found for this merchant."));
        }

        // جلب طرق الشحن الخاصة بهذا التاجر فقط (merchant-specific only)
        $shipping_data = \App\Models\Shipping::forMerchant($merchantId)->get();

        // جلب طرق التغليف الخاصة بهذا التاجر فقط (merchant-specific only)
        $package_data = DB::table('packages')->where('user_id', $merchantId)->get();
        // No fallback to user 0 - if merchant has no packages, collection will be empty

        // catalogItemsTotal = RAW price (no discount deduction)
        $catalogItemsTotal = $totalPrice;

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        return view('frontend.checkout.step1', [
            'catalogItems' => $merchantItems,
            'catalogItemsTotal' => $catalogItemsTotal, // ✅ RAW catalogItems total (no discount)
            'totalPrice' => $catalogItemsTotal, // Backward compatibility
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'shipping_cost' => 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'merchant_shipping_id' => $merchantId,
            'merchant_packing_id' => $merchantId,
            'is_merchant_checkout' => true,
            'merchant_id' => $merchantId,
            // بيانات الشحن الموحدة من MerchantCartService
            'merchant_shipping_data' => $merchantShippingData,
            'has_complete_shipping_data' => $cartData['has_complete_shipping_data'],
            'missing_shipping_data' => $cartData['missing_shipping_data'],
        ]);
    }

    /**
     * MERCHANT CHECKOUT STEP 1 SUBMIT
     * Collects customer data, coordinates from map, calculates tax.
     * Data saved to merchant_step1_{merchant_id} session.
     */
    public function checkoutMerchantStep1(Request $request, $merchantId)
    {
        $step1 = $request->all();

        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|numeric',
            'customer_address' => 'required|string|max:255',
            'customer_zip' => 'nullable|string|max:20',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'country_id' => 'nullable|numeric|exists:countries,id',
        ];

        // Add password validation if creating account
        if (isset($step1['create_account']) && $step1['create_account'] == 1) {
            $rules['password'] = 'required|string|min:6|confirmed';
            $rules['password_confirmation'] = 'required|string|min:6';
        }

        $validator = Validator::make($step1, $rules, [
            'customer_name.required' => __('Name is required'),
            'customer_email.required' => __('Email is required'),
            'customer_phone.required' => __('Phone is required'),
            'customer_address.required' => __('Address is required'),
            'latitude.required' => __('Please select your location from the map'),
            'longitude.required' => __('Please select your location from the map'),
            'password.required' => __('Password is required when creating an account'),
            'password.min' => __('Password must be at least 6 characters'),
            'password.confirmed' => __('Password confirmation does not match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        // Create account if requested
        if (isset($step1['create_account']) && $step1['create_account'] == 1 && !Auth::check()) {
            $existingUser = \App\Models\User::where('email', $step1['customer_email'])->first();

            if ($existingUser) {
                return back()->with('unsuccess', __('An account with this email already exists. Please login.'))->withInput();
            }

            $user = new \App\Models\User();
            $user->name = $step1['customer_name'];
            $user->email = $step1['customer_email'];
            $user->phone = $step1['customer_phone'];
            $user->address = $step1['customer_address'];
            $user->zip = $step1['customer_zip'] ?? null;
            $user->password = bcrypt($step1['password']);
            $user->email_verified = 'Yes';
            $user->affilate_code = null;
            $user->is_provider = 0;
            $user->save();

            Auth::login($user, true);
        }

        // Build step1 data (raw data only - geocoding happens in Step 2)
        $step1Data = [
            'customer_name' => $step1['customer_name'],
            'customer_email' => $step1['customer_email'],
            'customer_phone' => $step1['customer_phone'],
            'customer_address' => $step1['customer_address'],
            'customer_zip' => $step1['customer_zip'] ?? null,
            'latitude' => (float) $step1['latitude'],
            'longitude' => (float) $step1['longitude'],
            'country_id' => $step1['country_id'] ?? null,
            'shipping' => $step1['shipping'] ?? 'shipto',
            'pickup_location' => $step1['pickup_location'] ?? null,
            'create_account' => $step1['create_account'] ?? null,
            'dp' => $step1['dp'] ?? 0,
            'totalQty' => $step1['totalQty'] ?? 0,
            'merchant_shipping_id' => $step1['merchant_shipping_id'] ?? 0,
            'merchant_packing_id' => $step1['merchant_packing_id'] ?? 0,
            'currency_sign' => $step1['currency_sign'] ?? null,
            'currency_name' => $step1['currency_name'] ?? null,
            'currency_value' => $step1['currency_value'] ?? 1,
            'discount_code' => $step1['discount_code'] ?? null,
            'discount_amount' => $step1['discount_amount'] ?? null,
            'discount_code_id' => $step1['discount_code_id'] ?? null,
            'user_id' => $step1['user_id'] ?? null,
            // ========================================================================
            // DELIVERY OPTIONS (from Step 1 location selection)
            // ========================================================================
            'delivery_type' => $step1['delivery_type'] ?? null, // 'local_courier', 'pickup', 'shipping_company', or null
            'selected_courier_id' => !empty($step1['selected_courier_id']) ? (int)$step1['selected_courier_id'] : null,
            'selected_courier_fee' => !empty($step1['selected_courier_fee']) ? (float)$step1['selected_courier_fee'] : 0,
            'selected_service_area_id' => !empty($step1['selected_service_area_id']) ? (int)$step1['selected_service_area_id'] : null,
            'selected_pickup_point_id' => !empty($step1['selected_pickup_point_id']) ? (int)$step1['selected_pickup_point_id'] : null,
            'customer_city_id' => !empty($step1['customer_city_id']) ? (int)$step1['customer_city_id'] : null,
        ];

        // Merge location_draft_merchant_{id} into step1Data (if available)
        $locationDraft = Session::get('location_draft_merchant_' . $merchantId);
        if ($locationDraft) {
            $step1Data = array_merge($step1Data, [
                'country_id' => $locationDraft['country_id'] ?? $step1Data['country_id'],
                'country_name' => $locationDraft['country_name'] ?? null,
                'city_name' => $locationDraft['city_name'] ?? null,
                'state_name' => $locationDraft['state_name'] ?? null,
                // Purchase table fields
                'customer_country' => $locationDraft['country_name'] ?? null,
                'customer_city' => $locationDraft['city_name'] ?? null,
                'customer_state' => $locationDraft['state_name'] ?? null,
                'tax_rate' => $locationDraft['tax_rate'] ?? 0,
                'tax_amount' => $locationDraft['tax_amount'] ?? 0,
                'tax_location' => $locationDraft['tax_location'] ?? '',
            ]);
            // Clear location_draft after merging
            Session::forget('location_draft_merchant_' . $merchantId);
        }

        // Calculate tax from country_id (fallback if no location_draft)
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';
        $country = null;

        if (!empty($step1Data['country_id'])) {
            $country = \App\Models\Country::find($step1Data['country_id']);
            if ($country) {
                // Set customer_country fallback if not set from location_draft
                if (empty($step1Data['customer_country'])) {
                    $step1Data['customer_country'] = $country->country_name;
                }
                // Set tax if not already set from location_draft
                if ($taxRate == 0 && $country->tax > 0) {
                    $taxRate = $country->tax;
                    $taxLocation = $country->country_name;
                }
            }
        }

        // Calculate tax amount for this merchant only
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        $merchantSubtotal = 0;

        // Calculate subtotal for this merchant's catalogItems only
        foreach ($cart->items as $catalogItem) {
            $itemMerchantId = 0;
            if (isset($catalogItem['user_id'])) {
                $itemMerchantId = (int)$catalogItem['user_id'];
            } elseif (isset($catalogItem['item']) && is_object($catalogItem['item']) && isset($catalogItem['item']->user_id)) {
                $itemMerchantId = (int)$catalogItem['item']->user_id;
            } elseif (isset($catalogItem['item']) && is_array($catalogItem['item']) && isset($catalogItem['item']['user_id'])) {
                $itemMerchantId = (int)$catalogItem['item']['user_id'];
            }

            if ($itemMerchantId == (int)$merchantId) {
                $merchantSubtotal += (float)($catalogItem['price'] ?? 0);
            }
        }

        $taxAmount = ($merchantSubtotal * $taxRate) / 100;

        // Save tax data
        $step1Data['tax_rate'] = $taxRate;
        $step1Data['tax_location'] = $taxLocation;
        $step1Data['tax_amount'] = $taxAmount;
        $step1Data['merchant_subtotal'] = $merchantSubtotal;
        $step1Data['catalog_items_total'] = $merchantSubtotal;
        $step1Data['total_with_tax'] = $merchantSubtotal + $taxAmount;

        Session::put('merchant_step1_' . $merchantId, $step1Data);

        // ========================================================================
        // DELIVERY TYPE ROUTING
        // If delivery_type is 'local_courier' or 'pickup', auto-create step2 data
        // and skip to step3 (no shipping selection needed)
        // ========================================================================
        $deliveryType = $step1Data['delivery_type'] ?? null;

        if ($deliveryType === 'local_courier' || $deliveryType === 'pickup') {
            // Auto-populate step2 data based on delivery selection
            $courierFee = 0;
            $courierName = null;

            if ($deliveryType === 'local_courier' && !empty($step1Data['selected_courier_id'])) {
                // Get courier details
                $courierFee = (float)($step1Data['selected_courier_fee'] ?? 0);
                $serviceAreaId = $step1Data['selected_service_area_id'] ?? null;
                if ($serviceAreaId) {
                    $serviceArea = CourierServiceArea::with('courier')->find($serviceAreaId);
                    if ($serviceArea) {
                        $courierName = $serviceArea->courier->name ?? 'Courier';
                    }
                }
            }

            // Calculate totals
            $grandTotal = $merchantSubtotal + $taxAmount + $courierFee;

            // Build step2 data
            $step2Data = [
                'catalog_items_total' => $merchantSubtotal,
                'discount_amount' => 0,
                'discount_code' => '',
                'discount_percentage' => '',
                'discount_code_id' => null,
                'shipping_company' => null,
                'shipping_cost' => 0,
                'original_shipping_cost' => 0,
                'is_free_shipping' => false,
                'free_shipping_discount' => 0,
                'packing_company' => null,
                'packing_cost' => 0,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'tax_location' => $taxLocation,
                'subtotal_before_discount' => $merchantSubtotal + $taxAmount + $courierFee,
                'grand_total' => $grandTotal,
                'total' => $grandTotal,
                'final_total' => $grandTotal,
                // Delivery data
                'delivery_type' => $deliveryType,
                'courier_id' => $step1Data['selected_courier_id'] ?? null,
                'courier_name' => $courierName,
                'courier_fee' => $courierFee,
                'pickup_point_id' => $step1Data['selected_pickup_point_id'] ?? null,
                'customer_city_id' => $step1Data['customer_city_id'] ?? null,
            ];

            Session::put('merchant_step2_' . $merchantId, $step2Data);
            Session::save();

            // Skip step2, go directly to step3 (payment)
            return redirect()->route('front.checkout.merchant.step3', $merchantId);
        }

        // Normal flow: Go to step2 (shipping selection)
        Session::save();
        return redirect()->route('front.checkout.merchant.step2', $merchantId);
    }

    /**
     * MERCHANT CHECKOUT STEP 2 - Shipping selection page.
     */
    public function checkoutMerchantStep2($merchantId)
    {
        if (!Session::has('merchant_step1_' . $merchantId)) {
            return redirect()->route('front.checkout.merchant', $merchantId)->with('success', __("Please fill up step 1."));
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $step1 = (object) Session::get('merchant_step1_' . $merchantId);

        $step2 = Session::has('merchant_step2_' . $merchantId)
            ? (object) Session::get('merchant_step2_' . $merchantId)
            : null;

        $cartData = $this->getMerchantCartData($merchantId);
        $merchantItems = $cartData['merchantItems'];
        $totalPrice = $cartData['totalPrice'];
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        $shipping_data = \App\Models\Shipping::forMerchant($merchantId)->get();
        $package_data = DB::table('packages')->where('user_id', $merchantId)->get();
        // No fallback to user 0 - if merchant has no packages, collection will be empty

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        // N+1 FIX: Pre-load all merchant data
        $step2MerchantData = $this->prepareStep2MerchantData($merchantItems, $step1);
        $country = $step2MerchantData['country'];
        $isState = isset($step1->customer_state) ? 1 : 0;

        // Group items by merchant (will contain single merchant only)
        // This avoids code duplication in view
        $itemsByMerchant = $this->groupItemsByMerchant($merchantItems);

        // ========================================================================
        // COURIER DELIVERY SUPPORT
        // Check if courier delivery is available for customer's city
        // ========================================================================
        $courierData = $this->getCourierDeliveryData($merchantId, $step1);

        return view('frontend.checkout.step2', [
            'catalogItemsByMerchant' => $itemsByMerchant, // Grouped items (single merchant)
            'catalogItems' => $merchantItems, // Keep for backward compatibility
            'catalogItemsTotal' => $totalPrice, // Items total only - shipping/packing added dynamically
            'totalPrice' => $totalPrice, // Backward compatibility
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'shipping_cost' => 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'merchant_shipping_id' => $merchantId,
            'merchant_packing_id' => $merchantId,
            'step1' => $step1,
            'step2' => $step2, // ✅ Pass saved step2 data to view
            'country' => $country, // For tax calculation (N+1 FIX)
            'preloadedCountry' => $country, // Alias for Blade
            'isState' => $isState, // For tax calculation
            'is_merchant_checkout' => true,
            'merchant_id' => $merchantId,
            'merchantData' => $step2MerchantData['merchantData'], // N+1 FIX
            // Courier delivery data
            'courier_available' => $courierData['available'],
            'available_couriers' => $courierData['couriers'],
            'merchant_pickup_points' => $courierData['pickup_points'],
            'customer_city_id' => $courierData['customer_city_id'],
        ]);
    }

    /**
     * Step 2 Submit - حفظ بيانات الشحن للتاجر المحدد
     *
     * MERCHANT-ONLY LOGIC:
     * 1. Reads cart (READ-ONLY) to calculate merchant total
     * 2. Filters catalogItems by merchant_id
     * 3. Calculates shipping cost for this merchant only
     * 4. Saves to merchant_step2_{merchantId} session (not global step2)
     * 5. Does NOT modify cart or auth state
     *
     * @param Request $request
     * @param int $merchantId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkoutMerchantStep2Submit(Request $request, $merchantId)
    {
        $step2 = $request->all();
        $oldCart = Session::get('cart');

        // Check if cart contains physical catalogItems (digital catalogItems don't need shipping)
        $cart = new Cart($oldCart);
        $hasPhysicalProducts = false;
        foreach ($cart->items as $catalogItem) {
            $itemMerchantId = data_get($catalogItem, 'item.user_id') ?? data_get($catalogItem, 'item.merchant_user_id') ?? 0;
            if ($itemMerchantId == $merchantId) {
                // Check if this is a physical catalogItem (dp = 0)
                if (isset($catalogItem['dp']) && $catalogItem['dp'] == 0) {
                    $hasPhysicalProducts = true;
                    break;
                }
            }
        }

        // ✅ VALIDATION: Check if shipping method is selected (for physical catalogItems only)
        if ($hasPhysicalProducts) {
            $hasShipping = false;

            // Check for array format (multi-merchant shipping)
            if (isset($step2['shipping']) && is_array($step2['shipping'])) {
                // Check if this merchant has a shipping selection
                if (isset($step2['shipping'][$merchantId]) && !empty($step2['shipping'][$merchantId])) {
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

        $input = Session::get('merchant_step1_' . $merchantId) + $step2;

        // NOTE: Creating Cart instance for READ-ONLY access
        // This does NOT modify session - only used to read and filter merchant catalogItems
        $cart = new Cart($oldCart);

        // تصفية منتجات هذا التاجر فقط (merchant catalogItems only)
        $merchantTotal = 0;
        foreach ($cart->items as $catalogItem) {
            $itemMerchantId = data_get($catalogItem, 'item.user_id') ?? data_get($catalogItem, 'item.merchant_user_id') ?? 0;
            if ($itemMerchantId == $merchantId) {
                $merchantTotal += (float)($catalogItem['price'] ?? 0);
            }
        }

        // ========================================================================
        // ✅ UNIFIED: catalog_items_total is the RAW total (no discount subtracted)
        // ========================================================================
        $catalogItemsTotal = $merchantTotal;

        // Get discount code data (but DON'T subtract from catalogItemsTotal!)
        $discountAmount = 0;
        $discountCode = '';
        $discountPercentage = '';
        $discountCodeId = null;

        if (Session::has('discount_code_merchant_' . $merchantId)) {
            $discountAmount = (float)Session::get('discount_code_merchant_' . $merchantId, 0);
            $discountCode = Session::get('discount_code_value_merchant_' . $merchantId, '');
            $discountPercentage = Session::get('discount_percentage_merchant_' . $merchantId, '');
            $discountCodeId = Session::get('discount_code_id_merchant_' . $merchantId);
        } elseif (Session::has('discount_code')) {
            $discountAmount = (float)Session::get('discount_code', 0);
            $discountCode = Session::get('discount_code_value', '');
            $discountPercentage = Session::get('discount_percentage', '');
            $discountCodeId = Session::get('discount_code_id');
        }

        // For free_above calculation, use catalogItems total (before discount)
        $baseAmount = $catalogItemsTotal;

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
                    $merchantTryotoShipping = \App\Models\Shipping::where('user_id', $vid)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $merchantTryotoShipping ? (float)$merchantTryotoShipping->free_above : 0;

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


        // ✅ FIXED: Check for array format first (merchant checkout multi-merchant mode)
        // Modal sends: packeging[merchant_id] = package_id
        $packId = null;
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            // Format: packeging[merchant_id] = package_id
            $packId = (int)($step2['packeging'][$merchantId] ?? 0);
        } elseif (isset($step2['merchant_packing_id']) && $step2['merchant_packing_id']) {
            $packId = (int)$step2['merchant_packing_id'];
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

        // ========================================================================
        // ✅ COURIER DELIVERY HANDLING
        // ========================================================================
        $courierId = null;
        $courierFee = 0.0;
        $courierName = null;
        $pickupPointId = null;
        $deliveryType = 'shipping'; // Default: regular shipping

        // Check if courier delivery was selected
        if (isset($step2['delivery_type']) && $step2['delivery_type'] === 'courier') {
            $deliveryType = 'courier';
            $courierId = (int)($step2['courier_id'] ?? 0);
            $pickupPointId = (int)($step2['pickup_point_id'] ?? 0);

            if ($courierId > 0) {
                // Get courier fee from service area
                $customerCityId = (int)($step2['customer_city_id'] ?? 0);
                if ($customerCityId > 0) {
                    $serviceArea = CourierServiceArea::where('courier_id', $courierId)
                        ->where('city_id', $customerCityId)
                        ->first();
                    if ($serviceArea) {
                        $courierFee = (float)$serviceArea->price;
                        $courierName = $serviceArea->courier->name ?? 'Courier';
                    }
                }
            }

            // When using courier, clear regular shipping costs
            $shipping_cost_total = 0;
            $shipping_name = null;
            $is_free_shipping = false;
            $original_shipping_cost = 0;
            $free_shipping_discount = 0;
        }
        // Check if pickup was selected (no delivery)
        elseif (isset($step2['delivery_type']) && $step2['delivery_type'] === 'pickup') {
            $deliveryType = 'pickup';
            $pickupPointId = (int)($step2['pickup_point_id'] ?? 0);

            // Clear all shipping costs
            $shipping_cost_total = 0;
            $shipping_name = null;
            $is_free_shipping = false;
            $original_shipping_cost = 0;
            $free_shipping_discount = 0;
            $courierFee = 0;
        }

        // Get tax data from merchant step1
        $step1Data = Session::get('merchant_step1_' . $merchantId);
        $taxAmount = $step1Data['tax_amount'] ?? 0;
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';

        // ========================================================================
        // ✅ UNIFIED PRICE CALCULATION
        // ========================================================================
        // catalog_items_total = RAW catalogItems (no discount)
        // subtotal = catalog_items_total - discount_amount
        // grand_total = subtotal + tax + shipping + packing + courier_fee
        // subtotal_before_discount = catalog_items_total + tax + shipping + packing + courier_fee (for discount recalculation)

        $subtotal = $catalogItemsTotal - $discountAmount;
        $grandTotal = $subtotal + $taxAmount + $shipping_cost_total + $packing_cost_total + $courierFee;
        $subtotalBeforeDiscount = $catalogItemsTotal + $taxAmount + $shipping_cost_total + $packing_cost_total + $courierFee;

        // Save all data to step2
        $step2['catalog_items_total'] = $catalogItemsTotal;          // ✅ RAW catalogItems total
        $step2['discount_amount'] = $discountAmount;                 // ✅ Discount amount
        $step2['discount_code'] = $discountCode;                     // ✅ Discount code
        $step2['discount_percentage'] = $discountPercentage;         // ✅ Discount percentage
        $step2['discount_code_id'] = $discountCodeId;                // ✅ Discount Code ID
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
        $step2['subtotal_before_discount'] = $subtotalBeforeDiscount;    // ✅ For discount recalculation
        $step2['grand_total'] = $grandTotal;                         // ✅ Final amount
        $step2['total'] = $grandTotal;                               // Backward compatibility
        $step2['final_total'] = $grandTotal;                         // ✅ Alias

        // ✅ Courier delivery data
        $step2['delivery_type'] = $deliveryType;                     // 'shipping', 'courier', 'pickup'
        $step2['courier_id'] = $courierId;
        $step2['courier_name'] = $courierName;
        $step2['courier_fee'] = $courierFee;
        $step2['pickup_point_id'] = $pickupPointId;

        // ✅ Save raw shipping/packing selections for restore on refresh/back
        if (isset($step2['shipping']) && is_array($step2['shipping'])) {
            $step2['saved_shipping_selections'] = $step2['shipping'];
        }
        if (isset($step2['packeging']) && is_array($step2['packeging'])) {
            $step2['saved_packing_selections'] = $step2['packeging'];
        }

        Session::put('merchant_step2_' . $merchantId, $step2);
        Session::save(); // Ensure session is saved before redirect

        return redirect()->route('front.checkout.merchant.step3', $merchantId);
    }

    /**
     * Step 3 - Display payment methods for specific merchant ONLY
     *
     * CRITICAL MULTI-MERCHANT LOGIC:
     * 1. Shows ONLY payment gateways where user_id = merchant_id
     * 2. NO FALLBACK to global/admin payment methods
     * 3. If merchant has no payment methods: ERROR and redirect
     * 4. Filters cart to merchant's catalogItems only
     * 5. Calculates totals for this merchant only
     *
     * This ensures each merchant uses ONLY their configured payment methods
     *
     * @param int $merchantId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function checkoutMerchantStep3($merchantId)
    {
        if (!Session::has('merchant_step1_' . $merchantId)) {
            return redirect()->route('front.checkout.merchant', $merchantId)->with('success', __("Please fill up step 1."));
        }
        if (!Session::has('merchant_step2_' . $merchantId)) {
            return redirect()->route('front.checkout.merchant.step2', $merchantId)->with('success', __("Please fill up step 2."));
        }
        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $step1 = (object) Session::get('merchant_step1_' . $merchantId);
        $step2 = (object) Session::get('merchant_step2_' . $merchantId);

        // Get merchant cart data using helper method (avoids code duplication)
        $cartData = $this->getMerchantCartData($merchantId);
        $merchantItems = $cartData['merchantItems'];
        $catalogItemsTotal = $cartData['totalPrice']; // CatalogItems only (no shipping)
        $totalQty = $cartData['totalQty'];
        $dp = $cartData['digital'];

        // Get payment gateways for THIS merchant ONLY (NO FALLBACK)
        // CRITICAL: Only show payment methods owned by this merchant
        // scopeHasGateway returns a Collection, so we filter it
        $allGateways = MerchantPayment::scopeHasGateway($this->curr->id);
        $gateways = $allGateways->where('user_id', $merchantId)
            ->where('checkout', 1); // checkout=1 means enabled for checkout

        // If merchant has no payment methods, show error (NO FALLBACK to admin methods)
        if ($gateways->isEmpty()) {
            return redirect()->route('front.cart')->with('unsuccess', __("No payment methods available for this merchant currently."));
        }

        // جلب طرق الشحن
        $shipping_data = \App\Models\Shipping::forMerchant($merchantId)->get();

        // جلب طرق التغليف
        $package_data = DB::table('packages')->where('user_id', $merchantId)->get();
        // No fallback to user 0 - if merchant has no packages, collection will be empty

        $pickups = DB::table('pickups')->get();
        $curr = $this->curr;

        $paystack = MerchantPayment::whereKeyword('paystack')->first();
        $paystackData = $paystack ? $paystack->convertAutoData() : [];

        // CRITICAL: Use total from step2 (catalogItems + shipping + any adjustments)
        // step2 already calculated: merchantTotal + shipping_cost
        // This is the ONLY source of truth for final total
        $finalTotal = $step2->total ?? $catalogItemsTotal;

        // ✅ N+1 FIX: Pre-load country for step3
        $preloadedCountry = CheckoutDataService::loadCountry($step1);

        return view('frontend.checkout.step3', [
            'catalogItems' => $merchantItems,
            'catalogItemsTotal' => $catalogItemsTotal, // CatalogItems only - ALWAYS for "Total MRP" display
            'totalPrice' => $catalogItemsTotal, // Keep same as catalogItemsTotal for backward compatibility
            'pickups' => $pickups,
            'totalQty' => $totalQty,
            'gateways' => $gateways,
            'shipping_cost' => $step2->shipping_cost ?? 0,
            'digital' => $dp,
            'curr' => $curr,
            'shipping_data' => $shipping_data,
            'package_data' => $package_data,
            'merchant_shipping_id' => $merchantId,
            'merchant_packing_id' => $merchantId,
            'paystack' => $paystackData,
            'step2' => $step2, // CRITICAL: Contains pre-calculated total (catalogItems + tax + shipping)
            'step1' => $step1,
            'is_merchant_checkout' => true,
            'merchant_id' => $merchantId,
            'preloadedCountry' => $preloadedCountry,
        ]);
    }

    /**
     * Helper: Group catalogItems by merchant ID - READ-ONLY
     *
     * SAFETY:
     * 1. Receives array of catalogItems (already filtered)
     * 2. Groups them by merchant_id
     * 3. Does NOT access session
     * 4. Does NOT modify cart
     * 5. Pure data transformation
     *
     * Used by checkout steps to organize display.
     *
     * @param array $items Cart items (already filtered if merchant-specific)
     * @return array Grouped items by merchant_id with metadata
     */
    private function groupItemsByMerchant(array $items): array
    {
        $grouped = [];

        foreach ($items as $rowKey => $cartItem) {
            $merchantId = data_get($cartItem, 'item.user_id') ?? 0;

            if (!isset($grouped[$merchantId])) {
                $merchant = \App\Models\User::find($merchantId);
                $grouped[$merchantId] = [
                    'merchant_id' => $merchantId,
                    'merchant_name' => $merchant ? ($merchant->shop_name ?? $merchant->name) : 'Unknown',
                    'items' => [],
                    'total' => 0,
                    'count' => 0,
                ];
            }

            $grouped[$merchantId]['items'][$rowKey] = $cartItem;
            $grouped[$merchantId]['total'] += (float)($cartItem['price'] ?? 0);
            $grouped[$merchantId]['count'] += (int)($cartItem['qty'] ?? 1);
        }

        return $grouped;
    }

    /**
     * Remove merchant items from cart after payment completion.
     *
     * WARNING: This method MODIFIES cart session
     * ONLY call this AFTER successful payment completion
     *
     * USAGE:
     * 1. Called by payment controllers after payment success
     * 2. Removes merchant's items from cart
     * 3. Keeps other merchants' items intact
     * 4. Cleans up merchant-specific session data
     * 5. Does NOT affect auth state
     *
     * @param int $merchantId Merchant user ID
     * @return void
     */
    public static function removeMerchantItemsFromCart($merchantId)
    {
        if (!Session::has('cart')) {
            return;
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        // تصفية المنتجات: الاحتفاظ فقط بمنتجات التجار الآخرين
        $remainingItems = [];
        foreach ($cart->items as $rowKey => $catalogItem) {
            $itemMerchantId = data_get($catalogItem, 'item.user_id') ?? data_get($catalogItem, 'item.merchant_user_id') ?? 0;

            // إذا لم يكن من نفس التاجر، نحتفظ به
            if ($itemMerchantId != $merchantId) {
                $remainingItems[$rowKey] = $catalogItem;
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
        Session::forget('merchant_step1_' . $merchantId);
        Session::forget('merchant_step2_' . $merchantId);
        Session::forget('discount_code_merchant_' . $merchantId);
        Session::forget('checkout_merchant_id');
    }

    /**
     * N+1 OPTIMIZATION: Prepare merchant data for step2 view.
     * Pre-loads all merchant data (shipping, packaging, merchant info) in bulk
     * to avoid N+1 queries inside Blade template.
     *
     * @param array $cartItems Cart items array
     * @param mixed $step1 Step 1 session data
     * @return array Merchant data and country info
     */
    protected function prepareStep2MerchantData(array $cartItems, $step1 = null): array
    {
        // Pre-load all merchant data using CheckoutDataService
        $merchantData = CheckoutDataService::loadMerchantData($cartItems);

        // Pre-load country data
        $country = CheckoutDataService::loadCountry($step1);

        return [
            'merchantData' => $merchantData,
            'country' => $country,
        ];
    }

    /**
     * API: Get delivery options based on customer's city
     *
     * Called via AJAX when customer selects location from map
     * Returns available delivery methods: local courier, pickup, shipping companies
     *
     * @param Request $request
     * @param int $merchantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeliveryOptions(Request $request, $merchantId)
    {
        $cityId = $request->input('city_id');
        $cityName = $request->input('city_name');

        // Try to find city by ID first, then by name
        $customerCityId = null;
        if ($cityId) {
            $customerCityId = (int)$cityId;
        } elseif ($cityName) {
            $city = City::where('city_name', 'LIKE', '%' . $cityName . '%')->first();
            if ($city) {
                $customerCityId = $city->id;
            }
        }

        $result = [
            'success' => true,
            'customer_city_id' => $customerCityId,
            'city_matched' => false,
            'delivery_options' => [],
            'local_couriers' => [],
            'pickup_available' => false,
            'pickup_points' => [],
        ];

        // ========================================================================
        // 1. SHIPPING TO ADDRESS - ALWAYS AVAILABLE (first option)
        // ========================================================================
        $result['delivery_options'][] = [
            'type' => 'shipping_company',
            'label' => __('Ship to Address'),
            'description' => __('Delivery by shipping company to your address'),
            'icon' => 'fa-truck',
        ];

        // ========================================================================
        // 2. CHECK LOCAL COURIERS (only if customer is in merchant's service area)
        // ========================================================================
        if ($customerCityId) {
            // Check if merchant has pickup points (warehouse) in customer's city
            $merchantHasWarehouseInCity = PickupPoint::where('user_id', $merchantId)
                ->where('city_id', $customerCityId)
                ->where('status', 1)
                ->exists();

            if ($merchantHasWarehouseInCity) {
                $result['city_matched'] = true;

                // Get available local couriers for this city
                $serviceAreas = CourierServiceArea::where('city_id', $customerCityId)
                    ->with('courier')
                    ->whereHas('courier', function ($query) {
                        $query->where('status', 1);
                    })
                    ->get();

                if ($serviceAreas->isNotEmpty()) {
                    $result['local_couriers'] = $serviceAreas->map(function ($area) {
                        return [
                            'courier_id' => $area->courier_id,
                            'courier_name' => $area->courier->name,
                            'delivery_fee' => (float)$area->price,
                            'service_area_id' => $area->id,
                        ];
                    });

                    // Add local courier option (second option)
                    $result['delivery_options'][] = [
                        'type' => 'local_courier',
                        'label' => __('Delivery by Courier'),
                        'description' => __('Fast delivery by local courier'),
                        'icon' => 'fa-motorcycle',
                    ];
                }
            }
        }

        return response()->json($result);
    }

    /**
     * Get courier delivery data for a merchant and customer location.
     *
     * Checks if:
     * 1. Merchant has pickup points in customer's city
     * 2. Couriers are available in customer's city
     *
     * @param int $merchantId
     * @param object|null $step1 Step 1 session data containing customer_city
     * @return array
     */
    protected function getCourierDeliveryData(int $merchantId, $step1 = null): array
    {
        $result = [
            'available' => false,
            'couriers' => [],
            'pickup_points' => [],
            'customer_city_id' => null,
        ];

        if (!$step1) {
            return $result;
        }

        // Get customer coordinates
        $customerLat = isset($step1->latitude) ? (float)$step1->latitude : null;
        $customerLng = isset($step1->longitude) ? (float)$step1->longitude : null;

        // Try to get customer city ID from step1
        $customerCityId = null;

        // First check if customer_city_id is directly available (from step1 form hidden field)
        if (!empty($step1->customer_city_id)) {
            $customerCityId = (int)$step1->customer_city_id;
        }
        // Fallback: check city_id
        elseif (!empty($step1->city_id)) {
            $customerCityId = (int)$step1->city_id;
        }
        // Otherwise try to find city by name
        elseif (!empty($step1->customer_city)) {
            $city = City::where('city_name', $step1->customer_city)->first();
            if ($city) {
                $customerCityId = $city->id;
            }
        }

        $result['customer_city_id'] = $customerCityId;

        // ========================================================================
        // STRATEGY 1: Search by city_id (exact match)
        // ========================================================================
        $merchantPickupPoints = collect();
        $serviceAreas = collect();

        if ($customerCityId) {
            // Check if merchant has pickup points in customer's city
            $merchantPickupPoints = PickupPoint::where('user_id', $merchantId)
                ->where('city_id', $customerCityId)
                ->where('status', 1)
                ->get();

            if ($merchantPickupPoints->isNotEmpty()) {
                // Get available couriers for this city
                $serviceAreas = CourierServiceArea::where('city_id', $customerCityId)
                    ->with('courier')
                    ->whereHas('courier', function ($query) {
                        $query->where('status', 1);
                    })
                    ->get();
            }
        }

        // ========================================================================
        // STRATEGY 2: Fallback to coordinate-based search (within radius)
        // Used when city matching fails but coordinates are available
        // ========================================================================
        if ($serviceAreas->isEmpty() && $customerLat && $customerLng) {
            // Find merchant pickup points within radius using Haversine formula
            $merchantPickupPoints = $this->findPickupPointsNearLocation(
                $merchantId,
                $customerLat,
                $customerLng,
                20 // Default 20km radius
            );

            if ($merchantPickupPoints->isNotEmpty()) {
                // Find courier service areas within radius
                $serviceAreas = $this->findCourierServiceAreasNearLocation(
                    $customerLat,
                    $customerLng,
                    20 // Default 20km radius
                );
            }
        }

        // No pickup points found = no courier delivery available
        if ($merchantPickupPoints->isEmpty()) {
            return $result;
        }

        $result['pickup_points'] = $merchantPickupPoints;

        // No couriers found
        if ($serviceAreas->isEmpty()) {
            return $result;
        }

        // Format couriers for view with full details
        $couriers = [];
        foreach ($serviceAreas as $area) {
            $courier = $area->courier;
            $couriers[] = [
                'courier_id' => $area->courier_id,
                'courier_name' => $courier->name,
                'courier_photo' => $courier->photo ? asset('assets/images/couriers/' . $courier->photo) : asset('assets/images/noimage.png'),
                'courier_phone' => $courier->phone,
                'courier_address' => $courier->address,
                'delivery_fee' => (float)$area->price,
                'service_area_id' => $area->id,
                'city_name' => $area->city->city_name ?? '',
                'distance_km' => $area->distance_km ?? null,
            ];
        }

        $result['available'] = true;
        $result['couriers'] = $couriers;

        return $result;
    }

    /**
     * Find merchant pickup points within radius using Haversine formula
     *
     * @param int $merchantId
     * @param float $lat Customer latitude
     * @param float $lng Customer longitude
     * @param int $radiusKm Search radius in kilometers
     * @return \Illuminate\Support\Collection
     */
    protected function findPickupPointsNearLocation(int $merchantId, float $lat, float $lng, int $radiusKm = 20)
    {
        // Haversine formula to calculate distance
        // 6371 = Earth's radius in km
        return PickupPoint::where('user_id', $merchantId)
            ->where('status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance_km
            ", [$lat, $lng, $lat])
            ->havingRaw("distance_km <= ?", [$radiusKm])
            ->orderBy('distance_km')
            ->get();
    }

    /**
     * Find courier service areas within radius using Haversine formula
     *
     * @param float $lat Customer latitude
     * @param float $lng Customer longitude
     * @param int $radiusKm Search radius in kilometers
     * @return \Illuminate\Support\Collection
     */
    protected function findCourierServiceAreasNearLocation(float $lat, float $lng, int $radiusKm = 20)
    {
        return CourierServiceArea::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('courier')
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->selectRaw("
                courier_service_areas.*,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance_km
            ", [$lat, $lng, $lat])
            ->havingRaw("distance_km <= ?", [$radiusKm])
            ->orderBy('distance_km')
            ->get();
    }
}
