<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Order,
    Classes\MuaadhMailer
};
use App\Helpers\PriceHelper;
use App\Models\Country;
use App\Models\Reward;
use App\Models\StockReservation;
use App\Traits\HandlesVendorCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use OrderHelper;
use Illuminate\Support\Str;

class ManualPaymentController extends CheckoutBaseControlller
{
    use HandlesVendorCheckout, SavesCustomerShippingChoice;

    public function store(Request $request)
    {
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];
        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = $request->all();
        $input = array_merge($step1, $step2, $input);
        
        $rules = ['txnid' => 'required'];
        $messages = ['required' => __('The Transaction ID field is required.')];
        \Validator::make($input, $rules, $messages);
        if ($request->pass_check) {
            $auth = OrderHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForVendor($originalCart, $vendorId);
        OrderHelper::license_check($cart); // For License Checking

        $new_cart = [];
        $new_cart['totalQty'] = $cart->totalQty;
        $new_cart['totalPrice'] = $cart->totalPrice;
        $new_cart['items'] = $cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);
        // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $orderTotal = $prepared['order_total'];

        $order = new Order;
        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
        $input['cart'] = $new_cart;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $orderTotal;
        $input['order_number'] = Str::random(4) . time();
        $input['wallet_price'] = $request->wallet_price / $this->curr->value;

        // Get tax data from vendor step2
        $input['tax'] = $step2['tax_amount'] ?? 0;
        $input['tax_location'] = $step2['tax_location'] ?? '';


        if (Session::has('affilate')) {
            $val = $request->total / $this->curr->value;
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
                $user = OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $order->fill($input)->save();

        // Clear stock reservations after successful order
        StockReservation::clearAfterPurchase();

        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();

        if ($input['discount_code_id'] != "") {
            OrderHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
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
    }
}
