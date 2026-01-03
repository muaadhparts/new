<?php

namespace App\Http\Controllers;

use App\Classes\MuaadhMailer;
use App\Helpers\PurchaseHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Payment\Checkout\CheckoutBaseControlller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Purchase;
use App\Models\Reward;
use App\Traits\CreatesTryotoShipments;
use App\Traits\HandlesMerchantCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MyFatoorah\Library\MyFatoorah;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentEmbedded;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use App\Services\MerchantCredentialService;
use Exception;

class MyFatoorahController extends CheckoutBaseControlller {
    use CreatesTryotoShipments, HandlesMerchantCheckout;

    /**
     * @var array
     */
    public $mfConfig = [];

    protected MerchantCredentialService $merchantCredentialService;

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initiate MyFatoorah Configuration
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
     * Get merchant-specific MyFatoorah config
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
                "Configure via Admin Panel > Merchant Credentials or Merchant Dashboard."
            );
        }

        // Get merchant's gateway settings (test_mode, country)
        $gateway = \DB::table('payment_gateways')
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

    /**
     * Redirect to MyFatoorah Invoice URL
     *
     * POLICY (STRICT):
     * - merchant_id MUST come from Route parameter
     * - NO session fallback, NO POST fallback
     * - Fail immediately if merchant_id not in route
     *
     * @param int $merchantId From route: /checkout/merchant/{merchantId}/payment/myfatoorah
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index($merchantId) {
        // ====================================================================
        // STRICT POLICY: merchant_id FROM ROUTE ONLY
        // No session, no POST, no fallback - fail immediately if missing
        // ====================================================================
        Log::info('MyFatoorah index() called', [
            'merchantId_raw' => $merchantId,
            'request_path' => request()->path(),
            'request_method' => request()->method(),
            'user_id' => Auth::id()
        ]);

        $merchantId = (int)$merchantId;

        if (!$merchantId) {
            Log::error('MyFatoorah: merchant_id missing from route', [
                'user_id' => Auth::id(),
                'route' => request()->path()
            ]);
            return redirect()->route('front.cart')
                ->with('unsuccess', __('خطأ: لم يتم تحديد التاجر في مسار الدفع.'));
        }

        $cancel_url = route('front.cart');

        // Get merchant-specific MyFatoorah config - NO FALLBACK
        try {
            $this->mfConfig = $this->getMerchantMfConfig($merchantId);
        } catch (\Exception $e) {
            Log::error('MyFatoorah: Merchant credentials not configured', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);
            return redirect($cancel_url)->with('unsuccess', __('عذراً، لم يتم إعداد بوابة الدفع لهذا التاجر بعد. يرجى التواصل مع التاجر.'));
        }

        // Get checkout steps from merchant-specific sessions
        $step1 = Session::get('merchant_step1_' . $merchantId);
        $step2 = Session::get('merchant_step2_' . $merchantId);

        Log::info('MyFatoorah: Checking sessions', [
            'merchantId' => $merchantId,
            'step1_exists' => !empty($step1),
            'step2_exists' => !empty($step2),
            'all_session_keys' => array_keys(Session::all())
        ]);

        if (!$step1 || !$step2) {
            Log::warning('MyFatoorah: Session expired', [
                'merchantId' => $merchantId,
                'step1' => $step1,
                'step2' => $step2
            ]);
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        try {
            // حفظ بيانات الطلب في Session
            $input = array_merge($step1, $step2, request()->all());
            Session::put('input_data', $input);

            // استخدام المبلغ القادم من step3 مباشرة (المبلغ الصحيح المحسوب مسبقاً)
            $totalAmount = (float)request('total', 0);

            if ($totalAmount <= 0) {
                return redirect($cancel_url)->with('unsuccess', __('المبلغ الإجمالي غير صحيح'));
            }

            // تحويل المبلغ من العملة الحالية إلى العملة الافتراضية (إذا لزم الأمر)
            $finalAmount = $totalAmount / $this->curr->value;

            // إعداد بيانات الفاتورة
            $paymentId = request('pmid') ?: 0;
            $sessionId = request('sid') ?: null;
            $purchaseId = request('oid') ?: null;

            $curlData = [
                "NotificationOption" => "ALL",
                'CustomerName'       => $input['customer_name'] ?? 'Guest',
                'InvoiceValue'       => $finalAmount,
                'DisplayCurrencyIso' => $input['currency_name'] ?? 'SAR',
                'CustomerEmail'      => $input['customer_email'] ?? 'guest@example.com',
                'CallBackUrl'        => route('front.myfatoorah.notify'),
                'ErrorUrl'           => route('front.myfatoorah.notify'),
                'MobileCountryCode'  => '+966',
                'CustomerMobile'     => $input['customer_phone'] ?? '0000000000',
                'Language'           => 'en',
                'CustomerReference'  => 'order_' . time(),
                'SourceInfo'         => 'Laravel ' . app()::VERSION . ' - MyFatoorah Package ' . MYFATOORAH_LARAVEL_PACKAGE_VERSION
            ];

            $mfObj = new MyFatoorahPayment($this->mfConfig);
            $payment = $mfObj->getInvoiceURL($curlData, $paymentId, $purchaseId, $sessionId);

            Log::debug('MyFatoorah Invoice Created', [
                'invoice_url' => $payment['invoiceURL'],
                'payment_id' => $paymentId,
                'total_from_step3' => $totalAmount,
                'final_amount' => $finalAmount,
                'currency' => $input['currency_name'] ?? 'SAR',
                'user_id' => Auth::id()
            ]);

            return redirect($payment['invoiceURL']);

        } catch (Exception $ex) {
            Log::error('MyFatoorah Invoice Creation Failed', [
                'error' => $ex->getMessage(),
                'user_id' => Auth::id()
            ]);

            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return redirect($cancel_url)->with('unsuccess', __($exMessage));
        }
    }

    public function notify(Request $request)
    {
        // Get merchant checkout data at start
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];

        $cancel_url = route('front.cart');

        // POLICY: Merchant ID is REQUIRED for all payment operations
        if (!$merchantId) {
            Log::error('MyFatoorah notify: No merchant_id in checkout session', [
                'user_id' => Auth::id(),
                'session_data' => $merchantData
            ]);
            return redirect($cancel_url)->with('unsuccess', __('خطأ في جلسة الدفع. يرجى إعادة المحاولة من السلة.'));
        }

        // Get merchant-specific MyFatoorah config - NO FALLBACK
        try {
            $this->mfConfig = $this->getMerchantMfConfig($merchantId);
        } catch (\Exception $e) {
            Log::error('MyFatoorah notify: Merchant credentials not configured', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage()
            ]);
            return redirect($cancel_url)->with('unsuccess', __('عذراً، لم يتم إعداد بوابة الدفع لهذا التاجر بعد.'));
        }

        $steps = $this->getCheckoutSteps($merchantId, $merchantData['is_merchant_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = Session::get('input_data');
        $input = array_merge($step1, $step2);

        try {
            $paymentId = request('paymentId');

            if (!$paymentId) {
                Log::warning('MyFatoorah Callback: Missing PaymentId');
                return redirect($cancel_url)->with('unsuccess', __('معرف الدفع مفقود'));
            }

            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

            Log::debug('MyFatoorah Payment Status', [
                'payment_id' => $paymentId,
                'status' => $data->InvoiceStatus,
                'invoice_id' => $data->InvoiceId ?? null,
                'user_id' => Auth::id()
            ]);

            // التحقق من حالة الدفع
            if ($data->InvoiceStatus !== 'Paid') {
                $message = $this->getPaymentErrorMessage($data->InvoiceStatus, $data->InvoiceError);

                Log::warning('MyFatoorah Payment Not Completed', [
                    'payment_id' => $paymentId,
                    'status' => $data->InvoiceStatus,
                    'error' => $data->InvoiceError ?? 'N/A'
                ]);

                return redirect($cancel_url)->with('unsuccess', __($message));
            }

        } catch (Exception $ex) {
            Log::error('MyFatoorah Callback Failed', [
                'error' => $ex->getMessage(),
                'payment_id' => request('paymentId'),
                'user_id' => Auth::id()
            ]);

            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return redirect($cancel_url)->with('unsuccess', __($exMessage));
        }

        // ✅ الدفع نجح - إنشاء الطلب
        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);
        // PurchaseHelper::license_check($cart); // For License Checking
        $t_oldCart = Session::get('cart');
        $t_cart = new Cart($t_oldCart);
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = PurchaseHelper::item_affilate_check($cart); // For CatalogItem Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        // ✅ استخدام المبلغ المحفوظ من input_data بدلاً من إعادة الحساب
        $purchaseTotal = (float)($input['total'] ?? 0);

        // استخراج بيانات الشحن والتغليف من input_data
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping mode
            // تأكد أن القيم موجودة ولها قيم افتراضية
            if (!isset($input['shipping_title'])) $input['shipping_title'] = '';
            if (!isset($input['merchant_shipping_id'])) $input['merchant_shipping_id'] = 0;
            if (!isset($input['packing_title'])) $input['packing_title'] = '';
            if (!isset($input['merchant_packing_id'])) $input['merchant_packing_id'] = 0;
            if (!isset($input['shipping_cost'])) $input['shipping_cost'] = 0;
            if (!isset($input['packing_cost'])) $input['packing_cost'] = 0;

            // تحويل القيم المصفوفية إلى JSON إذا كانت مصفوفات
            if (!isset($input['merchant_shipping_ids']) || empty($input['merchant_shipping_ids'])) {
                $input['merchant_shipping_ids'] = json_encode([]);
            } elseif (is_array($input['merchant_shipping_ids'])) {
                $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
            }

            if (!isset($input['merchant_packing_ids']) || empty($input['merchant_packing_ids'])) {
                $input['merchant_packing_ids'] = json_encode([]);
            } elseif (is_array($input['merchant_packing_ids'])) {
                $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
            }

            if (!isset($input['merchant_ids']) || empty($input['merchant_ids'])) {
                $input['merchant_ids'] = json_encode([]);
            } elseif (is_array($input['merchant_ids'])) {
                $input['merchant_ids'] = json_encode($input['merchant_ids']);
            }
        } else {
            // Multi shipping mode
            // تحويل المصفوفات إلى JSON للحفظ في قاعدة البيانات
            if (isset($input['shipping']) && is_array($input['shipping'])) {
                $input['merchant_shipping_ids'] = json_encode($input['shipping']);
                $input['shipping_title'] = json_encode($input['shipping']);
                $input['merchant_shipping_id'] = json_encode($input['shipping']);
            } elseif (isset($input['merchant_shipping_ids'])) {
                if (is_array($input['merchant_shipping_ids'])) {
                    $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
                }
                $input['shipping_title'] = $input['merchant_shipping_ids'];
                $input['merchant_shipping_id'] = $input['merchant_shipping_ids'];
            } else {
                $input['merchant_shipping_ids'] = json_encode([]);
                $input['shipping_title'] = json_encode([]);
                $input['merchant_shipping_id'] = json_encode([]);
            }

            if (isset($input['packeging']) && is_array($input['packeging'])) {
                $input['merchant_packing_ids'] = json_encode($input['packeging']);
                $input['packing_title'] = json_encode($input['packeging']);
                $input['merchant_packing_id'] = json_encode($input['packeging']);
            } elseif (isset($input['merchant_packing_ids'])) {
                if (is_array($input['merchant_packing_ids'])) {
                    $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
                }
                $input['packing_title'] = $input['merchant_packing_ids'];
                $input['merchant_packing_id'] = $input['merchant_packing_ids'];
            } else {
                $input['merchant_packing_ids'] = json_encode([]);
                $input['packing_title'] = json_encode([]);
                $input['merchant_packing_id'] = json_encode([]);
            }

            if (isset($input['merchant_ids'])) {
                if (is_array($input['merchant_ids'])) {
                    $input['merchant_ids'] = json_encode($input['merchant_ids']);
                }
            } else {
                $input['merchant_ids'] = json_encode([]);
            }

            if (!isset($input['shipping_cost'])) $input['shipping_cost'] = 0;
            if (!isset($input['packing_cost'])) $input['packing_cost'] = 0;

            unset($input['shipping']);
            unset($input['packeging']);
        }


        $purchase = new Purchase;
        $input['cart'] = $new_cart;
        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $purchaseTotal;
        $input['purchase_number'] = Str::random(4) . time();
        $input['wallet_price'] = $input['wallet_price'] / $this->curr->value;
        $input['payment_status'] = "Completed";
        $input['method'] = "MyFatoorah";  // Payment Method Name
        $input['txnid'] = $data->InvoiceId;  // Transaction ID from MyFatoorah

        // Tax location handling removed - states table deleted
        if ($input['tax_type'] == 'state_tax') {
            $input['tax_location'] = ''; // State model removed
        } else {
            $input['tax_location'] = Country::findOrFail($input['tax'])->country_name;
        }
        $input['tax'] = Session::get('current_tax');

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

        // Create an OTO shipment (doesn't break the purchase on failure)
        $this->createOtoShipments($purchase, $input);

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

        // Remove only merchant's items from cart
        $this->removeMerchantItemsFromCart($merchantId, $originalCart);

        if ($purchase->user_id != 0 && $purchase->wallet_price != 0) {
            PurchaseHelper::add_to_transaction($purchase, $purchase->wallet_price); // Store To Transactions
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

        // Determine success URL based on remaining cart items
        $success_url = $this->getSuccessUrl($merchantId, $originalCart);
        return redirect($success_url);


     }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how to map order data to MyFatoorah
     * You can get the data using the order object in your system
     * 
     * @param int|string $purchaseId
     * 
     * @return array
     */

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
            $purchaseId = request('oid') ?: 147;
            $purchase   = $this->getTestOrderData($purchaseId);

            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');

            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($purchase['total'], $purchase['currency'], config('myfatoorah.register_apple_pay'));

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
        $purchaseId = $inputData['CustomerReference'];

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
    private function getTestMessage($status, $error) {
        if ($status == 'Paid') {
            return 'Invoice is paid.';
        } else if ($status == 'Failed') {
            return 'Invoice is not paid due to ' . $error;
        } else if ($status == 'Expired') {
            return $error;
        }
    }

    /**
     * Get user-friendly payment error message based on status
     *
     * @param string $status
     * @param string $error
     * @return string
     */
    private function getPaymentErrorMessage($status, $error) {
        switch ($status) {
            case 'Failed':
                return 'فشل الدفع: ' . ($error ?: 'حدث خطأ أثناء معالجة الدفع');

            case 'Expired':
                return 'انتهت صلاحية رابط الدفع. الرجاء المحاولة مرة أخرى.';

            case 'Pending':
                return 'الدفع قيد المعالجة. الرجاء الانتظار أو المحاولة مرة أخرى.';

            case 'Canceled':
            case 'Cancelled':
                return 'تم إلغاء عملية الدفع.';

            default:
                return 'حالة الدفع غير معروفة: ' . $status;
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
