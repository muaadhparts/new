<?php

/**
 * ====================================================================
 * MYFATOORAH PAYMENT CONTROLLER - MERCHANT CHECKOUT ONLY
 * ====================================================================
 * Modified: 2025-01-19 for Merchant Checkout System
 * - Uses HandlesMerchantCheckout trait
 * - Reads from merchant_step1/step2 ONLY
 * ====================================================================
 */

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\MuaadhMailer;
use App\Helpers\PurchaseHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Purchase;
use App\Models\MerchantPayment;
use App\Models\Reward;
use App\Models\StockReservation;
use App\Traits\HandlesMerchantCheckout;
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
use App\Services\MerchantCredentialService;
use Exception;

class MyFatoorahController extends CheckoutBaseControlller {
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;

    /**
     * @var array
     */
    public $mfConfig = [];

    protected MerchantCredentialService $merchantCredentialService;

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initiate MyFatoorah Configuration
     * Note: mfConfig is set dynamically per-merchant in store() method
     *
     * MARKETPLACE POLICY: No default system credentials.
     * Each merchant MUST have their own MyFatoorah credentials.
     */
    public function __construct(
        MerchantCredentialService $merchantCredentialService
    ) {
        parent::__construct();
        $this->merchantCredentialService = $merchantCredentialService;

        // No default config - MUST use getMerchantMfConfig() per merchant
        $this->mfConfig = [];
    }

