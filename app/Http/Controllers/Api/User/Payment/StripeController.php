<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\MonetaryUnit;
use App\Models\TopUp;
use App\Models\Muaadhsetting;
use App\Models\MerchantPayment;
use App\Models\WalletLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class StripeController extends Controller
{

    public function __construct()
    {

        $data = MerchantPayment::whereKeyword('stripe')->first();
        $paydata = $data->convertAutoData();
        \Config::set('services.stripe.key', $paydata['key']);
        \Config::set('services.stripe.secret', $paydata['secret']);
    }

    public function store(Request $request)
    {

        $topUp = TopUp::where('topup_number', $request->topup_number)->first();
        $item_amount = $topUp->amount * $topUp->currency_value;
        $curr = MonetaryUnit::where('name', '=', $topUp->currency_code)->first();
        $gs = Muaadhsetting::findOrFail(1);

        try {
            $stripe_secret_key = Config::get('services.stripe.secret');
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            $checkout_session = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "success_url" => route('api.user.topup.stripe.notify') . '?session_id={CHECKOUT_SESSION_ID}',
                "cancel_url" => route('front.payment.cancle'),
                "locale" => "auto",

                "line_items" => [
                    [
                        "quantity" => 1,
                        "price_data" => [
                            "currency" => $curr->name,
                            "unit_amount" => $item_amount * 100,
                            "product_data" => [
                                "name" => $gs->site_name . ' TopUp'
                            ]
                        ]
                    ],
                ]
            ]);

            Session::put('topup_id', $request->topup_number);
            return redirect($checkout_session->url);
        } catch (Exception $e) {
            return back()->with('unsuccess', $e->getMessage());
        }
    }


    public function notify(Request $request)
    {

        $topupNumber = Session::get('topup_id');
        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($request->session_id);
        $topUp = TopUp::where('topup_number', $topupNumber)->firstOrFail();



        if ($response->status == 'complete') {
            $user = \App\Models\User::findOrFail($topUp->user_id);
            $user->balance = $user->balance + ($topUp->amount);
            $user->save();
            $topUp['status'] = 1;
            $topUp['method'] = 'Stripe';
            $topUp['txnid'] = $response->payment_intent;
            $topUp->update();
            // store in wallet_logs table
            if ($topUp->status == 1) {
                $walletLog = new WalletLog;
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
            return redirect(route('user.success', 1));
        } else {
            return redirect(route('user.success', 0));
        }
    }
}
