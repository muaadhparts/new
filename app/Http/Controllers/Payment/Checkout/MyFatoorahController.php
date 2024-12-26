<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\GeniusMailer;
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


//    public function store(Request $request)
//    {
//        $input = $request->all();
//        $step1 = Session::get('step1');
//        $step2 = Session::get('step2');
//        $input = array_merge($step1, $step2, $input);
//
////
//
////        dd($input );
//
//        if (!Session::has('cart')) {
//            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
//        }
//
//        $oldCart = Session::get('cart');
//        $cart = new Cart($oldCart);
//
//        OrderHelper::license_check($cart); // For License Checking
//        $t_oldCart = Session::get('cart');
//        $t_cart = new Cart($t_oldCart);
//        $new_cart = [];
//        $new_cart['totalQty'] = $t_cart->totalQty;
//        $new_cart['totalPrice'] = $t_cart->totalPrice;
//        $new_cart['items'] = $t_cart->items;
//        $new_cart = json_encode($new_cart);
//        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
//        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);
//
//
//        $orderCalculate = PriceHelper::getOrderTotal($input, $cart);
////        dd($input,$orderCalculate,'multi');
//
//
////         dd($input,$orderCalculate,'multi');
//        if (isset($orderCalculate['success']) && $orderCalculate['success'] == false) {
//            return redirect()->back()->with('unsuccess', $orderCalculate['message']);
//        }
//
//
//
//            // multi shipping
//
//            $orderTotal = $orderCalculate['total_amount'];
//            $shipping = $orderCalculate['shipping'];
//            $packeing = $orderCalculate['packeing'];
//            $is_shipping = $orderCalculate['is_shipping'];
//            $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
//            $vendor_packing_ids = $orderCalculate['vendor_packing_ids'];
//            $vendor_ids = $orderCalculate['vendor_ids'];
//            $shipping_cost = $orderCalculate['shipping_cost'];
//            $packing_cost = $orderCalculate['packing_cost'];
//
//            $input['shipping_title'] = $vendor_shipping_ids;
//            $input['vendor_shipping_id'] = $vendor_shipping_ids;
//            $input['packing_title'] = $vendor_packing_ids;
//            $input['vendor_packing_id'] = $vendor_packing_ids;
//            $input['shipping_cost'] = $shipping_cost;
//            $input['packing_cost'] = $packing_cost;
//            $input['is_shipping'] = $is_shipping;
//            $input['vendor_shipping_ids'] = $vendor_shipping_ids;
//            $input['vendor_packing_ids'] = $vendor_packing_ids;
//            $input['vendor_ids'] = $vendor_ids;
//            unset($input['shipping']);
//            unset($input['packeging']);
////        }
//
//
//
//        $order = new Order;
//        $success_url = route('front.payment.return');
//        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
//        $input['cart'] = $new_cart;
//        $input['affilate_users'] = $affilate_users;
//        $input['pay_amount'] = $orderTotal;
//        $input['order_number'] = Str::random(4) . time();
//        $input['wallet_price'] = $request->wallet_price / $this->curr->value;
//
//        if ($input['tax_type'] == 'state_tax') {
//            $input['tax_location'] = State::findOrFail($input['tax'])->state;
//        } else {
//            $input['tax_location'] = Country::findOrFail($input['tax'])->country_name;
//        }
//        $input['tax'] = Session::get('current_tax');
//
//
//
////        dd($input);
//        $order->fill($input)->save();
//        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
//        $order->notifications()->create();
//
//        if ($input['coupon_id'] != "") {
//            OrderHelper::coupon_check($input['coupon_id']); // For Coupon Checking
//        }
//
//        if (Auth::check()) {
//            if ($this->gs->is_reward == 1) {
//                $num = $order->pay_amount;
//                $rewards = Reward::get();
//                foreach ($rewards as $i) {
//                    $smallest[$i->order_amount] = abs($i->order_amount - $num);
//                }
//
//                if (isset($smallest)) {
//                    asort($smallest);
//                    $final_reword = Reward::where('order_amount', key($smallest))->first();
//                    Auth::user()->update(['reward' => (Auth::user()->reward + $final_reword->reward)]);
//                }
//            }
//        }
//
//        OrderHelper::size_qty_check($cart); // For Size Quantiy Checking
//        OrderHelper::stock_check($cart); // For Stock Checking
//        OrderHelper::vendor_order_check($cart, $order); // For Vendor Order Checking
//
//        Session::put('temporder', $order);
//        Session::put('tempcart', $cart);
//        Session::forget('cart');
//        Session::forget('already');
//        Session::forget('coupon');
//        Session::forget('coupon_total');
//        Session::forget('coupon_total1');
//        Session::forget('coupon_percentage');
//
//        if ($order->user_id != 0 && $order->wallet_price != 0) {
//            OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
//        }
//
//        //Sending Email To Buyer
//        $data = [
//            'to' => $order->customer_email,
//            'type' => "new_order",
//            'cname' => $order->customer_name,
//            'oamount' => "",
//            'aname' => "",
//            'aemail' => "",
//            'wtitle' => "",
//            'onumber' => $order->order_number,
//        ];
//
//        $mailer = new GeniusMailer();
//        $mailer->sendAutoOrderMail($data, $order->id);
//
//        //Sending Email To Admin
//        $data = [
//            'to' => $this->ps->contact_email,
//            'subject' => "New Order Recieved!!",
//            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
//        ];
//        $mailer = new GeniusMailer();
//        $mailer->sendCustomMail($data);
//
//        return redirect($success_url);
//    }

    public function store(Request $request)
    {
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

        $orderCalculation = PriceHelper::getOrderTotal($input, $cart);
        if (isset($orderCalculation['success']) && !$orderCalculation['success']) {
            return redirect()->back()->with('unsuccess', $orderCalculation['message']);
        }

        // Merge shipping and packaging details into input
        $input = array_merge($input, [
            'shipping_title' => $orderCalculation['vendor_shipping_ids'],
            'vendor_shipping_id' => $orderCalculation['vendor_shipping_ids'],
            'packing_title' => $orderCalculation['vendor_packing_ids'],
            'vendor_packing_id' => $orderCalculation['vendor_packing_ids'],
            'shipping_cost' => $orderCalculation['shipping_cost'],
            'packing_cost' => $orderCalculation['packing_cost'],
            'is_shipping' => $orderCalculation['is_shipping'],
            'vendor_ids' => $orderCalculation['vendor_ids'],
            'pay_amount' => $orderCalculation['total_amount'],
            'order_number' => Str::random(4) . time(),
        ]);

        unset($input['shipping'], $input['packeging']);

        // Handle tax location
        if ($input['tax_type'] === 'state_tax') {
            $input['tax_location'] = State::findOrFail($input['tax'])->state;
        } else {
            $input['tax_location'] = Country::findOrFail($input['tax'])->country_name;
        }
        $input['tax'] = Session::get('current_tax');
        $input['user_id'] = Auth::id();

        // Create Order
        $order = new Order();
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

        return redirect()->route('front.payment.return');
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


        (new GeniusMailer())->sendAutoOrderMail($customerData, $order->id);

        // Email to Admin
        $adminData = [
            'to' => $this->ps->contact_email,
            'subject' => "New Order Received!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is {$order->order_number}. Please login to your panel to check.<br>Thank you.",
        ];
        (new GeniusMailer())->sendCustomMail($adminData);
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
            'MobileCountryCode'  => '+965',
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
        return [
            'total'    => 15,
            'currency' => 'KWD'
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
