<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Purchase,
    Classes\Instamojo,
    Classes\MuaadhMailer,
    Models\PaymentGateway
};
use App\Helpers\PriceHelper;
use App\Models\Country;
use App\Models\Reward;
use App\Traits\HandlesMerchantCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use PurchaseHelper;
use Illuminate\Support\Str;

class InstamojoController extends CheckoutBaseControlller
{
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;

    public function store(Request $request)
    {
        // Get merchant checkout data
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];

        // Get steps from merchant sessions ONLY
        $steps = $this->getCheckoutSteps($merchantId, $merchantData['is_merchant_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = array_merge($step1, $step2, $request->all());
        
        $data = PaymentGateway::whereKeyword('instamojo')->first();
        $total = $request->total;
        $paydata = $data->convertAutoData();
        if ($this->curr->name != "INR") {
            return redirect()->back()->with('unsuccess', __('Please Select INR Currency For This Payment.'));
        }

        if ($request->pass_check) {
            $auth = PurchaseHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $order['item_name'] = $this->gs->title . " Order";
        $order['item_number'] = Str::random(4) . time();
        $order['item_amount'] = $total;
        $cancel_url = route('front.payment.cancle');
        $notify_url = route('front.instamojo.notify');

        if ($paydata['sandbox_check'] == 1) {
            $api = new Instamojo($paydata['key'], $paydata['token'], 'https://test.instamojo.com/api/1.1/');
        } else {
            $api = new Instamojo($paydata['key'], $paydata['token']);
        }

        // ✅ استخدام المبلغ من step3 مباشرة (لا إعادة حساب)
        $total = round($total / $this->curr->value, 2);


        try {
            $response = $api->paymentRequestCreate(array(
                "purpose" => $order['item_name'],
                "amount" => $total,
                "send_email" => true,
                "email" => $request->customer_email,
                "redirect_url" => $notify_url
            ));
            $redirect_url = $response['longurl'];
            /** add payment ID to session **/
            Session::put('input_data', $input);
            Session::put('order_data', $order);
            Session::put('order_payment_id', $response['id']);

            return redirect($redirect_url);
        } catch (Exception $e) {
            return redirect($cancel_url)->with('unsuccess', 'Error: ' . $e->getMessage());
        }
    }

    public function notify(Request $request)
    {
        // Get merchant checkout data at start
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];

        $steps = $this->getCheckoutSteps($merchantId, $merchantData['is_merchant_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = Session::get('input_data');
        $order_data = Session::get('order_data');
        $cancel_url = route('front.payment.cancle');
        $input_data = $request->all();

        /** Get the payment ID before session clear **/
        $payment_id = Session::get('order_payment_id');

        if ($input_data['payment_status'] == 'Failed') {
            return redirect($cancel_url);
        }

        if ($input_data['payment_request_id'] == $payment_id) {

            $oldCart = Session::get('cart');
            $originalCart = new Cart($oldCart);
            $cart = $this->filterCartForMerchant($originalCart, $merchantId);
            PurchaseHelper::license_check($cart); // For License Checking
            $t_oldCart = Session::get('cart');
            $t_cart = new Cart($t_oldCart);
            $new_cart = [];
            $new_cart['totalQty'] = $t_cart->totalQty;
            $new_cart['totalPrice'] = $t_cart->totalPrice;
            $new_cart['items'] = $t_cart->items;
            $new_cart = json_encode($new_cart);
            $temp_affilate_users = PurchaseHelper::product_affilate_check($cart); // For Product Based Affilate Checking
            $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

            // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
            $prepared = $this->prepareOrderData($input, $cart);
            $input = $prepared['input'];
            $orderTotal = $prepared['order_total'];

            $purchase = new Purchase;
            $input['cart'] = $new_cart;
            $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
            $input['affilate_users'] = $affilate_users;
            $input['pay_amount'] = $orderTotal;
            $input['purchase_number'] = $order_data['item_number'];
            $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
            $input['payment_status'] = "Completed";
            $input['txnid'] = $input_data['payment_id'];

            // Get tax data from merchant step2
            $input['tax'] = $step2['tax_amount'] ?? 0;
            $input['tax_location'] = $step2['tax_location'] ?? '';


            if ($input['dp'] == 1) {
                $input['status'] = 'completed';
            }
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
                    $user = PurchaseHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                    $input['affilate_user'] = Session::get('affilate');
                    $input['affilate_charge'] = $sub;
                }
            }
            $purchase->fill($input)->save();
            $purchase->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
            $purchase->notifications()->create();

            if ($input['discount_code_id'] != "") {
                PurchaseHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
            }

            if (Auth::check()) {
                if ($this->gs->is_reward == 1) {
                    $num = $purchase->pay_amount;
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

            PurchaseHelper::size_qty_check($cart); // For Size Quantiy Checking
            PurchaseHelper::stock_check($cart); // For Stock Checking
            PurchaseHelper::merchant_purchase_check($cart, $purchase); // For Merchant Purchase Checking

            Session::put('temporder', $purchase);
            Session::put('tempcart', $cart);

            // Remove only merchant's items from cart
            $this->removeMerchantItemsFromCart($merchantId, $originalCart);

            if ($purchase->user_id != 0 && $purchase->wallet_price != 0) {
                PurchaseHelper::add_to_transaction($purchase, $purchase->wallet_price); // Store To Transactions
            }

            //Sending Email To Buyer
            $data = [
                'to' => $purchase->customer_email,
                'type' => "new_order",
                'cname' => $purchase->customer_name,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'wtitle' => "",
                'onumber' => $purchase->purchase_number,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendAutoOrderMail($data, $purchase->id);

            //Sending Email To Admin
            $data = [
                'to' => $this->ps->contact_email,
                'subject' => "New Purchase Recieved!!",
                'body' => "Hello Admin!<br>Your store has received a new purchase.<br>Purchase Number is " . $purchase->purchase_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            // Determine success URL based on remaining cart items
            $success_url = $this->getSuccessUrl($merchantId, $originalCart);
            return redirect($success_url);
        }
        return redirect($cancel_url);
    }
}
