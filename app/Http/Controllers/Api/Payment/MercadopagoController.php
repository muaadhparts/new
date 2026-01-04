<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\Package;
use App\Models\MerchantPayment;
use App\Models\Shipping;
use Illuminate\Http\Request;
use MercadoPago;

class MercadopagoController extends Controller
{

    public function store(Request $request)
    {
        $input = $request->all();
        if (!$request->has('purchase_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }
        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
        $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
        if ($curr->name != "USD") {
            return redirect()->back()->with('unsuccess', 'Please Select USD Currency For Stripe.');
        }
        $data = MerchantPayment::whereKeyword('mercadopago')->first();
        $item_amount = $purchase->pay_amount * $purchase->currency_value;
        $paydata = $data->convertAutoData();

        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (string) $item_amount;
        $payment->token = $input['token'];
        $payment->description = 'MercadoPago Payment';
        $payment->installments = 1;
        $payment->payer = array(
            "email" => $purchase['customer_email'],
        );
        $payment->save();

        if ($payment->status == 'approved') {
            $purchase->txnid = $payment->id;
            $purchase->method = "MercadoPago";
            $purchase->payment_status = 'Completed';
            $purchase->save();
            return redirect(route('front.payment.success', 1));
        }
        return redirect(route('front.payment.success', 0));
    }
}
