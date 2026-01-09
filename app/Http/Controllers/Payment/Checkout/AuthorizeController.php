<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Purchase,
    Models\MerchantPayment,
    Classes\MuaadhMailer
};
use App\Helpers\PriceHelper;
use App\Models\Country;
use App\Models\Reward;
use App\Traits\HandlesMerchantCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Session;
use PurchaseHelper;
use Illuminate\Support\Str;

class AuthorizeController extends CheckoutBaseControlller
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
        $data = MerchantPayment::whereKeyword('authorize.net')->first();

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

        $item_name = $this->gs->title . " Purchase";
        $item_number = Str::random(4) . time();
        $item_amount = $total;

        // Get cart and filter for merchant
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $success_url = $this->getSuccessUrl($merchantId, $originalCart);

        // Validate Card Data

        $validator = \Validator::make($request->all(), [
            'cardNumber' => 'required',
            'cardCode' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        if ($validator->passes()) {
            $paydata = $data->convertAutoData();

            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($paydata['login_id']);
            $merchantAuthentication->setTransactionKey($paydata['txn_key']);

            // Set the transaction's refId
            $refId = 'ref' . time();

            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(str_replace(' ', '', $request->cardNumber));
            $year = $request->year;
            $month = $request->month;
            $creditCard->setExpirationDate($year . '-' . $month);
            $creditCard->setCardCode($request->cardCode);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create purchase information
            $purchaser = new AnetAPI\OrderType();
            $purchaser->setInvoiceNumber($item_number);
            $purchaser->setDescription($item_name);

            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($item_amount);
            $transactionRequestType->setOrder($purchaser);
            $transactionRequestType->setPayment($paymentOne);
            // Assemble the complete transaction request
            $requestt = new AnetAPI\CreateTransactionRequest();
            $requestt->setMerchantAuthentication($merchantAuthentication);
            $requestt->setRefId($refId);
            $requestt->setTransactionRequest($transactionRequestType);

            // Create the controller and get the response
            $controller = new AnetController\CreateTransactionController($requestt);
            if ($paydata['sandbox_check'] == 1) {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            } else {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }

            if ($response != null) {

                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {

                        // Filter cart for merchant
                        $cart = $this->filterCartForMerchant($originalCart, $merchantId);
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
                        $input['purchase_number'] = $item_number;
                        $input['wallet_price'] = $request->wallet_price / $this->curr->value;
                        $input['payment_status'] = "Completed";
                        // Get tax data from step2 (already calculated and saved)
                        $input['tax'] = $step2['tax_amount'] ?? 0;
                        $input['tax_location'] = $step2['tax_location'] ?? '';

                        $input['txnid'] = $tresponse->getTransId();
                        if (Session::has('affilate')) {
                            $val = $request->total / $this->curr->value;
                            $val = $val / 100;
                            $sub = $val * $this->gs->affilate_charge;
                            if ($temp_affilate_users != null) {
                                $t_sub = 0;
                                if (is_array($temp_affilate_users)) {
                                    foreach ($temp_affilate_users as $t_cost) {
                                        $t_sub += $t_cost['charge'];
                                    }
                                }
                                $sub = $sub - $t_sub;
                            }
                            if ($sub > 0) {
                                PurchaseHelper::affilate_check(Session::get('affilate'), $sub, 0); // For Affiliate Checking
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
                                    $smallest[$i->purchase_amount] = abs($i->purchase_amount - $num);
                                }

                                if(isset($smallest)){
                                    asort($smallest);
                              $final_reword = Reward::where('purchase_amount', key($smallest))->first();
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
                    } else {
                        return back()->with('unsuccess', __('Payment Failed.'));
                    }
                    // Or, print errors if the API request wasn't successful
                } else {
                    return back()->with('unsuccess', __('Payment Failed.'));
                }
            } else {
                return back()->with('unsuccess', __('Payment Failed.'));
            }
        }
        return back()->with('unsuccess', __('Invalid Payment Details.'));
    }
}
