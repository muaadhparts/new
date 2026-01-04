<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
	Models\User,
    Traits\Paytm,
	Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
	Models\UserMembershipPlan
};

use Illuminate\{
	Http\Request,
	Support\Facades\Session
};

use Carbon\Carbon;
use Illuminate\Support\Str;

class PaytmController extends MembershipPlanBaseController
{

    use Paytm;
    public function store(Request $request)
    {
        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);

            $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
            $data = MerchantPayment::whereKeyword('paytm')->first();
            $user = $this->user;

            $item_amount = $membershipPlan->price * $this->curr->value;
            $curr = $this->curr;

            $supported_currency = json_decode($data->currency_id,true);
            if(!in_array($curr->id,$supported_currency)){
                return redirect()->back()->with('unsuccess',__('Invalid Currency For Paytm Payment.'));
            }

			$item_name = $membershipPlan->title." Plan";
			$item_number = Str::random(4).time();

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
            $plan->method = 'Paytm';
            $plan->save();

            Session::put('item_number',$plan->user_id);

            $s_datas = Session::all();
            $session_datas = json_encode($s_datas);
            file_put_contents(storage_path().'/paytm/'.$item_number.'.json', $session_datas);

	    $data_for_request = $this->handlePaytmRequest( $item_number, $item_amount, 'membership_plan' );
	    $paytm_txn_url = 'https://securegw-stage.paytm.in/theia/processTransaction';
	    $paramList = $data_for_request['paramList'];
	    $checkSum = $data_for_request['checkSum'];
	    return view( 'front.paytm-merchant-form', compact( 'paytm_txn_url', 'paramList', 'checkSum' ) );
    }


	public function notify( Request $request ) {


		$input = $request->all();
		$purchase_id = $request['ORDERID'];

        if(file_exists(storage_path().'/paytm/'.$purchase_id.'.json')){
            $data_results = file_get_contents(storage_path().'/paytm/'.$purchase_id.'.json');
            $lang = json_decode($data_results, true);
            foreach($lang as $key => $lan){
                Session::put(''.$key,$lan);
            }
            unlink(storage_path().'/paytm/'.$purchase_id.'.json');
        }

		if ( 'TXN_SUCCESS' === $request['STATUS'] ) {
			$transaction_id = $request['TXNID'];
        $purchase = UserMembershipPlan::where('user_id','=',Session::get('item_number'))
            ->orderBy('created_at','desc')->first();

        $user = User::findOrFail($purchase->user_id);
        $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
        $membershipPlan = MembershipPlan::findOrFail($purchase->membership_plan_id);

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

        $data['txnid'] = $transaction_id;
        $data['status'] = 1;
        $purchase->update($data);
            $maildata = [
                'to' => $user->email,
                'type' => "merchant_accept",
                'cname' => $user->name,
                'oamount' => "",
                'aname' => "",
                'aemail' => "",
                'onumber' => '',
            ];
            $mailer = new MuaadhMailer();
            $mailer->sendAutoMail($maildata);

            return redirect()->route('user-dashboard')->with('success',__('Merchant Account Activated Successfully'));

		} else if( 'TXN_FAILURE' === $request['STATUS'] ){
            //return view( 'payment-failed' );
        $purchase = UserMembershipPlan::where('user_id','=',Session::get('item_number'))
            ->orderBy('created_at','desc')->first();
            $purchase->delete();
            return redirect(route('user.payment.cancle'));
		}
    }
}
