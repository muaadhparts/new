<?php

namespace App\Http\Controllers;

use App\Classes\GeniusMailer;
use App\Helpers\OrderHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Payment\Checkout\CheckoutBaseControlller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Order;
use App\Models\Reward;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use MyFatoorah\Library\MyFatoorah;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentEmbedded;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use Exception;

class MyFatoorahController extends CheckoutBaseControlller {

    /**
     * @var array
     */
    public $mfConfig = [];

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initiate MyFatoorah Configuration
     */
    public function __construct() {
        parent::__construct();
        $this->mfConfig = [
            'apiKey'      => config('myfatoorah.api_key'),
            'isTest'      => config('myfatoorah.test_mode'),
            'countryCode' => config('myfatoorah.country_iso'),
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Redirect to MyFatoorah Invoice URL
     * Provide the index method with the order id and (payment method id or session id)
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|Response|\Illuminate\Routing\Redirector
     */
    public function index() {
//        dd($this->mfConfig);
        $cancel_url = route('front.checkout.step2');
        try {
            //For example: pmid=0 for MyFatoorah invoice or pmid=1 for Knet in test mode
            $paymentId = request('pmid') ?: 0;
            $sessionId = request('sid') ?: null;

//              $$orderId =  Order::max(id);
//            dd($this->mfConfig ,request()->all());
            $orderId  = request('oid') ?: 147;
            $orderId  = request('oid') ?: null;
            $curlData = $this->getPayLoadData();
//            dd($curlData );
            $mfObj   = new MyFatoorahPayment($this->mfConfig);
//            dd($mfObj ,$curlData);
            $payment = $mfObj->getInvoiceURL($curlData, $paymentId, $orderId, $sessionId);

//            dd($payment);
            return redirect($payment['invoiceURL']);
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return redirect($cancel_url)->with('unsuccess', __($exMessage));
//            return response()->json(['IsSuccess' => 'false', 'Message' => $exMessage]);
        }
    }

    public function notify(Request $request)
    {
//        dd($request->all());
        $success_url = route('front.payment.return');
        $cancel_url = route('front.checkout.step2');

        $input = Session::get('input_data');
        $step1 = Session::get('step1');
        $step2 = Session::get('step2');

        // dd($input ,$step1,$step2);
        $input = array_merge($step1, $step2);
//        $response =    $this->callback();

        try {
            $paymentId = request('paymentId');

            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data  = $mfObj->getPaymentStatus($paymentId, 'PaymentId');
            $message = $this->getTestMessage($data->InvoiceStatus, $data->InvoiceError);
            if($data->InvoiceStatus !== 'Paid'){
//                $response = ['IsSuccess' => true, 'Message' => $message, 'Data' => $data];
//                dd($cancel_url ,$data->InvoiceStatus ,'ss');
                return redirect($cancel_url)->with('unsuccess', __($message));
//                return  $response  = ['IsSuccess' => 'false', 'Message' => $exMessage];

            }
//
         } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return redirect($cancel_url)->with('unsuccess', __($exMessage));
//            $response  = ['IsSuccess' => 'false', 'Message' => $exMessage];
        }

//        dd($message , $data ,$data->InvoiceId  );
//        return response()->json($response);



        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        // OrderHelper::license_check($cart); // For License Checking
        $t_oldCart = Session::get('cart');
        $t_cart = new Cart($t_oldCart);
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);


        $orderCalculate = PriceHelper::getOrderTotal($input, $cart);
            $input['tax'] = data_get($orderCalculate, 'tax', 0);
//         dd($orderCalculate,'multi');
        if (isset($orderCalculate['success']) && $orderCalculate['success'] == false) {
            return redirect()->back()->with('unsuccess', $orderCalculate['message']);
        }

        if ($this->gs->multiple_shipping == 0) {
            $orderTotal = $orderCalculate['total_amount'];
            $shipping = $orderCalculate['shipping'];
            $packeing = $orderCalculate['packeing'];
            $is_shipping = $orderCalculate['is_shipping'];
            $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
            $vendor_packing_ids = $orderCalculate['vendor_packing_ids'];
            $vendor_ids = $orderCalculate['vendor_ids'];

            $input['shipping_title'] = @$shipping->title;
            $input['vendor_shipping_id'] = @$shipping->id;
            $input['packing_title'] = @$packeing->title;
            $input['vendor_packing_id'] = @$packeing->id;
            $input['shipping_cost'] = @$packeing->price ?? 0;
            $input['packing_cost'] = @$packeing->price ?? 0;
            $input['is_shipping'] = $is_shipping;
            $input['vendor_shipping_ids'] = $vendor_shipping_ids;
            $input['vendor_packing_ids'] = $vendor_packing_ids;
            $input['vendor_ids'] = $vendor_ids;
        } else {


            // multi shipping

            $orderTotal = $orderCalculate['total_amount'];
            $shipping = $orderCalculate['shipping'];
            $packeing = $orderCalculate['packeing'];
            $is_shipping = $orderCalculate['is_shipping'];
            $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
            $vendor_packing_ids = $orderCalculate['vendor_packing_ids'];
            $vendor_ids = $orderCalculate['vendor_ids'];
            $shipping_cost = $orderCalculate['shipping_cost'];
            $packing_cost = $orderCalculate['packing_cost'];

            $input['shipping_title'] = $vendor_shipping_ids;
            $input['vendor_shipping_id'] = $vendor_shipping_ids;
            $input['packing_title'] = $vendor_packing_ids;
            $input['vendor_packing_id'] = $vendor_packing_ids;
            $input['shipping_cost'] = $shipping_cost;
            $input['packing_cost'] = $packing_cost;
            $input['is_shipping'] = $is_shipping;
            $input['vendor_shipping_ids'] = $vendor_shipping_ids;
            $input['vendor_packing_ids'] = $vendor_packing_ids;
            $input['vendor_ids'] = $vendor_ids;
            unset($input['shipping']);
            unset($input['packeging']);
        }


        $order = new Order;
        $input['cart'] = $new_cart;
        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $orderTotal;
        $input['order_number'] = Str::random(4) . time();
        $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
        $input['payment_status'] = "Completed";
        if ($input['tax_type'] == 'state_tax') {
            $input['tax_location'] = State::findOrFail($input['tax'])->state;
        } else {
            $input['tax_location'] = Country::findOrFail($input['tax'])->country_name;
        }
        $input['tax'] = Session::get('current_tax');

//        $input['txnid'] = $response->getData()['transactions'][0]['related_resources'][0]['sale']['id'];
        $input['txnid'] = $data->InvoiceId;

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
                OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $order->fill($input)->save();
        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();

        if ($input['coupon_id'] != "") {
            OrderHelper::coupon_check($input['coupon_id']); // For Coupon Checking
        }

        OrderHelper::size_qty_check($cart); // For Size Quantiy Checking
        OrderHelper::stock_check($cart); // For Stock Checking
        OrderHelper::vendor_order_check($cart, $order); // For Vendor Order Checking

        Session::put('temporder', $order);
        Session::put('tempcart', $cart);
        Session::forget('cart');
        Session::forget('already');
        Session::forget('coupon');
        Session::forget('coupon_total');
        Session::forget('coupon_total1');
        Session::forget('coupon_percentage');

        if ($order->user_id != 0 && $order->wallet_price != 0) {
            OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
        }

        if (Auth::check()) {
            if ($this->gs->is_reward == 1) {
                $num = $order->pay_amount;
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
            'to' => $order->customer_email,
            'type' => "new_order",
            'cname' => $order->customer_name,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
            'onumber' => $order->order_number,
        ];
        $mailer = new GeniusMailer();
        $mailer->sendAutoOrderMail($data, $order->id);

        //Sending Email To Admin
        $data = [
            'to' => $this->ps->contact_email,
            'subject' => "New Order Recieved!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
        ];
        $mailer = new GeniusMailer();
        $mailer->sendCustomMail($data);

        return redirect($success_url);


     }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how to map order data to MyFatoorah
     * You can get the data using the order object in your system
     * 
     * @param int|string $orderId
     * 
     * @return array
     */
    private function getPayLoadData($orderId = null) {
//        $callbackURL = route('myfatoorah.callback');
        $callbackURL = route('front.myfatoorah.notify');

//        $cancel_url = route('front.payment.cancle');
//        $notify_url = route('front.paypal.notify');


        //You can get the data using the order object in your system
        $order = $this->getTestOrderData($orderId);
//            dd($order);
        return [
            "NotificationOption" => "ALL",
            'CustomerName'       =>  $order['customer_name'],
            'InvoiceValue'       => $order['total'],
            'DisplayCurrencyIso' => $order['currency_name'] ?? 'SAR',
            'CustomerEmail'      =>  $order['customer_email'],
            'CallBackUrl'        => $callbackURL,
            'ErrorUrl'           => $callbackURL,
            'MobileCountryCode'  => '+966',
            'CustomerMobile'     =>   $order['customer_phone'],
            'Language'           => 'en',
            'CustomerReference'  => $orderId,
            'SourceInfo'         => 'Laravel ' . app()::VERSION . ' - MyFatoorah Package ' . MYFATOORAH_LARAVEL_PACKAGE_VERSION
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get MyFatoorah Payment Information
     * Provide the callback method with the paymentId
     * 
     * @return Response
     */
    public function callback() {
        try {
            $paymentId = request('paymentId');

            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data  = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

//            dd($data);
            $message = $this->getTestMessage($data->InvoiceStatus, $data->InvoiceError);

            $response = ['IsSuccess' => true, 'Message' => $message, 'Data' => $data];
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            $response  = ['IsSuccess' => 'false', 'Message' => $exMessage];
        }

        return response()->json($response);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how to Display the enabled gateways at your MyFatoorah account to be displayed on the checkout page
     * Provide the checkout method with the order id to display its total amount and currency
     * 
     * @return View
     */
    public function checkout() {
        try {
            //You can get the data using the order object in your system
            $orderId = request('oid') ?: 147;
            $order   = $this->getTestOrderData($orderId);

            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');

            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($order['total'], $order['currency'], config('myfatoorah.register_apple_pay'));

            if (empty($paymentMethods['all'])) {
                throw new Exception('noPaymentGateways');
            }

            //Generate MyFatoorah session for embedded payment
            $mfSession = $mfObj->getEmbeddedSession($userDefinedField);

            //Get Environment url
            $isTest = $this->mfConfig['isTest'];
            $vcCode = $this->mfConfig['countryCode'];

            $countries = MyFatoorah::getMFCountries();
            $jsDomain  = ($isTest) ? $countries[$vcCode]['testPortal'] : $countries[$vcCode]['portal'];

            return view('myfatoorah.checkout', compact('mfSession', 'paymentMethods', 'jsDomain', 'userDefinedField'));
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return view('myfatoorah.error', compact('exMessage'));
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how the webhook is working when MyFatoorah try to notify your system about any transaction status update
     */
    public function webhook(Request $request) {
        try {
            //Validate webhook_secret_key
            $secretKey = config('myfatoorah.webhook_secret_key');
            if (empty($secretKey)) {
                return response(null, 404);
            }

            //Validate MyFatoorah-Signature
            $mfSignature = $request->header('MyFatoorah-Signature');
            if (empty($mfSignature)) {
                return response(null, 404);
            }

            //Validate input
            $body  = $request->getContent();
            $input = json_decode($body, true);
            if (empty($input['Data']) || empty($input['EventType']) || $input['EventType'] != 1) {
                return response(null, 404);
            }

            //Validate Signature
            if (!MyFatoorah::isSignatureValid($input['Data'], $secretKey, $mfSignature, $input['EventType'])) {
                return response(null, 404);
            }

            //Update Transaction status on your system
            $result = $this->changeTransactionStatus($input['Data']);

            return response()->json($result);
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return response()->json(['IsSuccess' => false, 'Message' => $exMessage]);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    private function changeTransactionStatus($inputData) {
        //1. Check if orderId is valid on your system.
        $orderId = $inputData['CustomerReference'];

        //2. Get MyFatoorah invoice id
        $invoiceId = $inputData['InvoiceId'];

        //3. Check order status at MyFatoorah side
        if ($inputData['TransactionStatus'] == 'SUCCESS') {
            $status = 'Paid';
            $error  = '';
        } else {
            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data  = $mfObj->getPaymentStatus($invoiceId, 'InvoiceId');

            $status = $data->InvoiceStatus;
            $error  = $data->InvoiceError;
        }

        $message = $this->getTestMessage($status, $error);

        //4. Update order transaction status on your system
        return ['IsSuccess' => true, 'Message' => $message, 'Data' => $inputData];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    private function getTestOrderData($orderId) {
        $input = Session::get('input_data');
        $step1 = Session::get('step1');
        $step2 = Session::get('step2');
        $input = ['currency' => 'SAR'];
        $input = array_merge($step1, $step2, $input);

//        dd($input);
        return  $input;
//        dd($input);
        return [
            'total'    => 1,
            'currency' => 'SAR'
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    private function getTestMessage($status, $error) {
        if ($status == 'Paid') {
            return 'Invoice is paid.';
        } else if ($status == 'Failed') {
            return 'Invoice is not paid due to ' . $error;
        } else if ($status == 'Expired') {
            return $error;
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
