<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Traits\Paytm,
    Models\TopUp,
    Models\WalletLog,
	Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Illuminate\{
	Http\Request,
	Support\Facades\Session
};
use Illuminate\Support\Str;


class PaytmController extends TopUpBaseController
{

    use Paytm;
    public function store(Request $request)
    {

     $data = MerchantPayment::whereKeyword('paytm')->first();   
     $user = $this->user;
     
     $item_amount = $request->amount;
     $curr = $this->curr;

    $supported_currency = json_decode($data->currency_id,true);
    if(!in_array($curr->id,$supported_currency)){
        return redirect()->back()->with('unsuccess',__('Invalid Currency For Paytm Payment.'));
    }
     
     $return_url = route('topup.payment.return');
     $cancel_url = route('topup.payment.cancle');
     $notify_url = route('topup.paytm.notify');
     $item_name = "TopUp via Paytm";
     $item_number = Str::random(4).time();

     $topUp = new TopUp;
     $topUp->user_id = $user->id;
     $topUp->currency = $this->curr->sign;
     $topUp->currency_code = $this->curr->name;
     $topUp->amount = $request->amount / $this->curr->value;
     $topUp->currency_value = $this->curr->value;
     $topUp->method = 'Paytm';
     $topUp->save();

        Session::put('item_number',$user->id); 

  
        $data_for_request = $this->handlePaytmRequest( $item_number, $item_amount, 'topup');
        $paytm_txn_url = 'https://securegw-stage.paytm.in/theia/processTransaction';
        $paramList = $data_for_request['paramList'];
        $checkSum = $data_for_request['checkSum'];
        return view( 'frontend.paytm-merchant-form', compact( 'paytm_txn_url', 'paramList', 'checkSum' ) );
    }


	public function notify( Request $request ) {

		$purchase_id = $request['ORDERID'];

     

		if ( 'TXN_SUCCESS' === $request['STATUS'] ) {
		$transaction_id = $request['TXNID'];

        $topUp = TopUp::where('user_id','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
        $user = \App\Models\User::findOrFail($topUp->user_id);
        $user->balance = $user->balance + ($topUp->amount);
        $user->save();
        $topUp->txnid = $transaction_id;
        $topUp->status = 1;
        $topUp->save();

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

		return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account.'));
    }else{
        $topUp = TopUp::where('user_id','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
        $topUp->delete();
    }
	return redirect()->back()->with('unsuccess',__('Payment Cancelled.'));
    }
}
