<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\Shipping;
use App\Models\Package;

class PaystackController extends Controller
{

    public function store(Request $request)
    {

        if (!$request->has('purchase_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }
        if (!$request->ref_id) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
        $item_amount = $purchase->pay_amount;
        $purchase['txnid'] = $request->ref_id;
        $purchase->payment_status = 'Completed';
        $purchase->pay_amount = round($item_amount / $purchase->currency_value, 2);
        $purchase->method = "Paystack";
        $purchase->update();
        return redirect(route('front.payment.success', 1));
    }
}
