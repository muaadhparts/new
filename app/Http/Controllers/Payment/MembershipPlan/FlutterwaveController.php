<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\Http\Controllers\Controller;
use App\Models\MerchantPayment;
use App\Models\UserMembershipPlan;
use Illuminate\Http\Request;

class FlutterwaveController extends Controller
{
    public $public_key;
    private $secret_key;

    public function __construct()
    {
        $data = MerchantPayment::whereKeyword('flutterwave')->first();
        if ($data) {
            $paydata = $data->convertAutoData();
            $this->public_key = $paydata['public_key'] ?? '';
            $this->secret_key = $paydata['secret_key'] ?? '';
        }
    }

    /**
     * Handle Flutterwave payment notification for membership plans
     */
    public function notify(Request $request)
    {
        $input = $request->all();

        if ($request->cancelled == "true") {
            return redirect()->route('user-dashboard')->with('unsuccess', __('Payment Cancelled!'));
        }

        if (!isset($input['txref'])) {
            return redirect()->route('user-dashboard')->with('unsuccess', __('Invalid Transaction!'));
        }

        $ref = $input['txref'];
        $query = [
            "SECKEY" => $this->secret_key,
            "txref" => $ref
        ];

        $data_string = json_encode($query);
        $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode($response, true);

        if ($resp['status'] == "success") {
            $paymentStatus = $resp['data']['status'];
            $chargeResponsecode = $resp['data']['chargecode'];

            if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {
                // Find the membership plan by transaction reference
                $membership = UserMembershipPlan::where('flutter_id', $input['txref'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($membership) {
                    $membership->txnid = $resp['data']['txid'];
                    $membership->status = 1;
                    $membership->save();

                    return redirect()->route('user-dashboard')
                        ->with('success', __('Membership plan activated successfully!'));
                }
            }
        }

        return redirect()->route('user-dashboard')
            ->with('unsuccess', __('Payment verification failed!'));
    }
}
