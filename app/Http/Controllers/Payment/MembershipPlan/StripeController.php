<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class StripeController extends MembershipPlanBaseController
{

    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('stripe')->first();
        $paydata = $data->convertAutoData();
        Config::set('services.stripe.key', $paydata['key']);
        Config::set('services.stripe.secret', $paydata['secret']);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
        ], [
            'shop_name.unique' => __('This shop name has already been taken.')
        ]);

        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $data = MerchantPayment::whereKeyword('stripe')->first();
        $user = $this->user;

        $item_amount = $membershipPlan->price * $this->curr->value;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Stripe Payment.'));
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
        $plan['method'] = 'Stripe';


        try {
            $stripe_secret_key = Config::get('services.stripe.secret');
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            $checkout_session = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "success_url" => route('user.membership.stripe.notify') . '?session_id={CHECKOUT_SESSION_ID}',
                "cancel_url" => route('user.payment.cancle'),
                "customer_email" => $user->email,
                "locale" => "auto",
                "line_items" => [
                    [
                        "quantity" => 1,
                        "price_data" => [
                            "currency" => $this->curr->name,
                            "unit_amount" => $item_amount * 100,
                            "product_data" => [
                                "name" => $this->gs->title . ' ' . $membershipPlan->title . ' Plan',
                            ]
                        ]
                    ],
                ]
            ]);

            Session::put('membership_plan_data', $plan);
            return redirect($checkout_session->url);
        } catch (Exception $e) {
            return back()->with('unsuccess', $e->getMessage());
        }
    }


    public function notify(Request $request)
    {

        $plandata = Session::get('membership_plan_data');
        $user = $this->user;
        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($request->session_id);

        if ($response->status == 'complete') {

            $purchase = new UserMembershipPlan;
            $purchase->user_id = $plandata['user_id'];
            $purchase->membership_plan_id = $plandata['membership_plan_id'];
            $purchase->title = $plandata['title'];
            $purchase->currency_sign = $this->curr->sign;
            $purchase->currency_code = $this->curr->name;
            $purchase->currency_value = $this->curr->value;
            $purchase->price = $plandata['price'];
            $purchase->days = $plandata['days'];
            $purchase->allowed_items = $plandata['allowed_items'];
            $purchase->details = $plandata['details'];
            $purchase->method = $plandata['method'];
            $purchase->txnid = $response->payment_intent;
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
            Session::forget('membership_plan_data');
            return redirect()->route('user-dashboard')->with('success', __('Membership Plan Activated Successfully'));
        }
    }
}
