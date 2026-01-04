<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\User,
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};

use Illuminate\Http\Request;
use Mollie\Laravel\Facades\Mollie;
use Illuminate\Support\Str;

use Session;
use PurchaseHelper;
use Carbon\Carbon;

class MollieController extends MembershipPlanBaseController
{

 public function store(Request $request){

    $this->validate($request, [
        'shop_name'   => 'unique:users',
    ],[
        'shop_name.unique' => __('This shop name has already been taken.')
    ]);

    $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
    $data = MerchantPayment::whereKeyword('mollie')->first();
    $user = $this->user;

    $item_amount = $membershipPlan->price * $this->curr->value;
    $curr = $this->curr;

    $supported_currency = json_decode($data->currency_id,true);
    if(!in_array($curr->id,$supported_currency)){
        return redirect()->back()->with('unsuccess',__('Invalid Currency For Molly Payment.'));
    }

     $input = $request->all();


     $notify_url = route('user.membership.molly.notify');
     $purchase['item_name'] = $membershipPlan->title." Plan";
     $purchase['item_number'] = Str::random(4).time();
     $purchase['item_amount'] = $item_amount;

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
     $plan['method'] = 'Molly';

        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $curr->name,
                'value' => ''.sprintf('%0.2f', $purchase['item_amount']).'', // You must send the correct number of decimals, thus we enforce the use of strings
            ],
            'description' => $purchase['item_name'] ,
            'redirectUrl' => $notify_url,
            ]);

        Session::put('payment_id',$payment->id);
        Session::put('molly_data',$plan);
        Session::put('user_data',$input);
        Session::put('order_data',$purchase);

        $payment = Mollie::api()->payments()->get($payment->id);

        return redirect($payment->getCheckoutUrl(), 303);

 }


public function notify(Request $request){

        $plan = Session::get('molly_data');
        $input = Session::get('user_data');
        $purchase = Session::get('order_data');

        $success_url = route('user.payment.return');
        $cancel_url = route('user.payment.cancle');

        $payment = Mollie::api()->payments()->get(Session::get('payment_id'));

        if($payment->status == 'paid'){

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
            $purchase->txnid = $payment->id;
            $purchase->status = 1;

            $user = User::findOrFail($purchase->user_id);
            $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
            $membershipPlan = MembershipPlan::findOrFail($purchase->membership_plan_id);

            $today = Carbon::now()->format('Y-m-d');
            $user->is_merchant = 2;
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

                $input['date'] = date('Y-m-d', strtotime($today.' + '.$membershipPlan->days.' days'));

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
        }
        else {
            return redirect($cancel_url);
        }
}

}
