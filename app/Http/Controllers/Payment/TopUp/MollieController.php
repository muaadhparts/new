<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mollie\Laravel\Facades\Mollie;

use Session;
use PurchaseHelper;


class MollieController extends TopUpBaseController
{

    public function store(Request $request){

        $data = MerchantPayment::whereKeyword('mollie')->first();  
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Molly Payment.'));
        }

        $item_name = "Deposit via Molly Payment";

        $dep['user_id'] = $user->id;
        $dep['currency'] = $this->curr->sign;
        $dep['currency_code'] = $this->curr->name;
        $dep['amount'] = $request->amount / $this->curr->value;
        $dep['currency_value'] = $this->curr->value;
        $dep['method'] = 'Molly Payment';

      
        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $curr->name,
                'value' => ''.sprintf('%0.2f', $item_amount).'', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $item_name ,
            'redirectUrl' => route('topup.molly.notify'),
            ]);

        Session::put('molly_data',$dep);
        Session::put('payment_id',$payment->id);
        $payment = Mollie::api()->payments()->get($payment->id);

        return redirect($payment->getCheckoutUrl(), 303);

 }


    public function notify(Request $request){

        $dep = Session::get('molly_data');
        $success_url = route('topup.payment.return');
        $cancel_url = route('topup.payment.cancle');
        $payment = Mollie::api()->payments()->get(Session::get('payment_id'));

        if($payment->status == 'paid'){
                    $deposit = new TopUp;
                    $deposit->user_id = $dep['user_id'];
                    $deposit->currency = $dep['currency'];
                    $deposit->currency_code = $dep['currency_code'];
                    $deposit->amount = $dep['amount'];
                    $deposit->currency_value = $dep['currency_value'];
                    $deposit->method = $dep['method'];
                    $deposit->txnid = $payment->id;
                    $deposit->status = 1;
                    $deposit->save();

                    $user = \App\Models\User::findOrFail($deposit->user_id);
                    $user->balance = $user->balance + ($deposit->amount);
                    $user->save();

                    // store in wallet_logs table
                    if ($deposit->status == 1) {
                        $walletLog = new WalletLog;
                        $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
                        $walletLog->user_id = $deposit->user_id;
                        $walletLog->amount = $deposit->amount;
                        $walletLog->user_id = $deposit->user_id;
                        $walletLog->currency_sign = $deposit->currency;
                        $walletLog->currency_code = $deposit->currency_code;
                        $walletLog->currency_value= $deposit->currency_value;
                        $walletLog->method = $deposit->method;
                        $walletLog->txnid = $deposit->txnid;
                        $walletLog->details = 'Payment Deposit';
                        $walletLog->type = 'plus';
                        $walletLog->save();
                    }
            
                    $maildata = [
                        'to' => $user->email,
                        'type' => "wallet_deposit",
                        'cname' => $user->name,
                        'damount' => $deposit->amount,
                        'wbalance' => $user->balance,
                        'oamount' => "",
                        'aname' => "",
                        'aemail' => "",
                        'onumber' => "",
                    ];
                    $mailer = new MuaadhMailer();
                    $mailer->sendAutoMail($maildata);

            Session::forget('molly_data');

            return redirect($success_url);
        }
        else {
            return redirect($cancel_url);
        }

        return redirect($cancel_url);
    }

}