<?php

namespace App\Http\Controllers\Api\User\Payment;


use App\Models\TopUp;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MerchantPayment;
use App\Models\WalletLog;
use Illuminate\Support\Str;



class FlutterWaveController extends Controller
{
  public $public_key;
  private $secret_key;

  public function __construct()
    {
        
        $data = MerchantPayment::whereKeyword('flutterwave')->first();
        $paydata = $data->convertAutoData();
        $this->public_key = $paydata['public_key'];
        $this->secret_key = $paydata['secret_key'];
    }

    public function store(Request $request){

         if(!$request->has('topup_number')){
             return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
        }

        $topupNumber = $request->topup_number;
        $purchase = TopUp::where('topup_number',$topupNumber)->first();
        $curr = Currency::where('name','=',$purchase->currency_code)->first();
        $settings = Muaadhsetting::findOrFail(1);
        $item_amount = $purchase->amount * $purchase->currency_value;

   
                $available_currency = array(
                    'CAD',
                    'EUR',
                    'GBP',
                    'USD',
                    'ZWD',
                    'NGN',
                    );
                    if(!in_array($curr->name,$available_currency))
                    {
                    return redirect()->back()->with('unsuccess','Invalid Currency For Flutter Wave.');
                    }

            $purchase['method'] = $request->method;
            $purchase->update();
                   

        // SET CURL

        $curl = curl_init();

        $currency = $curr->name;
        $txref = $purchase->topup_number; // ensure you generate unique references per transaction.
        $PBFPubKey = $this->public_key; // get your public key from the dashboard.
        $redirect_url = action('Api\User\Payment\FlutterWaveController@notify');
        $payment_plan = ""; // this is only required for recurring payments.
        
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'amount' => $item_amount,
            'customer_email' => User::findOrFail($purchase->user_id)->email,
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
   
   
   public function notify(Request $request){
   
    $input = $request->all();
    

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
        
        if ($resp['status'] = "success") {
           $txn = $resp['data']['txid'];
           
            $paymentStatus = $resp['data']['status'];
            $chargeResponsecode = $resp['data']['chargecode'];
    
            if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {

            $purchase = TopUp::where('topup_number',$resp['data']['txref'])->first();
            $purchase['txnid'] = $txn;
            $purchase['status'] = 1;
            $user = \App\Models\User::findOrFail($purchase->user_id);
            $user->balance = $user->balance + ($purchase->amount);
            $user->save();
            $purchase->update();
                              // store in wallet_logs table
                    if ($purchase->status == 1) {
                        $walletLog = new WalletLog;
                        $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
                        $walletLog->user_id = $purchase->user_id;
                        $walletLog->amount = $purchase->amount;
                        $walletLog->user_id = $purchase->user_id;
                        $walletLog->currency_sign = $purchase->currency;
                        $walletLog->currency_code = $purchase->currency_code;
                        $walletLog->currency_value= $purchase->currency_value;
                        $walletLog->method = $purchase->method;
                        $walletLog->txnid = $purchase->txnid;
                        $walletLog->details = 'Wallet TopUp';
                        $walletLog->type = 'plus';
                        $walletLog->save();
                    }
                

            return redirect(route('user.success',1));

        }

        else {
           
           return redirect(route('user.success',0));
        }

        
    }
        else {
           return redirect(route('user.success',0));
        }
    }

   }

}
