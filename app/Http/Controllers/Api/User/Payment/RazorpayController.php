<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\TopUp;
use App\Models\Muaadhsetting;
use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{

    public function __construct()
    {
        $data = MerchantPayment::whereKeyword('razorpay')->first();
        $paydata = $data->convertAutoData();
        $this->keyId = $paydata['key'];
        $this->keySecret = $paydata['secret'];
        $this->displayCurrency = 'INR';
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function store(Request $request)
    {

        if (!$request->has('topup_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $topupNumber = $request->topup_number;
        $purchase = TopUp::where('topup_number', $topupNumber)->first();
        $curr = Currency::where('name', '=', $purchase->currency_code)->first();
        if ($curr->name != "INR") {
            return redirect()->back()->with('unsuccess', 'Please Select INR Currency For Razorpay.');
        }
        $input = $request->all();

        $notify_url = action('Api\User\Payment\RazorpayController@razorCallback');

        $settings = Muaadhsetting::findOrFail(1);

        $item_amount = $purchase->amount * $curr->value;

        $item_name = $settings->title . " TopUp";

        $purchaseData = [
            'receipt' => $purchase->topup_number,
            'amount' => round($item_amount) * 100, // 2000 rupees in paise
            'currency' => 'INR',
            'payment_capture' => 1, // auto capture
        ];

        $razorpayOrder = $this->api->purchase->create($purchaseData);

        $razorpayOrderId = $razorpayOrder['id'];

        session(['razorpay_order_id' => $razorpayOrderId]);

        $purchase['method'] = $request->method;
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
                "merchant_topup_id" => $purchase->topup_number,
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
        $razorpayOrder = $this->api->purchase->fetch(session('razorpay_order_id'));
        $purchase_id = $razorpayOrder['receipt'];
        $purchase = TopUp::where('topup_number', $purchase_id)->first();
        $cancel_url = route('user.topup.send', $purchase->topup_number);

        $error = "Payment Failed";

        if (empty($_POST['razorpay_payment_id']) === false) {
            //$api = new Api($keyId, $keySecret);

            try
            {
                // Please note that the razorpay purchase ID must
                // come from a trusted source (session here, but
                // could be database or something else)
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

            $razorpayOrder = $this->api->purchase->fetch(session('razorpay_order_id'));

            $purchase_id = $razorpayOrder['receipt'];
            $transaction_id = $_POST['razorpay_payment_id'];
            $purchase = TopUp::where('topup_number', $purchase_id)->first();

            $user = \App\Models\User::findOrFail($purchase->user_id);
            $user->balance = $user->balance + ($purchase->amount);
            $user->save();

            if (isset($purchase)) {
                $purchase['txnid'] = $transaction_id;
                $purchase['status'] = 1;
                $purchase->update();

                // store in wallet_logs table
                if ($purchase->status == 1) {
                    $walletLog = new \App\Models\WalletLog;
                    $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                    $walletLog->user_id = $purchase->user_id;
                    $walletLog->amount = $purchase->amount;
                    $walletLog->user_id = $purchase->user_id;
                    $walletLog->currency_sign = $purchase->currency;
                    $walletLog->currency_code = $purchase->currency_code;
                    $walletLog->currency_value = $purchase->currency_value;
                    $walletLog->method = $purchase->method;
                    $walletLog->txnid = $purchase->txnid;
                    $walletLog->details = 'Wallet TopUp';
                    $walletLog->type = 'plus';
                    $walletLog->save();
                }

            }
            return redirect(route('user.success', 1));

        } else {

            return redirect($cancel_url);
        }

    }

}
