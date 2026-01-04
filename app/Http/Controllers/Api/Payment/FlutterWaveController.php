<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Payment\Checkout\CheckoutBaseControlller;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FlutterWaveController extends CheckoutBaseControlller
{
    public $public_key;
    private $secret_key;

    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('flutterwave')->first();
        $paydata = $data->convertAutoData();
        $this->public_key = $paydata['public_key'];
        $this->secret_key = $paydata['secret_key'];
    }

    public function store(Request $request)
    {
        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();

        $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
        if ($curr->name != "USD") {
            return redirect()->back()->with('unsuccess', 'Please Select USD Currency For Flutterwave.');
        }

        $item_amount = $purchase->pay_amount * $purchase->currency_value;

        $cancel_url = route('payment.checkout') . "?purchase_number=" . $purchase->purchase_number;
        $notify_url = route('api.flutter.notify');

        Session::put('purchase_data', $purchase);
        Session::put('purchase_payment_id', $purchase['purchase_number']);

        // SET CURL

        $curl = curl_init();

        $amount = $item_amount;
        $currency = $curr->name;
        $txref = $purchase['purchase_number']; // ensure you generate unique references per transaction.
        $PBFPubKey = $this->public_key; // get your public key from the dashboard.
        $redirect_url = $notify_url;
        $payment_plan = ""; // this is only required for recurring payments.

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $amount,
                'customer_email' => $purchase->customer_email,
                'currency' => $currency,
                'txref' => $txref,
                'PBFPubKey' => $PBFPubKey,
                'redirect_url' => $redirect_url,
                'payment_plan' => $payment_plan,
            ]),
            CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache",
            ],
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            // there was an error contacting the rave API
            return redirect($cancel_url)->with('unsuccess', 'Curl returned error: ' . $err);
        }

        $flutterwaveResponse = json_decode($response);

        if (!$flutterwaveResponse->data && !$flutterwaveResponse->data->link) {
            // there was an error from the API
            return redirect($cancel_url)->with('unsuccess', 'API returned error: ' . $flutterwaveResponse->message);
        }

        return redirect($flutterwaveResponse->data->link);
    }

    public function notify(Request $request)
    {

        $input_data = $request->all();

        if ($request->cancelled == "true") {
            return redirect(route('front.payment.success', 0));
        }

        $success_url = route('front.payment.success', 1);
        $cancel_url = route('front.payment.success', 0);

        /** Get the payment ID before session clear **/
        $payment_id = Session::get('purchase_payment_id');

        if (isset($input_data['txref'])) {

            $ref = $payment_id;

            $query = array(
                "SECKEY" => $this->secret_key,
                "txref" => $ref,
            );

            $data_string = json_encode($query);

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $response = curl_exec($ch);

            curl_close($ch);

            $resp = json_decode($response, true);

            if ($resp['status'] = "success") {
                if (!empty($resp['data'])) {

                    $paymentStatus = $resp['data']['status'];
                    $chargeResponsecode = $resp['data']['chargecode'];

                    if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {
                        $purchase = Purchase::where('purchase_number', $payment_id)->firstOrFail();
                        $data['payment_status'] = 'Completed';
                        $data['method'] = 'Flutterwave';
                        $purchase->update($data);
                        return redirect($success_url);
                    }
                }
            }
            return redirect($cancel_url);
        }
        return redirect($cancel_url);
    }
}
