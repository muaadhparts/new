<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Models\Muaadhsetting;
use Illuminate\Http\Request;
use App\Models\TopUp;
use App\Models\Currency;
use App\Http\Controllers\Controller;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Omnipay\Omnipay;

class PaymentController extends Controller
{
    public $_api_context;
    public $gateway;
    public function __construct()
    {
        $data = MerchantPayment::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
    }

    public function store(Request $request)
    {

        if (!$request->has('deposit_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $deposit_number = $request->deposit_number;

        $deposit = TopUp::where('deposit_number', $deposit_number)->first();
        $curr = Currency::where('name', '=', $deposit->currency_code)->first();

        $support = ['USD', 'EUR'];
        if (!in_array($curr->name, $support)) {
            return redirect()->back()->with('unsuccess', 'Please Select USD Or EUR Currency For Paypal.');
        }

        $item_amount = $deposit->amount * $deposit->currency_value;


        $notify_url = action('Api\User\Payment\PaymentController@notify');
        $cancel_url = route('user.success', 0);
        try {
            $response = $this->gateway->purchase(array(
                'amount' => $item_amount,
                'currency' => $curr->name,
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

            if ($response->isRedirect()) {
                Session::put('deposit_number', $deposit_number);
                if ($response->redirect()) {
                    return redirect($response->redirect());
                }
            } else {
                return redirect(route('user.success', 0));
            }
        } catch (\Throwable $th) {
            return redirect(route('user.success', 0));
        }
    }




    public function notify(Request $request)
    {

        $responseData = $request->all();
        $deposit_number = Session::get('deposit_number');
        if (empty($responseData['PayerID']) || empty($responseData['token'])) {
            return [
                'status' => false,
                'message' => __('Unknown error occurred'),
            ];
        }

        $purchaseRequest = $this->gateway->completePurchase(array(
            'payer_id' => $responseData['PayerID'],
            'transactionReference' => $responseData['paymentId'],
        ));

        $response = $purchaseRequest->send();

        if ($response->isSuccessful()) {

            $purchase = TopUp::where('deposit_number', $deposit_number)->first();
            $user = \App\Models\User::findOrFail($purchase->user_id);
            $user->balance = $user->balance + ($purchase->amount);
            $user->save();

            $purchase->method = "Paypal";
            $purchase->txnid = $response->getData()['wallet_logs'][0]['related_resources'][0]['sale']['id'];
            $purchase->status = 1;
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
                $walletLog->details = 'Payment Deposit';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            return redirect(route('user.success', 1));
        } else {
            return redirect(route('user.success', 0));
        }
    }
}
