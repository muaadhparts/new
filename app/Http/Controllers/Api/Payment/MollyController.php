<?php

namespace App\Http\Controllers\Api\Payment;

use Mollie\Laravel\Facades\Mollie;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\Shipping;
use App\Models\Package;
use Illuminate\Http\Request;
use Session;

class MollyController extends Controller
{
 

public function store(Request $request){

     if(!$request->has('purchase_number')){
         return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
     }

    $purchase_number = $request->purchase_number;
    $purchase = Purchase::where('purchase_number',$purchase_number)->firstOrFail();
    $curr = Currency::where('sign','=',$purchase->currency_sign)->firstOrFail();
    
    $available_currency = array(
        'AED',
        'AUD',
        'BGN',
        'BRL',
        'CAD',
        'CHF',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'HKD',
        'HRK',
        'HUF',
        'ILS',
        'ISK',
        'JPY',
        'MXN',
        'MYR',
        'NOK',
        'NZD',
        'PHP',
        'PLN',
        'RON',
        'RUB',
        'SEK',
        'SGD',
        'THB',
        'TWD',
        'USD',
        'ZAR'
        );
        if(!in_array($curr->name,$available_currency))
        {
        return redirect()->back()->with('unsuccess','Invalid Currency For Molly Payment.');
        }


        $input = $request->all();


        $settings = Muaadhsetting::findOrFail(1);
        $shipping = Shipping::findOrFail($request->shipping)->price * $purchase->currency_value;
        $packeging = Package::findOrFail($request->packeging)->price * $purchase->currency_value;
        $input['shipping'] = $shipping;
        $input['packeging'] = $packeging;
        $charge = $shipping + $packeging;
        $settings = Muaadhsetting::findOrFail(1);

        $item_amount = round($purchase->pay_amount / $curr->value, 2);
        $item_amount += $charge;


        $purchase['item_name'] = $settings->title." Purchase";
        $purchase['item_amount'] = $item_amount;


        $data['return_url'] = route('payment.checkout')."?purchase_number=".$purchase->purchase_number;
        $data['cancel_url'] = route('payment.checkout')."?purchase_number=".$purchase->purchase_number;

        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $curr->name,
                'value' => ''.sprintf('%0.2f', $purchase['item_amount']).'', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $settings->title." Purchase" ,
            'redirectUrl' => route('api.molly.notify'),
            ]);

        Session::put('payment_id',$payment->id);
        Session::put('molly_data',$purchase->id);
        Session::put('paypal_data',$input);
        $payment = Mollie::api()->payments()->get($payment->id);

        return redirect($payment->getCheckoutUrl(), 303);
 }



public function notify(Request $request){

        $paypal_data = Session::get('paypal_data');
        $purchase = Purchase::findOrFail(Session::get('molly_data'));
        $cancel_url = route('payment.checkout')."?purchase_number=".$purchase->purchase_number;
        $payment = Mollie::api()->payments()->get(Session::get('payment_id'));
        if($payment->status == 'paid'){
        $purchase['txnid'] = $payment->id;
        $purchase['method'] = 'Molly';
        $purchase->packing_cost = $paypal_data['packeging'];
        $purchase->shipping_cost = $paypal_data['shipping'];
        $purchase['payment_status'] = 'Completed';
        $purchase->update();
         return redirect(route('front.payment.success',1));
        }
        else {
            return redirect($cancel_url);
        }
}



}
