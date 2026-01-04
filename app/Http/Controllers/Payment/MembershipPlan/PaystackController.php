<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\Muaadhsetting,
    Models\UserMembershipPlan
};

use Carbon\Carbon;
use Illuminate\Http\Request;

class PaystackController extends MembershipPlanBaseController
{

    public function store(Request $request)
    {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
        ], [
            'shop_name.unique' => __('This shop name has already been taken.')
        ]);
        $user = $this->user;
        $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $settings = Muaadhsetting::findOrFail(1);
        $today = Carbon::now()->format('Y-m-d');
        $input = $request->all();
        $user->is_merchant = 2;
        if (!empty($package)) {
            if ($package->membership_plan_id == $request->subs_id) {
                $newday = strtotime($today);
                $lastday = strtotime($user->date);
                $secs = $lastday - $newday;
                $days = $secs / 86400;
                $total = $days + $membershipPlan->days;
                $user->date = date('Y-m-d', strtotime($today . ' + ' . $total . ' days'));
            } else {
                $user->date = date('Y-m-d', strtotime($today . ' + ' . $membershipPlan->days . ' days'));
            }
        } else {
            $user->date = date('Y-m-d', strtotime($today . ' + ' . $membershipPlan->days . ' days'));
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
        $plan->price = $membershipPlan->price;
        $plan->days = $membershipPlan->days;
        $plan->allowed_items = $membershipPlan->allowed_items;
        $plan->details = $membershipPlan->details;
        $plan->method = 'Paystack';
        $plan->txnid = $request->txnid;

        $plan->status = 1;
        $plan->save();
        if ($settings->is_smtp == 1) {
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
        } else {
            $headers = "From: " . $settings->from_name . "<" . $settings->from_email . ">";
            mail($user->email, 'Your Merchant Account Activated', 'Your Merchant Account Activated Successfully. Please Login to your account and build your own shop.', $headers);
        }

        return redirect()->route('user-dashboard')->with('success', __('Merchant Account Activated Successfully'));
    }
}
