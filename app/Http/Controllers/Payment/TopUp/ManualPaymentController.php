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
  
        $topUp = new TopUp;
        $topUp->user_id = $user->id;
        $topUp->currency = $curr->sign;
        $topUp->currency_code = $curr->name;
        $topUp->currency_value = $curr->value;
        $topUp->amount = $request->amount / $curr->value;
        $topUp->method = $request->method;
        $topUp->txnid = $request->txnid;
        $topUp->status = 0;
        $topUp->save();

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

        return redirect()->route('user-dashboard')->with('success',__('Your payment needs to verify. we\'ll confirm you soon.'));
  
    }  
}
