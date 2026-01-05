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
        $item_name = "TopUp Via Mercadopago";

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

            $user->balance = $user->balance + ($request->topup_amount / $this->curr->value);
            $user->mail_sent = 1;
            $user->save();

            $topUp = new TopUp;
            $topUp->user_id = $user->id;
            $topUp->currency = $this->curr->sign;
            $topUp->currency_code = $this->curr->name;
            $topUp->currency_value = $this->curr->value;
            $topUp->amount = $request->topup_amount / $this->curr->value;
            $topUp->method = 'Mercadopago';
            $topUp->txnid = $payment->id;
            $topUp->status = 1;
            $topUp->save();

            // store in wallet_logs table
            if ($topUp->status == 1) {
                $walletLog = new WalletLog;
                $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                $walletLog->user_id = $topUp->user_id;
                $walletLog->amount = $topUp->amount;
                $walletLog->user_id = $topUp->user_id;
                $walletLog->currency_sign = $topUp->currency;
                $walletLog->currency_code = $topUp->currency_code;
                $walletLog->currency_value = $topUp->currency_value;
                $walletLog->method = $topUp->method;
                $walletLog->txnid = $topUp->txnid;
                $walletLog->details = 'Wallet TopUp';
                $walletLog->type = 'plus';
                $walletLog->save();
            }

            $data = [
                'to' => $user->email,
                'type' => "wallet_topup",
                'cname' => $user->name,
                'damount' => $topUp->amount,
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
