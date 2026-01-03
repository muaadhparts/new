<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Muaadhsetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mollie\Laravel\Facades\Mollie;
use Session;

class MollyController extends Controller
{

    public function store(Request $request)
    {

        if (!$request->has('deposit_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $deposit_number = $request->deposit_number;
        $purchase = Deposit::where('deposit_number', $deposit_number)->first();
        $curr = Currency::where('name', '=', $purchase->currency_code)->first();

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

        $purchase['item_name'] = $settings->title . " Deposit";
        $purchase['item_amount'] = $item_amount;

        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $curr->name,
                'value' => '' . sprintf('%0.2f', $purchase['amount']) . '', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $settings->title . " Deposit",
            'redirectUrl' => route('api.user.deposit.molly.notify'),
        ]);

        Session::put('payment_id', $payment->id);
        Session::put('molly_data', $purchase->id);
        Session::put('paypal_data', $input);
        $payment = Mollie::api()->payments()->get($payment->id);

        return redirect($payment->getCheckoutUrl(), 303);
    }

    public function notify(Request $request)
    {

        $purchase = Deposit::findOrFail(Session::get('molly_data'));
  
        $cancel_url = route('user.deposit.send', $purchase->deposit_number);
        $payment = Mollie::api()->payments()->get(Session::get('payment_id'));

        if ($payment->status == 'paid') {

            $user = \App\Models\User::findOrFail($purchase->user_id);
            $user->balance = $user->balance + ($purchase->amount);
            $user->save();
            $purchase['txnid'] = $payment->id;
            $purchase['method'] = 'Molly';
            $purchase['status'] = 1;
            $purchase->update();

            // store in transaction table
            if ($purchase->status == 1) {
                $transaction = new Transaction;
                $transaction->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $transaction->user_id = $purchase->user_id;
                $transaction->amount = $purchase->amount;
                $transaction->user_id = $purchase->user_id;
                $transaction->currency_sign = $purchase->currency;
                $transaction->currency_code = $purchase->currency_code;
                $transaction->currency_value = $purchase->currency_value;
                $transaction->method = $purchase->method;
                $transaction->txnid = $purchase->txnid;
                $transaction->details = 'Payment Deposit';
                $transaction->type = 'plus';
                $transaction->save();
            }

            return redirect(route('user.success', 1));
        } else {
            return redirect($cancel_url);
        }
    }

}