    /**
     * Get MyFatoorah config for specific merchant
     *
     * MARKETPLACE POLICY:
     * - Merchant MUST have their own payment credentials
     * - NO FALLBACK to system credentials for payment
     * - Each merchant is financially responsible for their own transactions
     * - Uses merchant's own test_mode and country settings
     *
     * @param int $merchantId
     * @return array
     * @throws \Exception If merchant doesn't have payment credentials configured
     */
    protected function getMerchantMfConfig(int $merchantId): array
    {
        // Get merchant-specific API key - NO FALLBACK
        $apiKey = $this->merchantCredentialService->getMyFatoorahKeyStrict($merchantId);

        if (empty($apiKey)) {
            throw new \Exception(
                "MyFatoorah payment credentials not configured for merchant #{$merchantId}. " .
                "Each merchant must have their own payment credentials. " .
                "Configure via Operator Panel > Merchant Credentials or Merchant Dashboard."
            );
        }

        // Get merchant's gateway settings (test_mode, country)
        $gateway = \DB::table('merchant_payments')
            ->where('user_id', $merchantId)
            ->where('keyword', 'myfatoorah')
            ->first();

        $gatewayInfo = $gateway ? json_decode($gateway->information, true) : [];

        // Use merchant settings, fallback to system config
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
        // ====================================================================
        // MERCHANT ID RESOLUTION (Priority: POST > Session)
        // FIX: Read merchant_id from form POST first, then fall back to session
        // This prevents merchant_id loss when session expires or user navigates
        // ====================================================================
        $merchantIdFromPost = $request->input('checkout_merchant_id');
        $isMerchantCheckoutFromPost = $request->input('is_merchant_checkout') === '1';

        // Get from session as fallback
        $merchantData = $this->getMerchantCheckoutData();
        $merchantIdFromSession = $merchantData['merchant_id'];

        // Use POST value first, then session
        $merchantId = !empty($merchantIdFromPost) ? (int)$merchantIdFromPost : $merchantIdFromSession;
        $isMerchantCheckout = $isMerchantCheckoutFromPost || $merchantData['is_merchant_checkout'];

        // If we got merchant_id from POST but not in session, restore it to session for later use
        if ($merchantId && !$merchantIdFromSession) {
            Session::put('checkout_merchant_id', $merchantId);
            \Log::info('MyFatoorah store: Restored merchant_id to session from POST', [
                'merchant_id' => $merchantId
            ]);
        }

        // POLICY: Merchant ID is REQUIRED for all payment operations
        // MyFatoorah credentials are ONLY in merchant_credentials table
        if (!$merchantId) {
            \Log::error('MyFatoorah store: No merchant_id found in POST or session', [
                'user_id' => \Auth::id(),
                'post_merchant_id' => $merchantIdFromPost,
                'session_merchant_id' => $merchantIdFromSession,
                'session_data' => $merchantData
            ]);
            return redirect()->route('front.cart')->with('unsuccess', __('خطأ في جلسة الدفع. يرجى إعادة المحاولة من السلة.'));
        }

        // Get merchant-specific MyFatoorah config - NO FALLBACK
        try {
            $this->mfConfig = $this->getMerchantMfConfig($merchantId);
        } catch (\Exception $e) {
            \Log::error('MyFatoorah store: Merchant credentials not configured', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('front.cart')->with('unsuccess', __('عذراً، لم يتم إعداد بوابة الدفع لهذا التاجر بعد. يرجى التواصل مع التاجر.'));
        }

        // Get steps from merchant sessions ONLY
        $steps = $this->getCheckoutSteps($merchantId, $isMerchantCheckout);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = array_merge($step1, $step2, $request->all());

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);
        PurchaseHelper::license_check($cart);

        $cartData = [
            'totalQty' => $cart->totalQty,
            'totalPrice' => $cart->totalPrice,
            'items' => $cart->items,
        ];
        $input['cart'] = json_encode($cartData);

        $affilateUsers = PurchaseHelper::item_affilate_check($cart);
        $input['affilate_users'] = $affilateUsers ? json_encode($affilateUsers) : null;

        // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $purchaseTotal = $prepared['order_total'];

        $input['pay_amount'] = $purchaseTotal;
        $input['purchase_number'] = Str::random(4) . time();

        // Get tax data from merchant step2 (already fetched at method start)
        $input['tax'] = $step2['tax_amount'] ?? 0;
        $input['tax_location'] = $step2['tax_location'] ?? '';
        $input['user_id'] = Auth::id();


        // Create Purchase
        $purchase = new Purchase();
//        $purchase->fill($input);

//       return redirect(url('myfatoorah/checkout'));
//        dd($input);
        $purchase->fill($input)->save();

        // Clear stock reservations after successful purchase (stock already sold)
        StockReservation::clearAfterPurchase();

        // Create DeliveryCourier record if using local courier or pickup
        if ($merchantId) {
            $this->createDeliveryCourier($purchase, $merchantId, $step2, 'online');
        }

        // Purchase Tracks and Notifications
        $purchase->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
        $purchase->notifications()->create();

        // Discount Code validation
        if (!empty($input['discount_code_id'])) {
            PurchaseHelper::discount_code_check($input['discount_code_id']);
        }

        // Rewards for authenticated user
        if (Auth::check() && $this->gs->is_reward) {
            $this->applyRewards($purchase);
        }

        // Update Purchase Details
        PurchaseHelper::size_qty_check($cart);
        PurchaseHelper::stock_check($cart);
        PurchaseHelper::merchant_purchase_check($cart, $purchase);

        // Clear Session and Prepare for Next Purchase
        $this->clearPurchaseSession($purchase, $cart, $merchantId, $originalCart);

        // Send Emails
        $this->sendPurchaseEmails($purchase);
//        $this->checkout($purchase);
        try {

//        dd($purchase);
            //You can get the data using the purchase object in your system
            $purchaseId = $purchase->purchase_number ?: 147;
            $purchase2   = $this->getOrderData($purchase);
//            dd($purchase ,$purchase2);
            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');
//            dd($purchase ,'checkout');
            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($purchase2['total'], $purchase2['currency'], config('myfatoorah.register_apple_pay'));

//            dd($paymentMethods ,$purchase2);
            if (empty($paymentMethods['all'])) {
                throw new Exception('noMerchantPayments');
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

    private function applyRewards(Purchase $purchase)
    {
        $num = $purchase->pay_amount;
        $rewards = Reward::all();
        $closestReward = $rewards->sortBy(fn($reward) => abs($reward->order_amount - $num))->first();

        if ($closestReward) {
            Auth::user()->increment('reward', $closestReward->reward);
        }
    }

    private function clearPurchaseSession(Purchase $purchase, Cart $cart, $merchantId, $originalCart)
    {
        Session::put('temporder', $purchase);
        Session::put('tempcart', $cart);

        // Remove only merchant's items from cart
        $this->removeMerchantItemsFromCart($merchantId, $originalCart);

        if ($purchase->user_id && $purchase->wallet_price) {
            PurchaseHelper::add_to_wallet_log($purchase, $purchase->wallet_price); // Store To Wallet Log
        }
    }

    private function sendPurchaseEmails(Purchase $purchase)
    {
        // Email to Customer
        $customerData = [
            'to' => $purchase->customer_email,
            'type' => "new_order",
            'cname' => $purchase->customer_name,
            'onumber' => $purchase->purchase_number,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
        ];


        (new MuaadhMailer())->sendAutoPurchaseMail($customerData, $purchase->id);

        // Email to Admin
        $adminData = [
            'to' => $this->ps->contact_email,
            'subject' => "New Purchase Received!!",
            'body' => "Hello Operator!<br>Your store has received a new purchase.<br>Purchase Number is {$purchase->purchase_number}. Please login to your panel to check.<br>Thank you.",
        ];
        (new MuaadhMailer())->sendCustomMail($adminData);
    }


    /**
     * Redirect to MyFatoorah Invoice URL
     * Provide the index method with the purchase id and (payment method id or session id)
     *
     * @return Response
     */
    public function index() {

        
        try {
            //For example: pmid=0 for MyFatoorah invoice or pmid=1 for Knet in test mode
            $paymentId = request('pmid') ?: 0;
            $sessionId = request('sid') ?: null;

            $purchaseId  = request('oid') ?: 147;
            $curlData = $this->getPayLoadData($purchaseId);

            $mfObj   = new MyFatoorahPayment($this->mfConfig);
            $payment = $mfObj->getInvoiceURL($curlData, $paymentId, $purchaseId, $sessionId);

            return redirect($payment['invoiceURL']);
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return response()->json(['IsSuccess' => 'false', 'Message' => $exMessage]);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how to map purchase data to MyFatoorah
     * You can get the data using the purchase object in your system
     * 
     * @param int|string $purchaseId
     * 
     * @return array
     */
    private function getPayLoadData($purchaseId = null) {
        $callbackURL = route('myfatoorah.callback');

        //You can get the data using the purchase object in your system
        $purchase = $this->getTestOrderData($purchaseId);

        return [
            'CustomerName'       => 'FName LName',
            'InvoiceValue'       => $purchase['total'],
            'DisplayCurrencyIso' => $purchase['currency'],
            'CustomerEmail'      => 'test@test.com',
            'CallBackUrl'        => $callbackURL,
            'ErrorUrl'           => $callbackURL,
            'MobileCountryCode'  => '+966',
            'CustomerMobile'     => '12345678',
            'Language'           => 'en',
            'CustomerReference'  => $purchaseId,
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
     * Provide the checkout method with the purchase id to display its total amount and currency
     *
     * @return View
     */
    public function checkout($purchase) {
        try {

//        dd($purchase);
            //You can get the data using the purchase object in your system
            $purchaseId = $purchase->purchase_number ?: 147;
            $purchase2   = $this->getOrderData($purchase);
//            dd($purchase ,$purchase2);
            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');
//            dd($purchase ,'checkout');
            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($purchase2['total'], $purchase2['currency'], config('myfatoorah.register_apple_pay'));

//            dd($paymentMethods ,$purchase2);
            if (empty($paymentMethods['all'])) {
                throw new Exception('noMerchantPayments');
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
        $purchaseId = $inputData['CustomerReference'];

        //2. Get MyFatoorah invoice id
        $invoiceId = $inputData['InvoiceId'];

        //3. Check purchase status at MyFatoorah side
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

        //4. Update purchase transaction status on your system
        return ['IsSuccess' => true, 'Message' => $message, 'Data' => $inputData];
    }


//-----------------------------------------------------------------------------------------------------------------------------------------
    private function getTestOrderData($purchaseId) {
        return [
            'total'    => 1,
            'currency' => 'SAR'
        ];
    }

    private function getOrderData($purchase) {
//        dd($purchase ,'getOrderData');
        return [
            "CustomerName"  => $purchase->customer_name,
        "NotificationOption" =>  "ALL",
          "Language" =>  "ar",
        "DisplayCurrencyIso" =>  "SAR",
            "MobileCountryCode" =>  "966",
         "CustomerMobile" =>  "506552294",
        "total" =>  $purchase->pay_amount,
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
