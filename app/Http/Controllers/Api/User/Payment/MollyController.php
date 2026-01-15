<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\MonetaryUnit;
use App\Models\TopUp;
use App\Models\Muaadhsetting;
use App\Models\WalletLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mollie\Laravel\Facades\Mollie;
use Session;

class MollyController extends Controller
{

    public function store(Request $request)
    {

        if (!$request->has('topup_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $topupNumber = $request->topup_number;
        $purchase = TopUp::where('topup_number', $topupNumber)->first();
        $curr = MonetaryUnit::where('name', '=', $purchase->currency_code)->first();

        $available_currency = array(
            'AED',
            'AUD',
            'BGN',
            'BRL',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HRK',
            'HUF',
            'ILS',
            'ISK',
            'JPY',
            'MXN',
            'MYR',
            'NOK',
            'NZD',
            'PHP',
            'PLN',
            'RON',
            'RUB',
            'SEK',
            'SGD',
            'THB',
            'TWD',
            'USD',
            'ZAR',
        );
        if (!in_array($curr->name, $available_currency)) {
            return redirect()->back()->with('unsuccess', 'Invalid Currency For Molly Payment.');
        }

        $input = $request->all();

        $settings = Muaadhsetting::findOrFail(1);

        $item_amount = round($purchase->pay_amount / $curr->value, 2);

        $purchase['item_name'] = $settings->site_name . " TopUp";
        $purchase['item_amount'] = $item_amount;

        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $curr->name,
                'value' => '' . sprintf('%0.2f', $purchase['amount']) . '', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $settings->site_name . " TopUp",
            'redirectUrl' => route('api.user.topup.molly.notify'),
        ]);

        Session::put('payment_id', $payment->id);
        Session::put('molly_data', $purchase->id);
        Session::put('paypal_data', $input);
        $payment = Mollie::api()->payments()->get($payment->id);

        return redirect($payment->getCheckoutUrl(), 303);
    }

    public function notify(Request $request)
    {

        $purchase = TopUp::findOrFail(Session::get('molly_data'));
  
        $cancel_url = route('user.topup.send', $purchase->topup_number);
        $payment = Mollie::api()->payments()->get(Session::get('payment_id'));

        if ($payment->status == 'paid') {

            $user = \App\Models\User::findOrFail($purchase->user_id);
            $user->balance = $user->balance + ($purchase->amount);
            $user->save();
            $purchase['txnid'] = $payment->id;
            $purchase['method'] = 'Molly';
            $purchase['status'] = 1;
            $purchase->update();

            // store in wallet_logs table
            if ($purchase->status == 1) {
                $walletLog = new WalletLog;
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

            return redirect(route('user.success', 1));
        } else {
            return redirect($cancel_url);
        }
    }

}
