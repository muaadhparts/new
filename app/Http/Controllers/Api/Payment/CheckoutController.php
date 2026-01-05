<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Front\FrontBaseController;
use App\Models\Currency;
use App\Models\TopUp;
use App\Models\Purchase;
use App\Models\MerchantPayment;
use DB;
use Illuminate\Http\Request;

class CheckoutController extends FrontBaseController
{

    public function loadpayment(Request $request, $slug1, $slug2)
    {
        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
            $payment = $slug1;
            $pay_id = $slug2;
            $gateway = '';
            if ($pay_id != 0) {
                $gateway = MerchantPayment::findOrFail($pay_id);
            }
            return view('payment.load.payment', compact('payment', 'pay_id', 'gateway', 'curr'));
        }
    }

    public function topuploadpayment(Request $request, $slug1, $slug2)
    {

        if ($request->has('topup_number')) {
            $topupNumber = $request->topup_number;
            $topUp = TopUp::where('topup_number', $topupNumber)->firstOrFail();
            $curr = Currency::where('name', $topUp->currency_code)->firstOrFail();
            $payment = $slug1;
            $pay_id = $slug2;
            $gateway = '';
            if ($pay_id != 0) {
                $gateway = MerchantPayment::findOrFail($pay_id);
            }
            return view('payment.load.payment', compact('payment', 'pay_id', 'gateway', 'curr'));
        }
    }

    public function checkout(Request $request)
    {
        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $package_data = DB::table('packages')->where('user_id', '=', 0)->get();
            $shipping_data = DB::table('shippings')->where('user_id', '=', 0)->get();

            $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
            $gateways = MerchantPayment::scopeHasGateway($curr->id);

            $paystack = MerchantPayment::whereKeyword('paystack')->first();
            $paystackData = $paystack->convertAutoData();

            if ($purchase->payment_status == 'Pending') {
                return view('payment.checkout', compact('purchase', 'package_data', 'shipping_data', 'gateways', 'paystackData'));
            }
        }
    }
}
