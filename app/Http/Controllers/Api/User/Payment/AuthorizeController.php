<?php

namespace App\Http\Controllers\Api\User\Payment;

use App\Models\Muaadhsetting;
use App\Models\Deposit;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Validator;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Models\Transaction;
use Illuminate\Support\Str;

class AuthorizeController extends Controller
{

    public function store(Request $request)
    {
        $data = PaymentGateway::whereKeyword('authorize.net')->first();
        if (!$request->has('deposit_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $settings = Muaadhsetting::findOrFail(1);
        $item_name = $settings->title . " Deposit";
        $deposit_number = $request->deposit_number;
        $purchase = Deposit::where('deposit_number', $deposit_number)->first();

        $item_amount = $purchase->amount;

        $validator = Validator::make($request->all(), [
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
            $creditCard->setCardNumber(str_replace(' ', '', $request->cardNumber));
            $year = $request->year;
            $month = $request->month;
            $creditCard->setExpirationDate($year . '-' . $month);
            $creditCard->setCardCode($request->cardCode);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create purchase information
            $purchases = new AnetAPI\OrderType();
            $purchases->setInvoiceNumber($deposit_number);
            $purchases->setDescription($item_name);

            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($item_amount);
            $transactionRequestType->setOrder($purchases);
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
                $tresponse = $response->getTransactionResponse();

                // Check to see if the API request was successfully received and acted upon
                if ($tresponse->getresponseCode() == 1) {


                    $user = \App\Models\User::findOrFail($purchase->user_id);
                    $user->balance = $user->balance + ($purchase->amount);
                    $user->save();

                    $purchase['method'] = $request->method;
                    $purchase['txnid'] = $tresponse->getTransId();
                    $purchase['status'] = 1;
                    $purchase->update();

                    // store in transaction table
                    if ($purchase->status == 1) {
                        $transaction = new Transaction;
                        $transaction->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                        $transaction->user_id = $purchase->user_id;
                        $transaction->amount = $purchase->amount;
                        $transaction->user_id = $purchase->user_id;
                        $transaction->currency_sign = $purchase->currency;
                        $transaction->currency_code = $purchase->currency_code;
                        $transaction->currency_value = $purchase->currency_value;
                        $transaction->method = $purchase->method;
                        $transaction->txnid = $purchase->txnid;
                        $transaction->details = 'Payment Deposit';
                        $transaction->type = 'plus';
                        $transaction->save();
                    }

                    return redirect(route('user.success', 1));

                    // Or, print errors if the API request wasn't successful
                } else {
                    return redirect(route('user.success', 0));
                }
            } else {
                return redirect(route('user.success', 0));
            }
        }
        return redirect(route('user.success', 0));
    }
}
