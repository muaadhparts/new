<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\MuaadhMailer;
use App\Helpers\OrderHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Reward;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mollie\Laravel\Facades\Mollie;
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



    public function store(Request $request)
    {

        dd($request->all());
        $input = array_merge(
            Session::get('step1', []),
            Session::get('step2', []),
            $request->all()
        );

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $cart = new Cart(Session::get('cart'));
        OrderHelper::license_check($cart);

        $cartData = [
            'totalQty' => $cart->totalQty,
            'totalPrice' => $cart->totalPrice,
            'items' => $cart->items,
        ];
        $input['cart'] = json_encode($cartData);

        $affilateUsers = OrderHelper::product_affilate_check($cart);
        $input['affilate_users'] = $affilateUsers ? json_encode($affilateUsers) : null;

        // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $orderTotal = $prepared['order_total'];

        $input['pay_amount'] = $orderTotal;
        $input['order_number'] = Str::random(4) . time();

        // Get tax data from step2 (already calculated and saved)
        $step2_session = Session::get('step2');
        $input['tax'] = $step2_session['tax_amount'] ?? 0;
        $input['tax_location'] = $step2_session['tax_location'] ?? '';
        $input['user_id'] = Auth::id();


        // Create Order
        $order = new Order();
//        $order->fill($input);

//       return redirect(url('myfatoorah/checkout'));
//        dd($input);
        $order->fill($input)->save();

        // Order Tracks and Notifications
        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();

        // Coupon validation
        if (!empty($input['coupon_id'])) {
            OrderHelper::coupon_check($input['coupon_id']);
        }

        // Rewards for authenticated user
        if (Auth::check() && $this->gs->is_reward) {
            $this->applyRewards($order);
        }

        // Update Order Details
        OrderHelper::size_qty_check($cart);
        OrderHelper::stock_check($cart);
        OrderHelper::vendor_order_check($cart, $order);

        // Clear Session and Prepare for Next Order
        $this->clearOrderSession($order, $cart);

        // Send Emails
        $this->sendOrderEmails($order);
//        $this->checkout($order);
        try {

//        dd($order);
            //You can get the data using the order object in your system
            $orderId = $order->order_number ?: 147;
            $order2   = $this->getOrderData($order);
//            dd($order ,$order2);
            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');
//            dd($order ,'checkout');
            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($order2['total'], $order2['currency'], config('myfatoorah.register_apple_pay'));

//            dd($paymentMethods ,$order2);
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
            dd($ex ,$ex->getMessage());
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return view('myfatoorah.error', compact('exMessage'));
        }
//        return redirect(url('myfatoorah/checkout'));
//        return redirect()->url('front.payment.return');
//        return redirect()->route('front.payment.return');
    }

    private function applyRewards(Order $order)
    {
        $num = $order->pay_amount;
        $rewards = Reward::all();
        $closestReward = $rewards->sortBy(fn($reward) => abs($reward->order_amount - $num))->first();

        if ($closestReward) {
            Auth::user()->increment('reward', $closestReward->reward);
        }
    }

    private function clearOrderSession(Order $order, Cart $cart)
    {
        Session::put('temporder', $order);
        Session::put('tempcart', $cart);
        Session::forget(['cart', 'already', 'coupon', 'coupon_total', 'coupon_total1', 'coupon_percentage']);

        if ($order->user_id && $order->wallet_price) {
            OrderHelper::add_to_transaction($order, $order->wallet_price);
        }
    }

    private function sendOrderEmails(Order $order)
    {
        // Email to Customer
        $customerData = [
            'to' => $order->customer_email,
            'type' => "new_order",
            'cname' => $order->customer_name,
            'onumber' => $order->order_number,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
        ];


        (new MuaadhMailer())->sendAutoOrderMail($customerData, $order->id);

        // Email to Admin
        $adminData = [
            'to' => $this->ps->contact_email,
            'subject' => "New Order Received!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is {$order->order_number}. Please login to your panel to check.<br>Thank you.",
        ];
        (new MuaadhMailer())->sendCustomMail($adminData);
    }


    /**
     * Redirect to MyFatoorah Invoice URL
     * Provide the index method with the order id and (payment method id or session id)
     *
     * @return Response
     */
    public function index() {

        
        try {
            //For example: pmid=0 for MyFatoorah invoice or pmid=1 for Knet in test mode
            $paymentId = request('pmid') ?: 0;
            $sessionId = request('sid') ?: null;

            dd($paymentId, $sessionId);
            $orderId  = request('oid') ?: 147;
            $curlData = $this->getPayLoadData($orderId);

            dd($curlData ,request('oid'));
            $mfObj   = new MyFatoorahPayment($this->mfConfig);
            $payment = $mfObj->getInvoiceURL($curlData, $paymentId, $orderId, $sessionId);

            return redirect($payment['invoiceURL']);
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return response()->json(['IsSuccess' => 'false', 'Message' => $exMessage]);
        }
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
        $callbackURL = route('myfatoorah.callback');

        //You can get the data using the order object in your system
        $order = $this->getTestOrderData($orderId);

        return [
            'CustomerName'       => 'FName LName',
            'InvoiceValue'       => $order['total'],
            'DisplayCurrencyIso' => $order['currency'],
            'CustomerEmail'      => 'test@test.com',
            'CallBackUrl'        => $callbackURL,
            'ErrorUrl'           => $callbackURL,
            'MobileCountryCode'  => '+966',
            'CustomerMobile'     => '12345678',
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
    public function checkout($order) {
        try {

//        dd($order);
            //You can get the data using the order object in your system
            $orderId = $order->order_number ?: 147;
            $order2   = $this->getOrderData($order);
//            dd($order ,$order2);
            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');
//            dd($order ,'checkout');
            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($order2['total'], $order2['currency'], config('myfatoorah.register_apple_pay'));

//            dd($paymentMethods ,$order2);
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
            dd($ex ,$ex->getMessage());
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
        return [
            'total'    => 1,
            'currency' => 'SAR'
        ];
    }

    private function getOrderData($order) {
//        dd($order ,'getOrderData');
        return [
            "CustomerName"  => $order->customer_name,
        "NotificationOption" =>  "ALL",
          "Language" =>  "ar",
        "DisplayCurrencyIso" =>  "SAR",
            "MobileCountryCode" =>  "966",
         "CustomerMobile" =>  "506552294",
        "total" =>  $order->pay_amount,
//
//            'total'    => 1,
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
