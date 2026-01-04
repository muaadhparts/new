<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer
};

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoguepayController extends TopUpBaseController
{

    public function store(Request $request) {

        $user = $this->user;
        $curr = $this->curr;
  
        $deposit = new TopUp;
        $deposit->user_id = $user->id;
        $deposit->currency = $curr->sign;
        $deposit->currency_code = $curr->name;
        $deposit->currency_value = $curr->value;
        $deposit->amount = $request->amount / $curr->value;
        $deposit->method = 'Voguepay';
        $deposit->txnid = $request->ref_id;
        $deposit->status = 1;
        $deposit->save();
  
        $user->balance = $user->balance + ($request->amount / $curr->value);
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
  
        $data = [
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
        $mailer->sendAutoMail($data);

        return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account successfully.'));
  
    }  
}