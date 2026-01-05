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
  
        $topUp = new TopUp;
        $topUp->user_id = $user->id;
        $topUp->currency = $curr->sign;
        $topUp->currency_code = $curr->name;
        $topUp->currency_value = $curr->value;
        $topUp->amount = $request->amount / $curr->value;
        $topUp->method = 'Voguepay';
        $topUp->txnid = $request->ref_id;
        $topUp->status = 1;
        $topUp->save();

        $user->balance = $user->balance + ($request->amount / $curr->value);
        $user->save();

        // store in wallet_logs table
        if ($topUp->status == 1) {
            $walletLog = new WalletLog;
            $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
            $walletLog->user_id = $topUp->user_id;
            $walletLog->amount = $topUp->amount;
            $walletLog->user_id = $topUp->user_id;
            $walletLog->currency_sign = $topUp->currency;
            $walletLog->currency_code = $topUp->currency_code;
            $walletLog->currency_value= $topUp->currency_value;
            $walletLog->method = $topUp->method;
            $walletLog->txnid = $topUp->txnid;
            $walletLog->details = 'Wallet TopUp';
            $walletLog->type = 'plus';
            $walletLog->save();
        }

        $data = [
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
        $mailer->sendAutoMail($data);

        return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account successfully.'));
  
    }  
}