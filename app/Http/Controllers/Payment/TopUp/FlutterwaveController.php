<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Session;
use PurchaseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlutterwaveController extends TopUpBaseController
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

        $data = MerchantPayment::whereKeyword('flutterwave')->first();
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;


        $item_number = Str::random(4).time();

        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For Flutterwave Payment.'));
        }

        $topUp = new TopUp;
        $topUp->user_id = $user->id;
        $topUp->currency = $this->curr->sign;
        $topUp->currency_code = $this->curr->name;
        $topUp->amount = $request->amount / $this->curr->value;
        $topUp->currency_value = $this->curr->value;
        $topUp->method = 'Flutterwave';
        $topUp->flutter_id = $item_number;
        $topUp->save();

        // SET CURL

        $curl = curl_init();

        $customer_email = $user->email;
        $amount = $item_amount;  
        $currency = $curr->name;
        $txref = $item_number; // ensure you generate unique references per transaction.
        $PBFPubKey = $this->public_key; // get your public key from the dashboard.
        $redirect_url = route('topup.flutter.notify');
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

          return redirect($flutterwaveResponse->data->link);

     }


     public function notify(Request $request) {

        $input = $request->all();

       
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


              $topUp = TopUp::where('flutter_id','=',$input['txref'])->orderBy('created_at','desc')->first();
              $user = \App\Models\User::findOrFail($topUp->user_id);

              $user->balance = $user->balance + ($topUp->amount);
              $user->save();
              $topUp->txnid =  $resp['data']['txid'];
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

              }

              else {
                $topUp = TopUp::where('flutter_id','=',$input['txref'])
                ->orderBy('created_at','desc')->first();
                  $topUp->delete();
                  return redirect($cancel_url);
              }


            }
        }
            else {
              $topUp = TopUp::where('flutter_id','=',$input['txref'])
              ->orderBy('created_at','desc')->first();
                $topUp->delete();
                return redirect($cancel_url);
            }

     }
}