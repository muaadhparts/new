<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\TopUp;
use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SslController extends Controller
{
    public function store(Request $request)
    {
        $data = MerchantPayment::whereKeyword('sslcommerz')->first();
        if (!$request->has('deposit_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $deposit_number = $request->deposit_number;
        $purchase = TopUp::where('deposit_number', $deposit_number)->first();
        $curr = Currency::where('name', '=', $purchase->currency_code)->first();
        if ($curr->name != "BDT") {
            return redirect()->back()->with('unsuccess', 'Please Select BDT Currency For Sslcommerz .');
        }

        $item_amount = $purchase->amount * $purchase->currency_value;
        $txnid = "SSLCZ_TXN_" . uniqid();
        $purchase->amount = round($item_amount / $purchase->currency_value, 2);
        $purchase['method'] = $request->method;
        $purchase['txnid'] = $txnid;

        $purchase->update();
        $paydata = $data->convertAutoData();

        $post_data = array();
        $post_data['store_id'] = $paydata['store_id'];
        $post_data['store_passwd'] = $paydata['store_password'];
        $post_data['total_amount'] = $item_amount;
        $post_data['currency'] = $curr->name;
        $post_data['tran_id'] = $txnid;
        $post_data['success_url'] = action('Api\User\Payment\SslController@notify');
        $post_data['fail_url'] = route('user.deposit.send', $purchase->deposit_number);
        $post_data['cancel_url'] = route('user.deposit.send', $purchase->deposit_number);
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # CUSTOMER INFORMATION
        // $post_data['cus_name'] = $purchase['customer_name'];
        // $post_data['cus_email'] = $purchase['customer_email'];
        // $post_data['cus_add1'] = $purchase['customer_address'];
        // $post_data['cus_city'] = $purchase['customer_city'];
        // $post_data['cus_state'] = '';
        // $post_data['cus_postcode'] = $purchase['customer_zip'];
        // $post_data['cus_country'] = $purchase['customer_country'];
        // $post_data['cus_phone'] = '';
        // $post_data['cus_fax'] = '';

        # REQUEST SEND TO SSLCOMMERZ
        if ($paydata['sandbox_check'] == 1) {
            $direct_api_url = "https://sandbox.sslcommerz.com/gwprocess/v3/api.php";
        } else {
            $direct_api_url = "https://securepay.sslcommerz.com/gwprocess/v3/api.php";
        }
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC

        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close($handle);
            return redirect()->back()->with('unsuccess', "FAILED TO CONNECT WITH SSLCOMMERZ API");
            exit;
        }

        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true);

        if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {

            # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
            # echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
            echo "<meta http-equiv='refresh' content='0;url=" . $sslcz['GatewayPageURL'] . "'>";
            # header("Location: ". $sslcz['GatewayPageURL']);
            exit;
        } else {
            return redirect()->back()->with('unsuccess', "JSON Data parsing error!");

        }

    }

    public function notify(Request $request)
    {

        $input = $request->all();
        $purchase = TopUp::where('txnid', $input['tran_id'])->first();
        $user = \App\Models\User::findOrFail($purchase->user_id);
        $user->balance = $user->balance + ($purchase->amount);
        $user->save();
        if ($input['status'] == 'VALID') {
            $purchase->method = 'Stripe';
            $purchase->status = 1;
            $purchase->update();

            // store in wallet_logs table
            if ($purchase->status == 1) {
                $walletLog = new \App\Models\WalletLog;
                $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $walletLog->user_id = $purchase->user_id;
                $walletLog->amount = $purchase->amount;
                $walletLog->user_id = $purchase->user_id;
                $walletLog->currency_sign = $purchase->currency;
                $walletLog->currency_code = $purchase->currency_code;
                $walletLog->currency_value = $purchase->currency_value;
                $walletLog->method = $purchase->method;
                $walletLog->txnid = $purchase->txnid;
                $walletLog->details = 'Payment Deposit';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            return redirect(route('user.success', 1));
        } else {
            return redirect(route('user.success', 0));
        }
    }
}
