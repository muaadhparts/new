<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Redirect;
use Session;
use Omnipay\Omnipay;

class PaypalController extends TopUpBaseController
{
    public $_api_context;
    public $gateway;
    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
    }

    public function store(Request $request)
    {

        $data = MerchantPayment::whereKeyword('paypal')->first();
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id, true);

        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Paypal Payment.'));
        }

        $item_name = "TopUp via Paypal Payment";
        $cancel_url = route('topup.payment.cancle');
        $notify_url = route('topup.paypal.notify');

        $dep['user_id'] = $user->id;
        $dep['currency'] = $this->curr->sign;
        $dep['currency_code'] = $this->curr->name;
        $dep['amount'] = $request->amount / $this->curr->value;
        $dep['currency_value'] = $this->curr->value;
        $dep['method'] = 'Paypal';

        try {
            $response = $this->gateway->purchase(array(
                'amount' => $item_amount,
                'currency' => $curr->name,
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

            if ($response->isRedirect()) {
                Session::put('input_data', $request->all());
                Session::put('topup', $dep);

                if ($response->redirect()) {

                    return redirect($response->redirect());
                }
            } else {
                return redirect()->back()->with('unsuccess', $response->getMessage());
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('unsuccess', $th->getMessage());
        }
    }

    public function notify(Request $request)
    {
        $responseData = $request->all();
        $dep = Session::get('topup');

        $success_url = route('topup.payment.return');
        $cancel_url = route('topup.payment.cancle');


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

            $topUp = new TopUp;
            $topUp->user_id = $dep['user_id'];
            $topUp->currency = $dep['currency'];
            $topUp->currency_code = $dep['currency_code'];
            $topUp->amount = $dep['amount'];
            $topUp->currency_value = $dep['currency_value'];
            $topUp->method = $dep['method'];
            $topUp->txnid = $response->getData()['wallet_logs'][0]['related_resources'][0]['sale']['id'];
            $topUp->status = 1;
            $topUp->save();

            $user = \App\Models\User::findOrFail($topUp->user_id);
            $user->balance = $user->balance + ($topUp->amount);
            $user->save();

            // store in wallet_logs table
            if ($topUp->status == 1) {
                $walletLog = new \App\Models\WalletLog;
                $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $walletLog->user_id = $topUp->user_id;
                $walletLog->amount = $topUp->amount;
                $walletLog->user_id = $topUp->user_id;
                $walletLog->currency_sign = $topUp->currency;
                $walletLog->currency_code = $topUp->currency_code;
                $walletLog->currency_value = $topUp->currency_value;
                $walletLog->method = $topUp->method;
                $walletLog->txnid = $topUp->txnid;
                $walletLog->details = 'Wallet TopUp';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            $maildata = [
                'to' => $user->email,
                'type' => "wallet_topup",
                'cname' => $user->name,
                'damount' => $topUp->amount,
                'wbalance' => $user->balance,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => "",
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendAutoMail($maildata);

            Session::forget('topup');
            Session::forget('paypal_payment_id');
            return redirect($success_url);
        } else {
            return redirect($cancel_url);
        }
    }
}
