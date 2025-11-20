<?php

namespace App\Http\Controllers;

use App\Classes\MuaadhMailer;
use App\Helpers\OrderHelper;
use App\Helpers\PriceHelper;
use App\Http\Controllers\Payment\Checkout\CheckoutBaseControlller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Order;
use App\Models\Reward;
use App\Models\State;
use App\Traits\CreatesTryotoShipments;
use App\Traits\HandlesVendorCheckout;
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
use Exception;

class MyFatoorahController extends CheckoutBaseControlller {
    use CreatesTryotoShipments, HandlesVendorCheckout;

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
        // Get vendor checkout data
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];

        // Get steps from vendor sessions ONLY
        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $cancel_url = route('front.cart');

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
            $orderId = request('oid') ?: null;

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
            $payment = $mfObj->getInvoiceURL($curlData, $paymentId, $orderId, $sessionId);

            Log::info('MyFatoorah Invoice Created', [
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
        // Get vendor checkout data at start
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];

        $steps = $this->getCheckoutSteps($vendorId, $vendorData['is_vendor_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $cancel_url = route('front.cart');

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

            Log::info('MyFatoorah Payment Status', [
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
        $cart = $this->filterCartForVendor($originalCart, $vendorId);
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

        // ✅ استخدام المبلغ المحفوظ من input_data بدلاً من إعادة الحساب
        $orderTotal = (float)($input['total'] ?? 0);

        // استخراج بيانات الشحن والتغليف من input_data
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping mode
            $input['is_shipping'] = 0;

            // تأكد أن القيم موجودة ولها قيم افتراضية
            if (!isset($input['shipping_title'])) $input['shipping_title'] = '';
            if (!isset($input['vendor_shipping_id'])) $input['vendor_shipping_id'] = 0;
            if (!isset($input['packing_title'])) $input['packing_title'] = '';
            if (!isset($input['vendor_packing_id'])) $input['vendor_packing_id'] = 0;
            if (!isset($input['shipping_cost'])) $input['shipping_cost'] = 0;
            if (!isset($input['packing_cost'])) $input['packing_cost'] = 0;

            // تحويل القيم المصفوفية إلى JSON إذا كانت مصفوفات
            if (!isset($input['vendor_shipping_ids']) || empty($input['vendor_shipping_ids'])) {
                $input['vendor_shipping_ids'] = json_encode([]);
            } elseif (is_array($input['vendor_shipping_ids'])) {
                $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
            }

            if (!isset($input['vendor_packing_ids']) || empty($input['vendor_packing_ids'])) {
                $input['vendor_packing_ids'] = json_encode([]);
            } elseif (is_array($input['vendor_packing_ids'])) {
                $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
            }

            if (!isset($input['vendor_ids']) || empty($input['vendor_ids'])) {
                $input['vendor_ids'] = json_encode([]);
            } elseif (is_array($input['vendor_ids'])) {
                $input['vendor_ids'] = json_encode($input['vendor_ids']);
            }
        } else {
            // Multi shipping mode
            $input['is_shipping'] = 1;

            // تحويل المصفوفات إلى JSON للحفظ في قاعدة البيانات
            if (isset($input['shipping']) && is_array($input['shipping'])) {
                $input['vendor_shipping_ids'] = json_encode($input['shipping']);
                $input['shipping_title'] = json_encode($input['shipping']);
                $input['vendor_shipping_id'] = json_encode($input['shipping']);
            } elseif (isset($input['vendor_shipping_ids'])) {
                if (is_array($input['vendor_shipping_ids'])) {
                    $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
                }
                $input['shipping_title'] = $input['vendor_shipping_ids'];
                $input['vendor_shipping_id'] = $input['vendor_shipping_ids'];
            } else {
                $input['vendor_shipping_ids'] = json_encode([]);
                $input['shipping_title'] = json_encode([]);
                $input['vendor_shipping_id'] = json_encode([]);
            }

            if (isset($input['packeging']) && is_array($input['packeging'])) {
                $input['vendor_packing_ids'] = json_encode($input['packeging']);
                $input['packing_title'] = json_encode($input['packeging']);
                $input['vendor_packing_id'] = json_encode($input['packeging']);
            } elseif (isset($input['vendor_packing_ids'])) {
                if (is_array($input['vendor_packing_ids'])) {
                    $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
                }
                $input['packing_title'] = $input['vendor_packing_ids'];
                $input['vendor_packing_id'] = $input['vendor_packing_ids'];
            } else {
                $input['vendor_packing_ids'] = json_encode([]);
                $input['packing_title'] = json_encode([]);
                $input['vendor_packing_id'] = json_encode([]);
            }

            if (isset($input['vendor_ids'])) {
                if (is_array($input['vendor_ids'])) {
                    $input['vendor_ids'] = json_encode($input['vendor_ids']);
                }
            } else {
                $input['vendor_ids'] = json_encode([]);
            }

            if (!isset($input['shipping_cost'])) $input['shipping_cost'] = 0;
            if (!isset($input['packing_cost'])) $input['packing_cost'] = 0;

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
        $input['method'] = "MyFatoorah";  // ✅ إضافة Payment Method Name
        $input['txnid'] = $data->InvoiceId;  // ✅ رقم المعاملة من MyFatoorah

        if ($input['tax_type'] == 'state_tax') {
            $input['tax_location'] = State::findOrFail($input['tax'])->state;
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
                OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $order->fill($input)->save();

        // Create an OTO shipment (doesn't break the order on failure)
        $this->createOtoShipments($order, $input);

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

        // Remove only vendor's products from cart
        $this->removeVendorProductsFromCart($vendorId, $originalCart);

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
        $mailer = new MuaadhMailer();
        $mailer->sendAutoOrderMail($data, $order->id);

        //Sending Email To Admin
        $data = [
            'to' => $this->ps->contact_email,
            'subject' => "New Order Recieved!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
        ];
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        // Determine success URL based on remaining cart items
        $success_url = $this->getSuccessUrl($vendorId, $originalCart);
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
