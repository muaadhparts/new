<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CashOnDeliveryController extends Controller
{

    public function store(Request $request)
    {
        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $item_amount = $purchase->pay_amount * $purchase->currency_value;
            $purchase->pay_amount = round($item_amount / $purchase->currency_value, 2);
            $purchase->method = $request->method;
            $purchase->txnid = Str::random(12);
            $purchase->payment_status = 'Pending';
            $purchase->save();
            return redirect(route('front.payment.success', 1));
        } else {
            return redirect()->back()->with('unsuccess', 'Something Went Wrong.');
        }
    }

}
