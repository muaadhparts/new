<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\Shipping;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    public $keyId;
    public $keySecret;
    public $displayCurrency;
    public $api;


    public function __construct()
    {

        $data = PaymentGateway::whereKeyword('razorpay')->first();
        $paydata = $data->convertAutoData();
        $this->keyId = $paydata['key'];
        $this->keySecret = $paydata['secret'];
        $this->displayCurrency = 'INR';
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function store(Request $request)
    {

        if (!$request->has('purchase_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
        $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
        if ($curr->name != "INR") {
            return redirect()->back()->with('unsuccess', 'Please Select INR Currency For Razorpay.');
        }
        $input = $request->all();

        $notify_url = action('Api\Payment\RazorpayController@razorCallback');

        $settings = Muaadhsetting::findOrFail(1);

        $item_amount = $purchase->pay_amount * $purchase->currency_value;

        $item_name = $settings->title . " Purchase";

        $purchaseData = [
            'receipt' => $purchase->purchase_number,
            'amount' => round($item_amount) * 100, // 2000 rupees in paise
            'currency' => 'INR',
            'payment_capture' => 1, // auto capture
        ];

        $razorpayOrder = $this->api->order->create($purchaseData);

        $razorpayOrderId = $razorpayOrder['id'];

        session(['razorpay_order_id' => $razorpayOrderId]);

        $purchase['method'] = "Razorpay";
        $purchase['pay_amount'] = round($item_amount / $curr->value, 2);
        $purchase->update();

        $displayAmount = $amount = $purchaseData['amount'];

        if ($this->displayCurrency !== 'INR') {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);

            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }

        $checkout = 'automatic';

        if (isset($_GET['checkout']) and in_array($_GET['checkout'], ['automatic', 'manual'], true)) {
            $checkout = $_GET['checkout'];
        }

        $data = [
            "key" => $this->keyId,
            "amount" => $amount,
            "name" => $item_name,
            "description" => $item_name,
            "prefill" => [
                "name" => $request->name,
                "email" => $request->email,
                "contact" => $request->phone,
            ],
            "notes" => [
                "address" => $request->address,
                "merchant_purchase_id" => $purchase->purchase_number,
            ],
            "theme" => [
                "color" => "{{$settings->colors}}",
            ],
            "order_id" => $razorpayOrderId,
        ];

        if ($this->displayCurrency !== 'INR') {
            $data['display_currency'] = $this->displayCurrency;
            $data['display_amount'] = $displayAmount;
        }

        $json = json_encode($data);
        $displayCurrency = $this->displayCurrency;

        return view('frontend.razorpay-checkout', compact('data', 'displayCurrency', 'json', 'notify_url'));
    }

    public function razorCallback(Request $request)
    {

        $success = true;
        $razorpayOrder = $this->api->order->fetch(session('razorpay_order_id'));
        $purchase_id = $razorpayOrder['receipt'];
        $purchase = Purchase::where('purchase_number', $purchase_id)->first();

        if (empty($_POST['razorpay_payment_id']) === false) {
            try {
                $attributes = array(
                    'razorpay_order_id' => session('razorpay_order_id'),
                    'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                    'razorpay_signature' => $_POST['razorpay_signature'],
                );

                $this->api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $error = 'Razorpay Error : ' . $e->getMessage();
            }
        }

        if ($success === true) {

            $razorpayOrder = $this->api->order->fetch(session('razorpay_order_id'));

            $purchase_id = $razorpayOrder['receipt'];
            $transaction_id = $_POST['razorpay_payment_id'];
            $purchase = Purchase::where('purchase_number', $purchase_id)->first();

            if (isset($purchase)) {
                $data['txnid'] = $transaction_id;
                $data['payment_status'] = 'Completed';
                if ($purchase->dp == 1) {
                    $data['status'] = 'completed';
                }
                $purchase->update($data);
            }
            return redirect(route('front.payment.success', 1));
        } else {
            return redirect(route('front.checkout'));
        }
    }
}
