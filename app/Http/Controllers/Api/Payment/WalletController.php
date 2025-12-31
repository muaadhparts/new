<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WalletController extends Controller
{

    public function store(Request $request)
    {


        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $item_amount = $purchase->pay_amount * $purchase->currency_value;
            $user = User::findOrFail($purchase->user_id);
            if ($purchase->user_id == 0) {
                return redirect()->back()->with('unsuccess', 'Please login to continue');
            } else {
                if ($user->balance < $item_amount) {
                    return redirect()->back()->with('unsuccess', 'You do not have enough balance in your wallet');
                }
            }

            $purchase->pay_amount = round($item_amount / $purchase->currency_value, 2);
            $purchase->method = 'Wallet';
            $purchase->txnid = Str::random(12);
            $purchase->payment_status = 'Completed';
            $purchase->save();

            $user->balance = $user->balance - $item_amount;
            $user->save();



            return redirect(route('front.payment.success', 1));
        } else {
            return redirect()->back()->with('unsuccess', 'Something Went Wrong.');
        }
    }
}
