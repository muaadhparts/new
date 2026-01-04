<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer
};

use Illuminate\Http\Request;

class ManualPaymentController extends TopUpBaseController
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
        $deposit->method = $request->method;
        $deposit->txnid = $request->txnid;
        $deposit->status = 0;
        $deposit->save();
  
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

        return redirect()->route('user-dashboard')->with('success',__('Your payment needs to verify. we\'ll confirm you soon.'));
  
    }  
}
