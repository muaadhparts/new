<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\Package;
use App\Models\Shipping;
use Illuminate\Http\Request;

class ManualController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'txnid' => 'required',
        ]);
        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $item_amount = $purchase->pay_amount * $purchase->currency_value;
            $purchase->pay_amount = round($item_amount / $purchase->currency_value, 2);
            $purchase->method = $request->method;
            $purchase->txnid = $request->txnid;
            $purchase->payment_status = 'Pending';
            $purchase->save();
            return redirect(route('front.payment.success', 1));
        } else {
            return redirect()->back()->with('unsuccess', 'Something Went Wrong.');
        }
    }
}
