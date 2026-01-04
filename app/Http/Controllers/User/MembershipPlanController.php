<?php

namespace App\Http\Controllers\User;

use App\{
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\UserMembershipPlan,
    Models\MerchantPayment
};
use Carbon\Carbon;
use Illuminate\Http\Request;

class MembershipPlanController extends UserBaseController
{

    public function package()
    {
        $data['curr'] = $this->curr;
        $data['user'] = $this->user;
        $data['membershipPlans'] = MembershipPlan::all();
        $data['package'] = $this->user->membershipPlans()->where('status',1)->latest('id')->first();
        return view('user.package.index',$data);
    }

    public function merchantrequest($id)
    {
        $data['curr'] = $this->curr;
        $data['membershipPlan'] = MembershipPlan::findOrFail($id);
        $data['user'] = $this->user;
        $data['package'] = $this->user->membershipPlans()->where('status',1)->latest('id')->first();

        if($this->gs->reg_merchant != 1)
        {
            return redirect()->back();
        }

        $data['gateway'] = MerchantPayment::whereSubscription(1)->where('currency_id', 'like', "%\"{$this->curr->id}\"%")->latest('id')->get();
        $paystackData = MerchantPayment::whereKeyword('paystack')->first();
        $data['paystack'] = $paystackData->convertAutoData();
        $voguepayData = MerchantPayment::whereKeyword('voguepay')->first();


        return view('user.package.details',$data);
    }

    public function merchantrequestsub(Request $request)
    {
        $input = $request->all();
        if(isset($input['method'])){
            return redirect()->back();
        }
        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);

            if(\DB::table('static_content')->where('slug',$request->shop_name)->exists())
            {
                return redirect()->back()->with('unsuccess',__('This shop name has already been taken.'));
            }

            $success_url = route('user.payment.return');
            $user = $this->user;
            $subs = MembershipPlan::findOrFail($request->subs_id);

            $user->is_merchant = 2;
            $user->date = date('Y-m-d', strtotime(Carbon::now()->format('Y-m-d').' + '.$subs->days.' days'));
            $user->mail_sent = 1;
            $user->update($input);

            $sub = new UserMembershipPlan;
            $data = json_decode(json_encode($subs), true);
            $data['user_id'] = $user->id;
            $data['membership_plan_id'] = $subs->id;
            $data['method'] = 'Free';
            $data['status'] = 1;
            $sub->currency_sign = $this->curr->sign;
            $sub->currency_code = $this->curr->name;
            $sub->currency_value = $this->curr->value;
            $sub->fill($data)->save();

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

            return redirect($success_url)->with('success',__('Merchant Account Activated Successfully'));

    }

    public function paycancle(){
        return redirect()->back()->with('unsuccess',__('Payment Cancelled.'));
    }

    public function payreturn(){
        return redirect()->route('user-dashboard')->with('success',__('Merchant Account Activated Successfully'));
    }

    public function check(Request $request){

        //--- Validation Section
        $input = $request->all();
        $rules = ['shop_name'   => 'unique:users'];
        $customs = ['shop_name.unique' => __('This shop name has already been taken.')];
        $validator = \Validator::make($input, $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends
        return response()->json('success');
    }


}
