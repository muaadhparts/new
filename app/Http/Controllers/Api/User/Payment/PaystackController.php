<?php

namespace App\Http\Controllers\Api\User\Payment;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaystackController extends Controller
{

    public function store(Request $request)
    {
        
         if(!$request->has('deposit_number')){
             return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
         }
         
         
        $deposit_number = $request->deposit_number;
        $purchase = Deposit::where('deposit_number',$deposit_number)->first();
   
        $item_amount = $purchase->pay_amount ;
        $purchase['txnid'] = $request->ref_id;
        $purchase->status = 1;
        $purchase->amount = round($item_amount / $purchase->currency_value, 2);
        $purchase->method = "Paystack";
        $purchase->update();

        $user = \App\Models\User::findOrFail($purchase->user_id);
        $user->balance = $user->balance + ($purchase->amount);
        $user->save();

    if ($purchase->status == 1) {
            $transaction = new \App\Models\Transaction;
            $transaction->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
            $transaction->user_id = $purchase->user_id;
            $transaction->amount = $purchase->amount;
            $transaction->user_id = $purchase->user_id;
            $transaction->currency_sign = $purchase->currency;
            $transaction->currency_code = $purchase->currency_code;
            $transaction->currency_value= $purchase->currency_value;
            $transaction->method = $purchase->method;
            $transaction->txnid = $purchase->txnid;
            $transaction->details = 'Payment Deposit';
            $transaction->type = 'plus';
            $transaction->save();
        }
        return redirect(route('user.success',1));
        
    }

}