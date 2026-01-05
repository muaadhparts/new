<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Illuminate\{
    Http\Request,
    Support\Facades\Session
};

use Razorpay\Api\Api;
use Illuminate\Support\Str;


class RazorpayController extends TopUpBaseController
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

    public function store(Request $request){

        $data = MerchantPayment::whereKeyword('razorpay')->first();
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;

        $this->displayCurrency = ''.$curr->name.'';


        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Razorpay Payment.'));
        }

        $return_url = route('topup.payment.return');
        $cancel_url = route('topup.payment.cancle');
        $notify_url = route('topup.razorpay.notify');
        $item_name = "TopUp via Razorpay";
        $item_number = "RZP".Str::random(4).time();


        $purchaseData = [
            'receipt'         => $item_number,
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        
        $razorpayOrder = $this->api->purchase->create($purchaseData);
        
        $razorpayOrderId = $razorpayOrder['id'];
        
        session(['razorpay_order_id'=> $razorpayOrderId]);


    // Redirect to paypal IPN

                    $topUp = new TopUp;
                    $topUp->user_id = $user->id;
                    $topUp->currency = $this->curr->sign;
                    $topUp->currency_code = $this->curr->name;
                    $topUp->amount = $request->amount / $this->curr->value;
                    $topUp->currency_value = $this->curr->value;
                    $topUp->topup_number = $item_number;
                    $topUp->method = 'Razorpay';
                    $topUp->save();

                    $displayAmount = $amount = $purchaseData['amount'];
                    
                    if ($this->displayCurrency !== 'INR')
                    {
                        $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
                        $exchange = json_decode(file_get_contents($url), true);
                    
                        $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
                    }
                    
                    $checkout = 'automatic';
                    
                    if (isset($_GET['checkout']) and in_array($_GET['checkout'], ['automatic', 'manual'], true))
                    {
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
                    
                    if ($this->displayCurrency !== 'INR')
                    {
                        $data['display_currency']  = $this->displayCurrency;
                        $data['display_amount']    = $displayAmount;
                    }
                    
                    $json = json_encode($data);
                    $displayCurrency = $this->displayCurrency;
                    Session::put('item_number',$item_number); 
                    
        return view( 'frontend.razorpay-checkout', compact( 'data','displayCurrency','json','notify_url' ) );

 }

    
    public function notify(Request $request){

            $success = true;

            
            if (empty($_POST['razorpay_payment_id']) === false)
            {
                try
                {

                    $attributes = array(
                        'razorpay_order_id' => session('razorpay_order_id'),
                        'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                        'razorpay_signature' => $_POST['razorpay_signature']
                    );
            
                    $this->api->utility->verifyPaymentSignature($attributes);
                }
                catch(SignatureVerificationError $e)
                {
                    $success = false;
                    $error = 'Razorpay Error : ' . $e->getMessage();
                }
            }
            
            if ($success === true)
            {

                $transaction_id = $_POST['razorpay_payment_id'];

                $topUp = TopUp::where('topup_number','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
                $user = \App\Models\User::findOrFail($topUp->user_id);

                $user->balance = $user->balance + ($topUp->amount);
                $user->save();
                $topUp->txnid = $transaction_id;
                $topUp->status = 1;
                $topUp->save();


            // store in wallet_logs table
            if ($topUp->status == 1) {
                $walletLog = new WalletLog;
                $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
                $walletLog->user_id = $topUp->user_id;
                $walletLog->amount = $topUp->amount;
                $walletLog->user_id = $topUp->user_id;
                $walletLog->currency_sign = $topUp->currency;
                $walletLog->currency_code = $topUp->currency_code;
                $walletLog->currency_value= $topUp->currency_value;
                $walletLog->method = $topUp->method;
                $walletLog->txnid = $topUp->txnid;
                $walletLog->details = 'Wallet TopUp';
                $walletLog->type = 'plus';
                $walletLog->save();
            }


                $maildata = [
                    'to' => $user->email,
                    'type' => "wallet_topup",
                    'cname' => $user->name,
                    'damount' => $topUp->amount,
                    'wbalance' => $user->balance,
                    'oamount' => "",
                    'aname' => "",
                    'aemail' => "",
                    'onumber' => "",
                ];
                $mailer = new MuaadhMailer();
                $mailer->sendAutoMail($maildata);

            return redirect()->route('user-dashboard')->with('success',__('Balance has been added to your account.'));
            }else{
                $topUp = TopUp::where('topup_number','=',Session::get('item_number'))->orderBy('created_at','desc')->first();
                $topUp->delete();
            }
            return redirect()->back()->with('unsuccess',__('Payment Cancelled.'));
    }
    
}