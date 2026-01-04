<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};

use Illuminate\Http\Request;
use Carbon\Carbon;
use MercadoPago;

class MercadopagoController extends MembershipPlanBaseController
{

    public function store(Request $request)
    {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);
        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $data = MerchantPayment::whereKeyword('mercadopago')->first();
        $user = $this->user;

        $item_amount = $membershipPlan->price * $this->curr->value;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Mercadopago Payment.'));
        }

        $input = $request->all();

        $paydata = $data->convertAutoData();

        $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
        $success_url = route('user.payment.return');
        $item_name = $membershipPlan->title." Plan";

        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (string)$item_amount;
        $payment->token = $input['token'];
        $payment->description = $item_name;
        $payment->installments = 1;
        $payment->payer = array(
          "email" => $user->email
        );
        $payment->save();

        if ($payment->status == 'approved') {

            $today = Carbon::now()->format('Y-m-d');
            $input = $request->all();
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
            $plan->method = 'Mercadopago';
            $plan->txnid = $payment->id;

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

            return redirect($success_url);

        }

        return back()->with('unsuccess', __('Payment Failed.'));

    }
}
