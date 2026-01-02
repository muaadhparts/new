<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Purchase,
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

class FlutterwaveController extends CheckoutBaseControlller
{
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;
    public $public_key;
    private $secret_key;

    public function __construct()
    {
        parent::__construct();
        $data = PaymentGateway::whereKeyword('flutterwave')->first();
        $paydata = $data->convertAutoData();
        $this->public_key = $paydata['public_key'];
        $this->secret_key = $paydata['secret_key'];
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Get merchant checkout data
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];
        $isMerchantCheckout = $merchantData['is_merchant_checkout'];

        // Get steps from merchant sessions
        $steps = $this->getCheckoutSteps($merchantId, $isMerchantCheckout);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = array_merge($step1, $step2, $input);
        $data = PaymentGateway::whereKeyword('flutterwave')->first();
        $curr = $this->curr;
        $total = $request->total;

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Flutterwave Payment.'));
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
        $notify_url = route('front.flutter.notify');

        Session::put('input_data', $input);
        Session::put('order_data', $order);
        Session::put('order_payment_id', $order['item_number']);

        // SET CURL

        $curl = curl_init();

        $customer_email = $request->customer_email;
        $amount = $order['item_amount'];
        $currency = $this->curr->name;
        $txref = $order['item_number']; // ensure you generate unique references per transaction.
        $PBFPubKey = $this->public_key; // get your public key from the dashboard.
        $redirect_url = $notify_url;
        $payment_plan = ""; // this is only required for recurring payments.


        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $amount,
                'customer_email' => $customer_email,
                'currency' => $currency,
                'txref' => $txref,
                'PBFPubKey' => $PBFPubKey,
                'redirect_url' => $redirect_url,
                'payment_plan' => $payment_plan
            ]),
            CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache"
            ],
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            // there was an error contacting the rave API
            return redirect($cancel_url)->with('unsuccess', 'Curl returned error: ' . $err);
        }

        $transaction = json_decode($response);

        if (!$transaction->data && !$transaction->data->link) {
            // there was an error from the API
            return redirect($cancel_url)->with('unsuccess', 'API returned error: ' . $transaction->message);
        }

        return redirect($transaction->data->link);
    }


    public function notify(Request $request)
    {
        $input_data = $request->all();

        // Get merchant checkout data
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];
        $isMerchantCheckout = $merchantData['is_merchant_checkout'];

        // Get steps from merchant sessions
        $steps = $this->getCheckoutSteps($merchantId, $isMerchantCheckout);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        $input = Session::get('input_data');

        if ($request->cancelled == "true") {
            return redirect()->route('front.cart')->with('success', __('Payment Cancelled!'));
        }

        $order_data = Session::get('order_data');

        // Get cart and filter for merchant
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $success_url = $this->getSuccessUrl($merchantId, $originalCart);
        $cancel_url = route('front.payment.cancle');

        /** Get the payment ID before session clear **/
        $payment_id = Session::get('order_payment_id');

        if (Session::has('currency')) {
            $this->curr = \DB::table('currencies')->find(Session::get('currency'));
        } else {
            $this->curr = \DB::table('currencies')->where('is_default', '=', 1)->first();
        }

        if (isset($input_data['txref'])) {

            $ref = $payment_id;

            $query = array(
                "SECKEY" => $this->secret_key,
                "txref" => $ref
            );

            $data_string = json_encode($query);

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $response = curl_exec($ch);

            curl_close($ch);

            $resp = json_decode($response, true);

            if ($resp['status'] = "success") {
                if (!empty($resp['data'])) {

                    $paymentStatus = $resp['data']['status'];
                    $chargeResponsecode = $resp['data']['chargecode'];

                    if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {

                        // Filter cart for merchant checkout
                        $cart = $this->filterCartForMerchant($originalCart, $merchantId);
                        PurchaseHelper::license_check($cart); // For License Checking

                        $new_cart = [];
                        $new_cart['totalQty'] = $cart->totalQty;
                        $new_cart['totalPrice'] = $cart->totalPrice;
                        $new_cart['items'] = $cart->items;
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
                        $input['txnid'] = $resp['data']['txid'];

                        // Get tax data from step2 (already calculated and saved)
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

                                if(isset($smallest)){
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

                        return redirect($success_url);
                    }
                }
            }
            return redirect($cancel_url);
        }
        return redirect($cancel_url);
    }
}
