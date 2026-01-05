<?php

namespace App\Http\Controllers\Api\User\Payment;
use App\Http\Controllers\Controller;
use App\Models\TopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaystackController extends Controller
{

    public function store(Request $request)
    {
        
         if(!$request->has('topup_number')){
             return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
         }


        $topupNumber = $request->topup_number;
        $purchase = TopUp::where('topup_number',$topupNumber)->first();
   
        $item_amount = $purchase->pay_amount ;
        $purchase['txnid'] = $request->ref_id;
        $purchase->status = 1;
        $purchase->amount = round($item_amount / $purchase->currency_value, 2);
        $purchase->method = "Paystack";
        $purchase->update();

        $user = \App\Models\User::findOrFail($purchase->user_id);
        $user->balance = $user->balance + ($purchase->amount);
        $user->save();

    // store in wallet_logs table
    if ($purchase->status == 1) {
            $walletLog = new \App\Models\WalletLog;
            $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
            $walletLog->user_id = $purchase->user_id;
            $walletLog->amount = $purchase->amount;
            $walletLog->user_id = $purchase->user_id;
            $walletLog->currency_sign = $purchase->currency;
            $walletLog->currency_code = $purchase->currency_code;
            $walletLog->currency_value= $purchase->currency_value;
            $walletLog->method = $purchase->method;
            $walletLog->txnid = $purchase->txnid;
            $walletLog->details = 'Wallet TopUp';
            $walletLog->type = 'plus';
            $walletLog->save();
        }
        return redirect(route('user.success',1));
        
    }

}