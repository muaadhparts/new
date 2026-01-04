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
     $item_name = "Deposit via Paytm";
     $item_number = Str::random(4).time();

     $deposit = new TopUp;
     $deposit->user_id = $user->id;
     $deposit->currency = $this->curr->sign;
     $deposit->currency_code = $this->curr->name;
     $deposit->amount = $request->amount / $this->curr->value;
     $deposit->currency_value = $this->curr->value;
     $deposit->method = 'Paytm';
     $deposit->save();

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

        $deposit = TopUp::where('user_id','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
        $user = \App\Models\User::findOrFail($deposit->user_id);
        $user->balance = $user->balance + ($deposit->amount);
        $user->save();
        $deposit->txnid = $transaction_id;
        $deposit->status = 1;
        $deposit->save();

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

		return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account.'));
    }else{
        $deposit = TopUp::where('user_id','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
        $deposit->delete();
    }
	return redirect()->back()->with('unsuccess',__('Payment Cancelled.'));
    }
}
