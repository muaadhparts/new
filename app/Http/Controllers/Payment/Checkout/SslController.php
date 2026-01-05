<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Purchase,
    Classes\MuaadhMailer,
    Models\MerchantPayment
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

class SslController extends CheckoutBaseControlller
{
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;
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

        $data = MerchantPayment::whereKeyword('sslcommerz')->first();
        $paydata = $data->convertAutoData();

        $total = $request->total;


        if ($request->pass_check) {
            $auth = PurchaseHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }


        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $data['item_name'] = $this->gs->title . " Purchase";
        $data['item_number'] = Str::random(4) . time();
        $data['item_amount'] = $total;
        $data['txnid'] = "SSLCZ_TXN_" . uniqid();
        $cancel_url = route('front.payment.cancle');
        $notify_url = route('front.ssl.notify');

        // Get cart and filter for merchant
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);

        PurchaseHelper::license_check($cart); // For License Checking
        $new_cart = [];
        $new_cart['totalQty'] = $cart->totalQty;
        $new_cart['totalPrice'] = $cart->totalPrice;
        $new_cart['items'] = $cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = PurchaseHelper::item_affilate_check($cart); // For CatalogItem Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $purchaseTotal = $prepared['order_total'];

        $purchase = new Purchase;
        $input['cart'] = $new_cart;
        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $purchaseTotal;
        $input['purchase_number'] = $data['item_number'];
        $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
        $input['payment_status'] = "Pending";
        $input['txnid'] = $data['txnid'];


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

        if ($input['discount_code_id'] != "") {
            PurchaseHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
        }

        $post_data = array();
        $post_data['store_id'] = $paydata['store_id'];
        $post_data['store_passwd'] = $paydata['store_password'];
        $post_data['total_amount'] = $data['item_amount'];
        $post_data['currency'] = $this->curr->name;
        $post_data['tran_id'] = $data['txnid'];
        $post_data['success_url'] = $notify_url;
        $post_data['fail_url'] =  $cancel_url;
        $post_data['cancel_url'] =  $cancel_url;
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $input['customer_name'];
        $post_data['cus_email'] = $input['customer_email'];
        $post_data['cus_add1'] = $input['customer_address'];
        $post_data['cus_city'] = $input['customer_city'];
        $post_data['cus_state'] = $input['customer_state'];
        $post_data['cus_postcode'] = $input['customer_zip'];
        $post_data['cus_country'] = $input['customer_country'];
        $post_data['cus_phone'] = $input['customer_phone'];
        $post_data['cus_fax'] = $input['customer_phone'];

        # REQUEST SEND TO SSLCOMMERZ
        if ($paydata['sandbox_check'] == 1) {
            $direct_api_url = "https://sandbox.sslcommerz.com/gwprocess/v3/api.php";
        } else {
            $direct_api_url = "https://securepay.sslcommerz.com/gwprocess/v3/api.php";
        }


        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);


        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close($handle);
            return redirect($cancel_url);
            exit;
        }

        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true);


        if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {
            echo "<meta http-equiv='refresh' content='0;url=" . $sslcz['GatewayPageURL'] . "'>";
            # header("Location: ". $sslcz['GatewayPageURL']);
            exit;
        } else {
            return redirect($cancel_url);
        }
    }


    public function notify(Request $request)
    {
        $input_data = $request->all();

        // Get merchant checkout data
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];

        // Get cart and filter for merchant
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);

        $success_url = $this->getSuccessUrl($merchantId, $originalCart);
        $cancel_url = route('front.payment.cancle');

        /** Get the payment ID before session clear **/
        $payment_id = Session::get('order_payment_id');

        if (Session::has('currency')) {
            $this->curr = \DB::table('currencies')->find(Session::get('currency'));
        } else {
            $this->curr = \DB::table('currencies')->where('is_default', '=', 1)->first();
        }

        if ($input_data['status'] == 'VALID') {

            $purchase = Purchase::where('txnid', $request->tran_id)->first();
            $purchase->payment_status = 'Completed';
            $purchase->update();

            $purchase->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
            $purchase->notifications()->create();



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
                PurchaseHelper::add_to_wallet_log($purchase, $purchase->wallet_price); // Store To Wallet Log
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
            $mailer->sendAutoPurchaseMail($data, $purchase->id);

            //Sending Email To Admin
            $data = [
                'to' => $this->ps->contact_email,
                'subject' => "New Purchase Recieved!!",
                'body' => "Hello Operator!<br>Your store has received a new purchase.<br>Purchase Number is " . $purchase->purchase_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            return redirect($success_url);
        }
        return redirect($cancel_url);
    }
}
