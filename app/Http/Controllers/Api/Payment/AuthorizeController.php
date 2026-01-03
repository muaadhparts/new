<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Validator;

class AuthorizeController extends Controller
{

    public function store(Request $request)
    {

        if (!$request->has('purchase_number')) {
            return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $settings = Muaadhsetting::findOrFail(1);
        $item_name = $settings->title . " Purchase";
        $purchase_number = $request->purchase_number;
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();


        $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
        $item_amount = $purchase->pay_amount;
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'cardNumber' => 'required',
            'cardCode' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        $data = PaymentGateway::whereKeyword('authorize.net')->first();
        $paydata = $data->convertAutoData();
        if ($validator->passes()) {

            /* Create a merchantAuthenticationType object with authentication details retrieved from the constants file */

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
            $purchases->setInvoiceNumber($purchase_number);
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

                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {

                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {

                        $purchase['method'] = $request->method;
                        $purchase['pay_amount'] = round($item_amount / $curr->value, 2);
                        $purchase['txnid'] = $tresponse->getTransId();
                        $purchase['payment_status'] = "Completed";
                        $purchase->update();
                        return redirect(route('front.payment.success', 1));
                    } else {
                        return back()->with('unsuccess', 'Payment Failed.');
                    }
                    // Or, print errors if the API request wasn't successful
                } else {
                    return back()->with('unsuccess', 'Payment Failed.');
                }
            } else {
                return back()->with('unsuccess', 'Payment Failed.');
            }
        }
        return back()->with('unsuccess', 'Invalid Payment Details.');
    }
}
