<?php

/**
 * ====================================================================
 * PAYPAL PAYMENT CONTROLLER - MERCHANT CHECKOUT ONLY
 * ====================================================================
 *
 * This controller handles PayPal payments in merchant checkout system.
 *
 * Key Changes:
 * - Uses HandlesMerchantCheckout trait for merchant isolation
 * - Reads from merchant_step1_{id} and merchant_step2_{id} ONLY
 * - NO fallback to regular checkout sessions (step1/step2)
 * - Filters cart to process only merchant's catalogItems
 * - Removes only merchant's catalogItems from cart after purchase
 *
 * Modified: 2025-01-19 for Merchant Checkout System
 * ====================================================================
 */

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
use App\Models\StockReservation;
use App\Traits\HandlesMerchantCheckout;
use App\Traits\SavesCustomerShippingChoice;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Session;
use PurchaseHelper;
use Illuminate\Support\Str;
use Omnipay\Omnipay;

class PaypalController extends CheckoutBaseControlller
{
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;
    public $_api_context;
    public $gateway;
    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
    }

    public function store(Request $request)
    {
        // ====================================================================
        // MERCHANT CHECKOUT: Get merchant-specific session data
        // ====================================================================
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];
        $isMerchantCheckout = $merchantData['is_merchant_checkout'];

        // Get steps from merchant sessions ONLY
        $steps = $this->getCheckoutSteps($merchantId, $isMerchantCheckout);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        // Validate merchant checkout data exists
        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired. Please start checkout again.'));
        }

        $input = $request->all();
        $input = array_merge($step1, $step2, $input);
        
        $total = $request->total / $this->curr->value;
        $total = $total * $this->curr->value;

//        dd($total ,$input);


        PurchaseHelper::set_currency($this->curr->value); // For Converting Price

        $input['currency_sign'] = $this->curr->sign;
        $input['currency_name'] = $this->curr->value;


        if ($request->pass_check) {
            $auth = PurchaseHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }


        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $total = $request->total / $this->curr->value;
        $cancel_url = route('front.payment.cancle');
        $notify_url = route('front.paypal.notify');


//        dd($total ,$this->curr->name);
        try {
            $response = $this->gateway->purchase(array(
                'amount' => $total,
                'currency' => $this->curr->name,
                'currency' =>'USD',
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

//            dd($input ,$response->isSuccessful() ,$response->getData());

            if ($response->isRedirect()) {

                Session::put('input_data', $request->all());
                if ($response->redirect()) {
                    /** redirect to paypal **/
                    /** add payment ID to session **/
                    Session::put('input_data', $input);
                    Session::put('order_paymentorder_payment_id_id', $response->getId());
                    return redirect($response->redirect());
                }
            } else {
                return redirect()->back()->with('unsuccess', $response->getMessage());
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('unsuccess', $th->getMessage());
        }
    }

    public function notify(Request $request)
    {
        // ====================================================================
        // MERCHANT CHECKOUT: Get merchant-specific session data
        // ====================================================================
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];
        $isMerchantCheckout = $merchantData['is_merchant_checkout'];

        // Get steps from merchant sessions ONLY
        $steps = $this->getCheckoutSteps($merchantId, $isMerchantCheckout);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = Session::get('input_data');
        $input = array_merge($step1, $step2, $input);

        // Get cart and filter for merchant
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);

        $success_url = $this->getSuccessUrl($merchantId, $originalCart);
        $cancel_url = route('front.payment.cancle');
        


        $responseData = $request->all();

        if (empty($responseData['PayerID']) || empty($responseData['token'])) {
            return [
                'status' => false,
                'message' => __('Unknown error occurred'),
            ];
        }
        $purchaseRequest = $this->gateway->completePurchase(array(
            'payer_id' => $responseData['PayerID'],
            'transactionReference' => $responseData['paymentId'],
        ));
        $response = $purchaseRequest->send();

        if ($response->isSuccessful()) {

            PurchaseHelper::license_check($cart); // For License Checking

            // Serialize cart for purchase (using filtered merchant cart)
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
            $input['purchase_number'] = Str::random(4) . time();
            $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
            $input['payment_status'] = "Completed";

            // Get tax data from merchant step2 (already calculated and saved)
            $input['tax'] = $step2['tax_amount'] ?? 0;
            $input['tax_location'] = $step2['tax_location'] ?? '';

            $input['txnid'] = $response->getData()['wallet_logs'][0]['related_resources'][0]['sale']['id'];
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
                    PurchaseHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                    $input['affilate_user'] = Session::get('affilate');
                    $input['affilate_charge'] = $sub;
                }
            }

            $purchase->fill($input)->save();

            // Clear stock reservations after successful purchase
            StockReservation::clearAfterPurchase();

            $purchase->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
            $purchase->notifications()->create();

            if ($input['discount_code_id'] != "") {
                PurchaseHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
            }

            PurchaseHelper::size_qty_check($cart); // For Size Quantiy Checking
            PurchaseHelper::stock_check($cart); // For Stock Checking
            PurchaseHelper::merchant_purchase_check($cart, $purchase); // For Merchant Purchase Checking

            Session::put('temporder', $purchase);
            Session::put('tempcart', $cart);

            // ====================================================================
            // MERCHANT CHECKOUT: Remove only merchant's items from cart
            // ====================================================================
            $this->removeMerchantItemsFromCart($merchantId, $originalCart);

            if ($purchase->user_id != 0 && $purchase->wallet_price != 0) {
                PurchaseHelper::add_to_wallet_log($purchase, $purchase->wallet_price); // Store To Wallet Log
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
                'body' => "Hello Admin!<br>Your store has received a new purchase.<br>Purchase Number is " . $purchase->purchase_number . ".Please login to your panel to check. <br>Thank you.",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);

            return redirect($success_url);
        }
        return redirect($cancel_url);
    }
}
