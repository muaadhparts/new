<?php

namespace App\Http\Controllers\Api\Payment;

use App\Classes\Instamojo;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\Package;
use App\Models\MerchantPayment;
use App\Models\Shipping;
use Illuminate\Http\Request;

class InstamojoController extends Controller
{

    public function store(Request $request)
    {

        if (!$request->has('purchase_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
        $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();

        if ($curr->name != "INR") {
            return redirect()->back()->with('unsuccess', 'Please Select INR Currency For Instamojo.');
        }

        $settings = Muaadhsetting::findOrFail(1);
        $item_name = $settings->title . " Purchase";
        $user_email = $purchase->customer_email;

        $item_amount = round($purchase->pay_amount * $purchase->currency_value, 2);

        $notify_url = action('Api\Payment\InstamojoController@notify');
        $data = MerchantPayment::whereKeyword('instamojo')->first();

        $paydata = $data->convertAutoData();
        if($paydata['sandbox_check'] == 1){
            $api = new Instamojo($paydata['key'], $paydata['token'], 'https://test.instamojo.com/api/1.1/');
            }
            else {
            $api = new Instamojo($paydata['key'], $paydata['token']);
            }

        try {
            $response = $api->paymentRequestCreate(array(
                "purpose" => $item_name,
                "amount" => $item_amount,
                "send_email" => true,
                "email" => $user_email,
                "redirect_url" => $notify_url,
            ));

            $redirect_url = $response['longurl'];
            $purchase->pay_id = $purchase['pay_id'] = $response['id'];
            $purchase['pay_amount'] = round($item_amount / $purchase->currency_value, 2);
            $purchase['method'] = $request->method;
            $purchase->update();
            return redirect($redirect_url);
        } catch (Exception $e) {
            print('Error: ' . $e->getMessage());
        }

    }

    public function notify(Request $request)
    {

        $data = $request->all();

        $purchase = Purchase::where('pay_id', '=', $data['payment_request_id'])->first();
        $cancel_url = route('payment.checkout') . "?purchase_number=" . $purchase->purchase_number;

        if (isset($purchase)) {
            $data['txnid'] = $data['payment_id'];
            $data['payment_status'] = 'Completed';
            $purchase->update($data);
            return redirect(route('front.payment.success', 1));
        }
        return $cancel_url;
    }

}
