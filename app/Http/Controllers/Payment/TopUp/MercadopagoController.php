<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\Classes\MuaadhMailer;
use App\Models\TopUp;
use App\Models\MerchantPayment;
use App\Models\WalletLog;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MercadoPago;

class MercadopagoController extends TopUpBaseController
{

    public function store(Request $request)
    {

        $data = MerchantPayment::whereKeyword('mercadopago')->first();

        $item_amount = $request->amount;
        $curr = $this->curr;

        $input = $request->all();
        $user = $this->user;
        $item_name = "Deposit Via Mercadopago";

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Mercadopago Payment.'));
        }

        $paydata = $data->convertAutoData();

        $user = $this->user;

        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (float) $item_amount;
        $payment->token = $input['token'];
        $payment->description = $item_name;
        $payment->installments = 1;
        $payment->payer = array(
            "email" => $user->email,
        );
        $payment->save();

        if ($payment->status == 'approved') {

            $user->balance = $user->balance + ($request->deposit_amount / $this->curr->value);
            $user->mail_sent = 1;
            $user->save();

            $deposit = new TopUp;
            $deposit->user_id = $user->id;
            $deposit->currency = $this->curr->sign;
            $deposit->currency_code = $this->curr->name;
            $deposit->currency_value = $this->curr->value;
            $deposit->amount = $request->deposit_amount / $this->curr->value;
            $deposit->method = 'Mercadopago';
            $deposit->txnid = $payment->id;
            $deposit->status = 1;
            $deposit->save();

            // store in wallet_logs table
            if ($deposit->status == 1) {
                $walletLog = new WalletLog;
                $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $walletLog->user_id = $deposit->user_id;
                $walletLog->amount = $deposit->amount;
                $walletLog->user_id = $deposit->user_id;
                $walletLog->currency_sign = $deposit->currency;
                $walletLog->currency_code = $deposit->currency_code;
                $walletLog->currency_value = $deposit->currency_value;
                $walletLog->method = $deposit->method;
                $walletLog->txnid = $deposit->txnid;
                $walletLog->details = 'Payment Deposit';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            $data = [
                'to' => $user->email,
                'type' => "wallet_deposit",
                'cname' => $user->name,
                'damount' => $deposit->amount,
                'wbalance' => $user->balance,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => "",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoMail($data);

            return redirect()->route('user-dashboard')->with('success', __('Balance has been added to your account.'));

        }

        return back()->with('unsuccess', __('Payment Failed.'));

    }
}
