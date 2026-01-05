<?php

namespace App\Http\Controllers\User;

use App\{
  Models\WalletLog,
  Models\MerchantPayment
};
use App\Models\Currency;
use App\Models\TopUp;

class TopUpController extends UserBaseController
{
    public function index() {
      return view('user.top-up.index');
    }

    public function walletLogs() {
      return view('user.wallet-logs');
    }

    public function transhow($id) {
      $data = WalletLog::find($id);
      return view('load.wallet-log-details',compact('data'));
    }

    public function create() {
      $data['curr'] = $this->curr;
      $data['gateway']  = MerchantPayment::whereTopup(1)->where('currency_id', 'like', "%\"{$this->curr->id}\"%")->latest('id')->get();
      $paystackData = MerchantPayment::whereKeyword('paystack')->first();
      $data['paystack'] = $paystackData->convertAutoData();
      return view('user.top-up.create', $data);
    }


    public function paycancle(){
      return redirect()->back()->with('unsuccess',__('Payment Cancelled.'));
    }

    public function payreturn(){
      return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account.'));
   }


   function sendTopUp($number){
    $topUp = TopUp::where('topup_number',$number)->first();

    $curr = Currency::where('name', '=', $topUp->currency_code)->firstOrFail();
    $gateways = MerchantPayment::scopeHasGateway($curr->id);
    $paystack = MerchantPayment::whereKeyword('paystack')->first();
    $paystackData = $paystack->convertAutoData();

    if($topUp->status == 1){
        return response()->json(['status'=>false,'data'=>[],'error'=>"Top Up Already Added."]);
    }
    return view('user.top-up.payment',compact('topUp','gateways','paystackData'));
}

}
