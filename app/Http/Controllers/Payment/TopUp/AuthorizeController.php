<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeController extends TopUpBaseController
{

    public function store(Request $request)
    {

        $data = MerchantPayment::whereKeyword('authorize.net')->first();
        $user = $this->user;
        $item_amount = (string)$request->amount;
        $item_name = "TopUp Via Authorize.net";
        $item_number = Str::random(4) . time();

        $curr = $this->curr;
        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Authorize Payment.'));
        }

        $validator = \Validator::make($request->all(), [
            'cardNumber' => 'required',
            'cardCode' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        if ($validator->passes()) {
            /* Create a merchantAuthenticationType object with authentication details retrieved from the constants file */

            $paydata = $data->convertAutoData();
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($paydata['login_id']);
            $merchantAuthentication->setTransactionKey($paydata['txn_key']);

            // Set the transaction's refId
            $refId = 'ref' . time();

            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($request->cardNumber);
            $year = $request->year;
            $month = $request->month;
            $creditCard->setExpirationDate($year . '-' . $month);
            $creditCard->setCardCode($request->cardCode);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create purchase information
            $purchase = new AnetAPI\OrderType();
            $purchase->setInvoiceNumber($item_number);
            $purchase->setDescription($item_name);

            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($item_amount);
            $transactionRequestType->setOrder($purchase);
            $transactionRequestType->setPayment($paymentOne);
            // Assemble the complete transaction request
            $requestt = new AnetAPI\CreateTransactionRequest();
            $requestt->setMerchantAuthentication($merchantAuthentication);
            $requestt->setRefId($refId);
            $requestt->setTransactionRequest($transactionRequestType);

            // Create the controller and get the response
            $controller = new AnetController\CreateTransactionController($requestt);
            if ($paydata['sandbox_check'] == 1) {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            } else {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }


            if ($response != null) {
                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();


                    $user->balance = $user->balance + ($request->amount / $this->curr->value);
                    $user->mail_sent = 1;
                    $user->save();

                    $topUp = new TopUp;
                    $topUp->user_id = $user->id;
                    $topUp->currency = $this->curr->sign;
                    $topUp->currency_code = $this->curr->name;
                    $topUp->currency_value = $this->curr->value;
                    $topUp->amount = $request->amount / $this->curr->value;
                    $topUp->method = 'Authorize.net';
                    $topUp->txnid = $tresponse->getTransId();
                    $topUp->status = 1;
                    $topUp->save();

                    // store in wallet_logs table
                    if ($topUp->status == 1) {
                        $walletLog = new WalletLog;
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

                    // Or, print errors if the API request wasn't successful
                } else {
                    return back()->with('unsuccess', __('Payment Failed.'));
                }
            } else {
                return back()->with('unsuccess', __('Payment Failed.'));
            }
        }
        return back()->with('unsuccess', __('Invalid Payment Details.'));
    }
}
