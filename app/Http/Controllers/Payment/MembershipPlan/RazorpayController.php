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

use Carbon\Carbon;
use Razorpay\Api\Api;
use Illuminate\Support\Str;


class RazorpayController extends MembershipPlanBaseController
{

    public function __construct()
    {
        parent::__construct();
        $data = MerchantPayment::whereKeyword('razorpay')->first();
        $paydata = $data->convertAutoData();
        $this->keyId = $paydata['key'];
        $this->keySecret = $paydata['secret'];
        $this->displayCurrency = 'INR';
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'shop_name'   => 'unique:users',
        ], [
            'shop_name.unique' => __('This shop name has already been taken.')
        ]);

        $membershipPlan = MembershipPlan::findOrFail($request->subs_id);
        $data = MerchantPayment::whereKeyword('razorpay')->first();
        $user = $this->user;

        $item_amount = $membershipPlan->price * $this->curr->value;
        $curr = $this->curr;

        $supported_currency = json_decode($data->currency_id, true);
        if (!in_array($curr->id, $supported_currency)) {
            return redirect()->back()->with('unsuccess', __('Invalid Currency For Razorpay Payment.'));
        }

        $this->displayCurrency = '' . $curr->name . '';

        $item_name = $membershipPlan->title . " Plan";
        $item_number = Str::random(4) . time();

        $cancel_url = route('user.payment.cancle');
        $notify_url = route('user.membership.razorpay.notify');

        $purchaseData = [
            'receipt'         => $item_number,
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];

        $razorpayOrder = $this->api->purchase->create($purchaseData);

        $razorpayOrderId = $razorpayOrder['id'];

        session(['razorpay_order_id' => $razorpayOrderId]);

        // Redirect to paypal IPN

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
        $plan->method = 'Razorpay';
        $plan->save();

        $displayAmount = $amount = $purchaseData['amount'];

        if ($this->displayCurrency !== 'INR') {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);

            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }

        $checkout = 'automatic';

        if (isset($_GET['checkout']) and in_array($_GET['checkout'], ['automatic', 'manual'], true)) {
            $checkout = $_GET['checkout'];
        }

        $data = [
            "key"               => $this->keyId,
            "amount"            => $amount,
            "name"              => $item_name,
            "description"       => $item_name,
            "prefill"           => [
                "name"              => $user->name,
                "email"             => $user->email,
                "contact"           => $user->phone,
            ],
            "notes"             => [
                "address"           => $user->address,
                "merchant_order_id" => $item_number,
            ],
            "theme"             => [
                "color"             => "{{$this->gs->colors}}"
            ],
            "order_id"          => $razorpayOrderId,
        ];

        if ($this->displayCurrency !== 'INR') {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }

        $json = json_encode($data);
        $displayCurrency = $this->displayCurrency;
        Session::put('item_number', $plan->user_id);

        return view('frontend.razorpay-checkout', compact('data', 'displayCurrency', 'json', 'notify_url'));
    }


    public function notify(Request $request)
    {

        $success = true;


        if (empty($_POST['razorpay_payment_id']) === false) {


            try {

                $attributes = array(
                    'razorpay_order_id' => session('razorpay_order_id'),
                    'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                    'razorpay_signature' => $_POST['razorpay_signature']
                );

                $this->api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
            }
        }

        if ($success === true) {

            $razorpayOrder = $this->api->purchase->fetch(session('razorpay_order_id'));

            $purchase_id = $razorpayOrder['receipt'];
            $transaction_id = $_POST['razorpay_payment_id'];



            $purchase = UserMembershipPlan::where('user_id', '=', Session::get('item_number'))
                ->orderBy('created_at', 'desc')->first();

            $user = User::findOrFail($purchase->user_id);
            $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
            $membershipPlan = MembershipPlan::findOrFail($purchase->membership_plan_id);

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

            return redirect()->route('user-dashboard')->with('success', __('Merchant Account Activated Successfully'));
        } else {
            $razorpayOrder = $this->api->purchase->fetch(session('razorpay_order_id'));
            $purchase_id = $razorpayOrder['receipt'];
            $payment = UserMembershipPlan::where('user_id', '=', $purchase_id)
                ->orderBy('created_at', 'desc')->first();
            $payment->delete();
        }
    }
}
