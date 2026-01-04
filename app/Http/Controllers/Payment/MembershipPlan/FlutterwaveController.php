<?php

namespace App\Http\Controllers\Payment\MembershipPlan;

use App\{
    Models\User,
    Models\MembershipPlan,
    Classes\MuaadhMailer,
    Models\MerchantPayment,
    Models\UserMembershipPlan
};
use Session;
use PurchaseHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlutterwaveController extends MembershipPlanBaseController
{

    public $public_key;
    private $secret_key;

    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('flutterwave')->first();
        $paydata = $data->convertAutoData();
        $this->public_key = $paydata['public_key'];
        $this->secret_key = $paydata['secret_key'];
    }

    public function store(Request $request) {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[
               'shop_name.unique' => __('This shop name has already been taken.')
            ]);

            $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
            $user = $this->user;
            $data = MerchantPayment::whereKeyword('flutterwave')->first();

            $item_amount = $membershipPlan->price * $this->curr->value;
            $curr = $this->curr;

            $supported_currency = json_decode($data->currency_id,true);
            if(!in_array($curr->id,$supported_currency)){
                return redirect()->back()->with('unsuccess',__('Invalid Currency For Flutterwave Payment.'));
            }

            $item_number = Str::random(4).time();

            $item_currency = $curr->name;

            $available_currency = PurchaseHelper::flutter_currencies();

            if(!in_array($item_currency,$available_currency))
            {
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Flutter Wave.'));
            }

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
            $plan->method = 'Flutterwave';
            $plan->flutter_id = $item_number;
            $plan->status = 0;
            $plan->save();

        // SET CURL

        $curl = curl_init();
        $customer_email = $user->email;
        $amount = $item_amount;
        $currency = $item_currency;
        $txref = $item_number; // ensure you generate unique references per transaction.
        $PBFPubKey = $this->public_key; // get your public key from the dashboard.
        $redirect_url = route('user.membership.flutter.notify');
        $payment_plan = ""; // this is only required for recurring payments.

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
              'amount' => $amount,
              'customer_email' => $customer_email,
              'currency' => $currency,
              'txref' => $txref,
              'PBFPubKey' => $PBFPubKey,
              'redirect_url' => $redirect_url,
              'payment_plan' => $payment_plan
            ]),
            CURLOPT_HTTPHEADER => [
              "content-type: application/json",
              "cache-control: no-cache"
            ],
          ));

          $response = curl_exec($curl);
          $err = curl_error($curl);

          if($err){
            // there was an error contacting the rave API
            die('Curl returned error: ' . $err);
          }



          $flutterwaveResponse = json_decode($response);
          if(!$flutterwaveResponse->data && !$flutterwaveResponse->data->link){
            // there was an error from the API
            print_r('API returned error: ' . $flutterwaveResponse->message);
          }


          $s_datas = Session::all();
          $session_datas = json_encode($s_datas);
          file_put_contents(storage_path().'/flutter/'.$item_number.'.json', $session_datas);

          return redirect($flutterwaveResponse->data->link);

     }

     public function notify(Request $request) {

        $input = $request->all();

        if(file_exists(storage_path().'/flutter/'.$input['txref'].'.json')){
            $data_results = file_get_contents(storage_path().'/flutter/'.$input['txref'].'.json');
            $lang = json_decode($data_results, true);
            foreach($lang as $key => $lan){
                Session::put(''.$key,$lan);
            }
            unlink(storage_path().'/flutter/'.$input['txref'].'.json');
        }

        $success_url = route('user.payment.return');
        $cancel_url = route('user.payment.cancle');

        if($request->cancelled == "true"){
            return redirect()->route('user-dashboard')->with('success',__('Payment Cancelled!'));
        }

        if (isset($input['txref'])) {

            $ref = $input['txref'];

            $query = array(
                "SECKEY" => $this->secret_key,
                "txref" => $ref
            );

            $data_string = json_encode($query);

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $response = curl_exec($ch);

            curl_close($ch);

            $resp = json_decode($response, true);

            if ($resp['status'] == "success") {

                $paymentStatus = $resp['data']['status'];
                $chargeResponsecode = $resp['data']['chargecode'];

                if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {


                $membershipPlan = UserMembershipPlan::where('flutter_id','=',$input['txref'])->orderBy('id','desc')->first();
                $membershipPlan->status = 1;
                $membershipPlan->txnid = $resp['data']['txid'];
                $membershipPlan->update();

                $user = User::findOrFail($membershipPlan->user_id);
                $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();

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
            else {
                $plan = UserMembershipPlan::where('flutter_id','=',$input['txref'])->orderBy('id','desc')->first();
                $plan->delete();
                return redirect($cancel_url);
            }
        }

         } else {
                $plan = UserMembershipPlan::where('flutter_id','=',$input['txref'])->orderBy('id','desc')->first();
                $plan->delete();
                return redirect($cancel_url);
            }
     }
}
