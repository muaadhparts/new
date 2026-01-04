<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\UserMembershipPlan
};

use Illuminate\Http\Request;

class ManualPaymentController extends MembershipPlanBaseController
{
    public function store(Request $request){
        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);
        $user = $this->user;

        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $input = $request->all();
        $user->update($input);

        $plan = new UserMembershipPlan;
        $plan->user_id = $user->id;
        $plan->membership_plan_id = $membershipPlan->id;
        $plan->title = $membershipPlan->title;
        $plan['currency_sign'] = $this->curr->sign;
        $plan['currency_code'] = $this->curr->name;
        $plan['currency_value'] = $this->curr->value;
        $plan['price'] = $membershipPlan->price * $this->curr->value;
        $plan['price'] = $plan['price'] / $this->curr->value;
        $plan->days = $membershipPlan->days;
        $plan->allowed_items = $membershipPlan->allowed_items;
        $plan->details = $membershipPlan->details;
        $plan->method = $request->method;
        $plan->txnid = $request->txnid;
        $plan->status = 0;
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
    }
}
