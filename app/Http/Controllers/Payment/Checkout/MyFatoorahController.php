<?php

/**
 * ====================================================================
 * MYFATOORAH PAYMENT CONTROLLER - VENDOR CHECKOUT ONLY
 * ====================================================================
 * Modified: 2025-01-19 for Vendor Checkout System
 * - Uses HandlesVendorCheckout trait
 * - Reads from vendor_step1/step2 ONLY
 * ====================================================================
 */

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
use App\Models\StockReservation;
use App\Traits\HandlesVendorCheckout;
use App\Traits\SavesCustomerShippingChoice;
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
use App\Services\ApiCredentialService;
use App\Services\VendorCredentialService;
use Exception;

class MyFatoorahController extends CheckoutBaseControlller {
    use HandlesVendorCheckout, SavesCustomerShippingChoice;

    /**
     * @var array
     */
    public $mfConfig = [];

    protected ApiCredentialService $credentialService;
    protected VendorCredentialService $vendorCredentialService;

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initiate MyFatoorah Configuration
     * Note: mfConfig is set dynamically per-vendor in store() method
     */
    public function __construct(
        ApiCredentialService $credentialService,
        VendorCredentialService $vendorCredentialService
    ) {
        parent::__construct();
        $this->credentialService = $credentialService;
        $this->vendorCredentialService = $vendorCredentialService;

        // Default config - will be overridden per-vendor in store()
        $this->mfConfig = [
            'apiKey'      => $this->credentialService->getMyFatoorahKey(),
            'isTest'      => config('myfatoorah.test_mode'),
            'countryCode' => config('myfatoorah.country_iso'),
        ];
    }

    /**
     * Get MyFatoorah config for specific vendor
     *
     * MARKETPLACE POLICY:
     * - Vendor MUST have their own payment credentials
     * - NO FALLBACK to system credentials for payment
     * - Each vendor is financially responsible for their own transactions
     * - Uses vendor's own test_mode and country settings
     *
     * @param int $vendorId
     * @return array
     * @throws \Exception If vendor doesn't have payment credentials configured
     */
    protected function getVendorMfConfig(int $vendorId): array
    {
        // Get vendor-specific API key - NO FALLBACK
        $apiKey = $this->vendorCredentialService->getMyFatoorahKeyStrict($vendorId);

        if (empty($apiKey)) {
            throw new \Exception(
                "MyFatoorah payment credentials not configured for vendor #{$vendorId}. " .
                "Each vendor must have their own payment credentials. " .
                "Configure via Admin Panel > Vendor Credentials or Vendor Dashboard."
            );
        }

        // Get vendor's gateway settings (test_mode, country)
        $gateway = \DB::table('payment_gateways')
            ->where('user_id', $vendorId)
            ->where('keyword', 'myfatoorah')
            ->first();

        $gatewayInfo = $gateway ? json_decode($gateway->information, true) : [];

        // Use vendor settings, fallback to system config
        $isTest = isset($gatewayInfo['sandbox_check']) ? (bool)$gatewayInfo['sandbox_check'] : config('myfatoorah.test_mode');
        $countryCode = $gatewayInfo['country'] ?? config('myfatoorah.country_iso');

        return [
            'apiKey'      => $apiKey,
            'isTest'      => $isTest,
            'countryCode' => $countryCode,
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------



    public function store(Request $request)
    {
        // Get vendor checkout data
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];

        // Set vendor-specific MyFatoorah config
        if ($vendorId) {
            try {
                $this->mfConfig = $this->getVendorMfConfig($vendorId);
            } catch (\Exception $e) {
                \Log::error('MyFatoorah store: Vendor credentials not configured', [
                    'vendor_id' => $vendorId,
                    'error' => $e->getMessage()
                ]);
                return redirect()->route('front.cart')->with('unsuccess', __('عذراً، لم يتم إعداد بوابة الدفع لهذا التاجر بعد. يرجى التواصل مع التاجر.'));
            }
        }

        // Get steps from vendor sessions ONLY
        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = array_merge($step1, $step2, $request->all());

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForVendor($originalCart, $vendorId);
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

        // Get tax data from vendor step2 (already fetched at method start)
        $input['tax'] = $step2['tax_amount'] ?? 0;
        $input['tax_location'] = $step2['tax_location'] ?? '';
        $input['user_id'] = Auth::id();


        // Create Order
        $order = new Order();
//        $order->fill($input);

//       return redirect(url('myfatoorah/checkout'));
//        dd($input);
        $order->fill($input)->save();

        // Clear stock reservations after successful order (stock already sold)
        StockReservation::clearAfterPurchase();

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
        $this->clearOrderSession($order, $cart, $vendorId, $originalCart);

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

    private function clearOrderSession(Order $order, Cart $cart, $vendorId, $originalCart)
    {
        Session::put('temporder', $order);
        Session::put('tempcart', $cart);

        // Remove only vendor's products from cart
        $this->removeVendorProductsFromCart($vendorId, $originalCart);

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

            $orderId  = request('oid') ?: 147;
            $curlData = $this->getPayLoadData($orderId);

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
