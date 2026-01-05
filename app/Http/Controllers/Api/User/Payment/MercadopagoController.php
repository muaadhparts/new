<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Http\Controllers\Controller;
use App\Models\TopUp;
use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use MercadoPago;

class MercadopagoController extends Controller
{

    public function store(Request $request)
    {
        $topUp = TopUp::where('topup_number', $request->topup_number)->first();
        $input = $request->all();
        $user = \App\Models\User::findOrFail($topUp->user_id);
        $data = MerchantPayment::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();

        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (string) $topUp->amount;
        $payment->token = $input['token'];
        $payment->description = 'MercadoPago TopUp';
        $payment->installments = 1;
        $payment->payer = array(
            "email" => $user['email'],
        );
        $payment->save();


        if ($payment->status == 'approved') {
            $user->balance = $user->balance + ($topUp->amount);
            $user->save();
            $topUp['status'] = 1;
            $topUp['method'] = 'Mercadopago';
            $topUp['txnid'] = $payment->id;
            $topUp->update();
            return redirect(route('user.success', 1));
        }
        return redirect(route('user.success', 0));

    }

}
