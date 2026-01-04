<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\User,
    Classes\Instamojo,
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class InstamojoController extends MembershipPlanBaseController
{

 public function store(Request $request){


    $membershipPlan = MembershipPlan::findOrFail($request->subs_id);

        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);

            $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
            $data = MerchantPayment::whereKeyword('instamojo')->first();
            $user = $this->user;

            $item_amount = $membershipPlan->price * $this->curr->value;
            $curr = $this->curr;

            $supported_currency = json_decode($data->currency_id,true);
            if(!in_array($curr->id,$supported_currency)){
                return redirect()->back()->with('unsuccess',__('Invalid Currency For Instamojo Payment.'));
            }

        $input = $request->all();

        $cancel_url = route('user.payment.cancle');
        $notify_url = route('user.membership.instamojo.notify');
        $item_name = $membershipPlan->title." Plan";

        Session::put('user_data',$input);

        $paydata = $data->convertAutoData();
        if($paydata['sandbox_check'] == 1){
        $api = new Instamojo($paydata['key'], $paydata['token'], 'https://test.instamojo.com/api/1.1/');
        }
        else {
        $api = new Instamojo($paydata['key'], $paydata['token']);
        }

        try {
        $response = $api->paymentRequestCreate(array(
            "purpose" => $item_name,
            "amount" => round($item_amount,2),
            "send_email" => false,
            "email" => $request->email,
            "redirect_url" => $notify_url
            ));

        $redirect_url = $response['longurl'];
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
        $plan['method'] = 'Instamojo';
        $plan['pay_id'] = $response['id'];

        Session::put('membership_plan',$plan);

        $data['total'] =  $item_amount;
        $data['return_url'] = $notify_url;
        $data['cancel_url'] = $cancel_url;
        Session::put('paypal_items',$data);
        return redirect($redirect_url);

        }
        catch (Exception $e) {
            return redirect()->back()->with('unsuccess',$e->getMessage());
        }

 }


public function notify(Request $request){

        $data = $request->all();

        $plan = Session::get('membership_plan');

        $input = Session::get('user_data');

        $success_url = route('user.payment.return');
        $cancel_url  = route('user.payment.cancle');


        if($plan['pay_id'] == $data['payment_request_id']){

            $purchase = new UserMembershipPlan;
            $purchase->user_id = $plan['user_id'];
            $purchase->membership_plan_id = $plan['membership_plan_id'];
            $purchase->title = $plan['title'];
            $purchase->currency_sign = $this ->curr->sign;
            $purchase->currency_code = $this->curr->name;
            $purchase->currency_value = $this->curr->value;
            $purchase->price = $plan['price'];
            $purchase->days = $plan['days'];
            $purchase->allowed_items = $plan['allowed_items'];
            $purchase->details = $plan['details'];
            $purchase->method = $plan['method'];
            $purchase->txnid = $data['payment_id'];
            $purchase->status = 1;

        $user = User::findOrFail($purchase->user_id);
        $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
        $membershipPlan = MembershipPlan::findOrFail($purchase->membership_plan_id);

        $today = Carbon::now()->format('Y-m-d');

        $input['is_merchant'] = 2;

        if(!empty($package))
        {
            if($package->membership_plan_id == $purchase->membership_plan_id)
            {
                $newday = strtotime($today);
                $lastday = strtotime($user->date);
                $secs = $lastday-$newday;
                $days = $secs / 86400;
                $total = $days+$membershipPlan->days;
                $input['date'] = date('Y-m-d', strtotime($today.' + '.$total.' days'));

            }
            else
            {
                $input['date'] = date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));
            }
        }
        else
        {
            $input['date']= date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));
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


        Session::forget('membership_plan');

            return redirect($success_url);
        }
        else {
            return redirect($cancel_url);
        }

        return redirect()->route('user.payment.return');
}

}
