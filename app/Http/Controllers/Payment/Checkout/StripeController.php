<?php

/**
 * ====================================================================
 * STRIPE PAYMENT CONTROLLER - VENDOR CHECKOUT ONLY
 * ====================================================================
 *
 * Modified: 2025-01-19 for Vendor Checkout System
 * - Uses HandlesVendorCheckout trait
 * - Reads from vendor_step1/step2 ONLY
 * - Filters cart for vendor products
 * ====================================================================
 */

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\MuaadhMailer;
use App\Models\Cart;use App\Models\Country;use App\Models\Generalsetting;

use App\Models\Order;

use App\Models\PaymentGateway;
use App\Models\Reward;
use App\Models\State;
use App\Traits\HandlesVendorCheckout;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OrderHelper;
use Session;
use Str;

class StripeController extends CheckoutBaseControlller
{
    use HandlesVendorCheckout, SavesCustomerShippingChoice;
    public function __construct()
    {
        parent::__construct();
        $data = PaymentGateway::whereKeyword('stripe')->first();
        $paydata = $data->convertAutoData();
        \Config::set('services.stripe.key', $paydata['key']);
        \Config::set('services.stripe.secret', $paydata['secret']);
    }

    public function store(Request $request)
    {
        // Get vendor checkout data
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];

        // Get steps from vendor sessions ONLY
        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = $request->all();
        $input = array_merge($step1, $step2, $input);

        if ($request->pass_check) {
            $auth = OrderHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        try {
            $oldCart = Session::get('cart');
            $originalCart = new Cart($oldCart);
            $cart = $this->filterCartForVendor($originalCart, $vendorId);
            $gs = Generalsetting::first();
            $total = $request->total / $this->curr->value;
            $total = $total * $this->curr->value;

            $stripe_secret_key = Config::get('services.stripe.secret');
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            $checkout_session = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "success_url" => route('front.stripe.notify') . '?session_id={CHECKOUT_SESSION_ID}',
                "cancel_url" => route('front.payment.cancle'),
                "locale" => "auto",
                "line_items" => [
                    [
                        "quantity" => $cart->totalQty,
                        "price_data" => [
                            "currency" => $this->curr->name,
                            "unit_amount" => round($total / $cart->totalQty, 2) * 100,
                            "product_data" => [
                                "name" => $gs->title . 'Payment',
                            ],
                        ],
                    ],

                ],
            ]);

            Session::put('input_data', $input);
            return redirect($checkout_session->url);
        } catch (Exception $e) {
            dd($e->getMessage());
            return back()->with('unsuccess', $e->getMessage());
        }
    }

    public function notify(Request $request)
    {
        // Get vendor checkout data
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];

        // Get steps from vendor sessions
        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = Session::get('input_data');
        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($request->session_id);
       
        if ($response->status == 'complete') {
            $oldCart = Session::get('cart');
            $originalCart = new Cart($oldCart);
            $cart = $this->filterCartForVendor($originalCart, $vendorId);

            OrderHelper::license_check($cart); // For License Checking

            // Serialize filtered cart for order
            $new_cart = [];
            $new_cart['totalQty'] = $cart->totalQty;
            $new_cart['totalPrice'] = $cart->totalPrice;
            $new_cart['items'] = $cart->items;
            $new_cart = json_encode($new_cart);
            $temp_affilate_users = \OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
            $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);
            
            // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
            $prepared = $this->prepareOrderData($input, $cart);
            $input = $prepared['input'];
            $orderTotal = $prepared['order_total'];

            $order = new Order;
            $input['cart'] = $new_cart;
            $input['user_id'] = Auth::check() ? Auth::user()->id : null;
            $input['affilate_users'] = $affilate_users;
            $input['pay_amount'] = $orderTotal;
            $input['order_number'] = Str::random(4) . time();
            $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
            $input['payment_status'] = 'Completed';
            $input['txnid'] = $response->payment_intent;
            $input['method'] = 'Stripe';

            // Get tax data from vendor step2 (already fetched at method start)
            $input['tax'] = $step2['tax_amount'] ?? 0;
            $input['tax_location'] = $step2['tax_location'] ?? '';

            if ($input['dp'] == 1) {
                $input['status'] = 'completed';
            }
            if (Session::has('affilate')) {
                $val = $input['total'] / $this->curr->value;
                $val = $val / 100;
                $sub = $val * $this->gs->affilate_charge;
                if ($temp_affilate_users != null) {
                    $t_sub = 0;
                    foreach ($temp_affilate_users as $t_cost) {
                        $t_sub += $t_cost['charge'];
                    }
                    $sub = $sub - $t_sub;
                }

                if ($sub > 0) {
                    OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                    $input['affilate_user'] = Session::get('affilate');
                    $input['affilate_charge'] = $sub;
                }
            }
            // fake
            $input['shipping'] = null;
            $input['packeging'] = null;

            $order->fill($input)->save();
            $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
            $order->notifications()->create();

            if ($input['coupon_id'] != "") {
                OrderHelper::coupon_check($input['coupon_id']); // For Coupon Checking
            }

            if (Auth::check()) {
                if ($this->gs->is_reward == 1) {
                    $num = $order->pay_amount;
                    $rewards = Reward::get();
                    foreach ($rewards as $i) {
                        $smallest[$i->order_amount] = abs($i->order_amount - $num);
                    }

                    if (isset($smallest)) {
                        asort($smallest);
                        $final_reword = Reward::where('order_amount', key($smallest))->first();
                        Auth::user()->update(['reward' => (Auth::user()->reward + $final_reword->reward)]);
                    }
                }
            }

            OrderHelper::size_qty_check($cart); // For Size Quantiy Checking
            OrderHelper::stock_check($cart); // For Stock Checking
            OrderHelper::vendor_order_check($cart, $order); // For Vendor Order Checking

            Session::put('temporder', $order);
            Session::put('tempcart', $cart);

            // Remove only vendor's products from cart
            $this->removeVendorProductsFromCart($vendorId, $originalCart);

            if ($order->user_id != 0 && $order->wallet_price != 0) {
                OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
            }

            //Sending Email To Buyer
            $data = [
                'to' => $order->customer_email,
                'type' => "new_order",
                'cname' => $order->customer_name,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'wtitle' => "",
                'onumber' => $order->order_number,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendAutoOrderMail($data, $order->id);

            //Sending Email To Admin
            $data = [
                'to' => $this->ps->contact_email,
                'subject' => "New Order Recieved!!",
                'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            // Determine success URL based on remaining cart items
            $success_url = $this->getSuccessUrl($vendorId, $originalCart);
            return redirect($success_url);
        } else {
            return redirect(route('front.payment.cancle'));
        }
    }
}
