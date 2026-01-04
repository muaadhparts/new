<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\User,
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};

use Illuminate\{
    Http\Request,
    Support\Facades\Session
};
use Omnipay\Omnipay;


use Illuminate\Support\Str;
use Carbon\Carbon;

class PaypalController extends MembershipPlanBaseController
{
    public $_api_context;
    public $gateway;
    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
        ], [
            'shop_name.unique' => __('This shop name has already been taken.')
        ]);

        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $data = MerchantPayment::whereKeyword('paypal')->first();
        $user = $this->user;

        $item_amount = $membershipPlan->price * $this->curr->value;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Paypal Payment.'));
        }

        $plan['user_id'] = $user->id;
        $plan['membership_plan_id'] = $membershipPlan->id;
        $plan['title'] = $membershipPlan->title;
        $plan['currency_sign'] = $this->curr->sign;
        $plan['currency_code'] = $this->curr->name;
        $plan['currency_value'] = $this->curr->value;
        $plan['price'] = $membershipPlan->price * $this->curr->value;
        $plan['price'] = $plan['price'] / $this->curr->value;
        $plan['days'] = $membershipPlan->days;
        $plan['allowed_items'] = $membershipPlan->allowed_items;
        $plan['details'] = $membershipPlan->details;
        $plan['method'] = 'Paypal';

        $purchase['item_name'] = $membershipPlan->title . " Plan";
        $purchase['item_number'] = Str::random(4) . time();
        $purchase['item_amount'] = $item_amount;
        $cancel_url = route('user.payment.cancle');
        $notify_url = route('user.membership.paypal.notify');



        try {
            $response = $this->gateway->purchase(array(
                'amount' => $item_amount,
                'currency' => $this->curr->name,
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

            if ($response->isRedirect()) {
                Session::put('paypal_data', $plan);
                if ($response->redirect()) {

                    return redirect($response->redirect());
                }
            } else {
                return redirect()->back()->with('unsuccess', $response->getMessage());
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('unsuccess', $th->getMessage());
        }
    }

    public function notify(Request $request)
    {

        $paypal_data = Session::get('paypal_data');
        $success_url = route('user.payment.return');
        $cancel_url = route('user.payment.cancle');
        $input = $request->all();


        $responseData = $request->all();
        if (empty($responseData['PayerID']) || empty($responseData['token'])) {
            return [
                'status' => false,
                'message' => __('Unknown error occurred'),
            ];
        }
        $purchaseRequest = $this->gateway->completePurchase(array(
            'payer_id' => $responseData['PayerID'],
            'transactionReference' => $responseData['paymentId'],
        ));
        $response = $purchaseRequest->send();


        if ($response->isSuccessful()) {

            $purchase = new UserMembershipPlan;
            $purchase->user_id = $paypal_data['user_id'];
            $purchase->membership_plan_id = $paypal_data['membership_plan_id'];
            $purchase->title = $paypal_data['title'];
            $purchase->currency_sign = $this->curr->sign;
            $purchase->currency_code = $this->curr->name;
            $purchase->currency_value = $this->curr->value;
            $purchase->price = $paypal_data['price'];
            $purchase->days = $paypal_data['days'];
            $purchase->allowed_items = $paypal_data['allowed_items'];
            $purchase->details = $paypal_data['details'];
            $purchase->method = $paypal_data['method'];
            $purchase->txnid = $response->getData()['wallet_logs'][0]['related_resources'][0]['sale']['id'];
            $purchase->status = 1;

            $user = User::findOrFail($purchase->user_id);
            $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
            $membershipPlan = MembershipPlan::findOrFail($purchase->membership_plan_id);

            $today = Carbon::now()->format('Y-m-d');
            $user->is_merchant = 2;
            if (!empty($package)) {
                if ($package->membership_plan_id == $purchase->membership_plan_id) {
                    $newday = strtotime($today);
                    $lastday = strtotime($user->date);
                    $secs = $lastday - $newday;
                    $days = $secs / 86400;
                    $total = $days + $membershipPlan->days;
                    $input['date'] = date('Y-m-d', strtotime($today . ' + ' . $total . ' days'));
                } else {
                    $input['date'] = date('Y-m-d', strtotime($today . ' + ' . $membershipPlan->days . ' days'));
                }
            } else {

                $input['date'] = date('Y-m-d', strtotime($today . ' + ' . $membershipPlan->days . ' days'));
            }

            $input['mail_sent'] = 1;
            $user->update($input);
            $purchase->save();

            $maildata = [
                'to' => $user->email,
                'type' => "merchant_accept",
                'cname' => $user->name,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => "",
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoMail($maildata);

            Session::forget('payment_id');
            Session::forget('molly_data');
            Session::forget('user_data');
            Session::forget('order_data');

            return redirect($success_url);
        } else {
            return redirect($cancel_url);
        }
    }
}
