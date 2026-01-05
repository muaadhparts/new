<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;


class StripeController extends TopUpBaseController
{

    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('stripe')->first();
        $paydata = $data->convertAutoData();
        Config::set('services.stripe.key', $paydata['key']);
        Config::set('services.stripe.secret', $paydata['secret']);
    }



    public function store(Request $request)
    {

        $data = MerchantPayment::whereKeyword('stripe')->first();
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Stripe Payment.'));
        }


        try {
            $stripe_secret_key = Config::get('services.stripe.secret');
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            $checkout_session = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "success_url" => route('topup.stripe.notify') . '?session_id={CHECKOUT_SESSION_ID}',
                "cancel_url" => route('topup.payment.cancle'),
                "customer_email" => $user->email,
                "locale" => "auto",
                "line_items" => [
                    [
                        "quantity" => 1,
                        "price_data" => [
                            "currency" => $this->curr->name,
                            "unit_amount" => $item_amount * 100,
                            "product_data" => [
                                "name" => $this->gs->title . ' TopUp'
                            ]
                        ]
                    ],
                ]
            ]);

            Session::put('input_data', $request->all());
            return redirect($checkout_session->url);
        } catch (Exception $e) {
            return back()->with('unsuccess', $e->getMessage());
        }
    }


    public function notify(Request $request)
    {
        $input = Session::get('input_data');

        $user = $this->user;
        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($request->session_id);
        if ($response->status == 'complete') {

            $user->balance = $user->balance + ($input['amount'] / $this->curr->value);
            $user->mail_sent = 1;
            $user->save();

            $topup = new TopUp;
            $topup->user_id = $user->id;
            $topup->currency = $this->curr->sign;
            $topup->currency_code = $this->curr->name;
            $topup->currency_value = $this->curr->value;
            $topup->amount = $input['amount'] / $this->curr->value;
            $topup->method = 'Stripe';
            $topup->txnid = $response->payment_intent;
            $topup->status = 1;
            $topup->save();


            // store in wallet_logs table
            if ($topup->status == 1) {
                $walletLog = new WalletLog;
                $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $walletLog->user_id = $topup->user_id;
                $walletLog->amount = $topup->amount;
                $walletLog->user_id = $topup->user_id;
                $walletLog->currency_sign  = $topup->currency;
                $walletLog->currency_code  = $topup->currency_code;
                $walletLog->currency_value = $topup->currency_value;
                $walletLog->method = $topup->method;
                $walletLog->txnid = $topup->txnid;
                $walletLog->details = 'Payment TopUp';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            $data = [
                'to' => $user->email,
                'type' => "wallet_topup",
                'cname' => $user->name,
                'damount' => $topup->amount,
                'wbalance' => $user->balance,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => "",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoMail($data);


            return redirect()->route('user-dashboard')->with('success', __('Balance has been added to your account.'));
        }
    }
}
