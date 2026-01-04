<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeController extends MembershipPlanBaseController
{

    public function store(Request $request){

        $this->validate($request, [
        'shop_name'   => 'unique:users',
        ],[
            'shop_name.unique' => __('This shop name has already been taken.')
        ]);

        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $data = MerchantPayment::whereKeyword('authorize.net')->first();
        $user = $this->user;

        $item_amount = $membershipPlan->price * $this->curr->value;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Authorize Payment.'));
        }

        $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
        $today = Carbon::now()->format('Y-m-d');

        $item_name = $membershipPlan->title." Plan";
        $item_number = Str::random(4).time();

        $input = $request->all();
        $user->is_merchant = 2;


        $validator = \Validator::make($request->all(),[
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
            $creditCard->setExpirationDate($year.'-'.$month);
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
            if($paydata['sandbox_check'] == 1){
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            }

            else {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }

            if ($response != null) {
                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == "Ok") {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();


                        $user->is_merchant = 2;
                        if(!empty($package))
                        {
                            if($package->membership_plan_id == $request->subs_id)
                            {
                                $newday = strtotime($today);
                                $lastday = strtotime($user->date);
                                $secs = $lastday-$newday;
                                $days = $secs / 86400;
                                $total = $days+$membershipPlan->days;
                                $user->date = date('Y-m-d', strtotime($today.' + '.$total.' days'));
                            }
                            else
                            {
                                $user->date = date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));
                            }
                        }
                        else
                        {
                            $user->date = date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));
                        }
                        $user->mail_sent = 1;
                        $user->update($input);

                        $plan = new UserMembershipPlan;
                        $plan->user_id = $user->id;
                        $plan->membership_plan_id = $membershipPlan->id;
                        $plan->title = $membershipPlan->title;
                        $plan->currency_sign = $this->curr->sign;
                        $plan->currency_code = $this->curr->name;
                        $plan->currency_value = $this->curr->value;
                        $plan->price = $membershipPlan->price * $this->curr->value;
                        $plan->price = $plan->price / $this->curr->value;
                        $plan->days = $membershipPlan->days;
                        $plan->allowed_items = $membershipPlan->allowed_items;
                        $plan->details = $membershipPlan->details;
                        $plan->method = 'Authorize.net';
                        $plan->txnid = $tresponse->getTransId();
                        $plan->status = 1;
                        $plan->save();


                        $data = [
                            'to' => $user->email,
                            'type' => "merchant_accept",
                            'cname' => $user->name,
                            'oamount' => "",
                            'aname' => "",
                            'aemail' => "",
                            'onumber' => "",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendAutoMail($data);

                        return redirect()->route('user-dashboard')->with('success',__('Merchant Account Activated Successfully'));

                    } else {
                        return back()->with('unsuccess', __('Payment Failed.'));
                    }
                    // Or, print errors if the API request wasn't successful

            } else {
                return back()->with('unsuccess', __('Payment Failed.'));
            }

        }
        return back()->with('unsuccess', __('Invalid Payment Details.'));
    }
}
