<?php

namespace App\Http\Controllers\Payment\TopUp;

use App\{
    Models\TopUp,
    Models\WalletLog,
    Classes\MuaadhMailer,
    Models\MerchantPayment
};

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class SslController extends TopUpBaseController
{

    public function store(Request $request){

        $data = MerchantPayment::whereKeyword('sslcommerz')->first();
        $user = $this->user;

        $item_amount = $request->amount;
        $curr = $this->curr;

        $txnid = "SSLCZ_TXN_".uniqid();

        $supported_currency = json_decode($data->currency_id,true);
        if(!in_array($curr->id,$supported_currency)){
            return redirect()->back()->with('unsuccess',__('Invalid Currency For sslcommerz  Payment.'));
        }

        $cancel_url =  route('topup.payment.cancle');
        $notify_url = route('topup.ssl.notify');

        $topUp = new TopUp;
        $topUp->user_id = $user->id;
        $topUp->currency = $this->curr->sign;
        $topUp->currency_code = $this->curr->name;
        $topUp->amount = $request->amount / $this->curr->value;
        $topUp->currency_value = $this->curr->value;
        $topUp->method = 'SSLCommerz';
        $topUp->txnid = $txnid;
        $topUp->save();

        $paydata = $data->convertAutoData();

        $post_data = array();
        $post_data['store_id'] = $paydata['store_id'];
        $post_data['store_passwd'] = $paydata['store_password'];
        $post_data['total_amount'] = $item_amount;
        $post_data['currency'] = $curr->name;
        $post_data['tran_id'] = $txnid;
        $post_data['success_url'] = $notify_url;
        $post_data['fail_url'] =  $cancel_url;
        $post_data['cancel_url'] =  $cancel_url;
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE
        
        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $user->name;
        $post_data['cus_email'] = $user->email;
        $post_data['cus_add1'] = $user->address;
        $post_data['cus_city'] = $user->city;
        $post_data['cus_state'] = ''; // State field removed
        $post_data['cus_postcode'] = $user->zip;
        $post_data['cus_country'] = $user->country;
        $post_data['cus_phone'] = $user->phone;
        $post_data['cus_fax'] = $user->phone;
        
        # REQUEST SEND TO SSLCOMMERZ
        if($paydata['sandbox_check'] == 1){
            $direct_api_url = "https://sandbox.sslcommerz.com/gwprocess/v3/api.php";
        }
        else{
        $direct_api_url = "https://securepay.sslcommerz.com/gwprocess/v3/api.php";
        }


        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC
        
        $content = curl_exec($handle );
        
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        if($code == 200 && !( curl_errno($handle))) {
            curl_close( $handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close( $handle);
            return redirect()->back()->with('unsuccess',__("FAILED TO CONNECT WITH SSLCOMMERZ API"));
            exit;
        }
        
        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true );
        
  
        if(isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL']!="" ) {
        
             # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
            # echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
            echo "<meta http-equiv='refresh' content='0;url=".$sslcz['GatewayPageURL']."'>";
            # header("Location: ". $sslcz['GatewayPageURL']);
            exit;
        } else {
            return redirect()->back()->with('unsuccess',__("JSON Data parsing error!"));
        }

 }

    
    public function notify(Request $request){

        $input = $request->all();

        $cancel_url = route('topup.payment.cancle');
        $success_url = route('topup.payment.return');

        if($input['status'] == 'VALID'){


            $topUp = TopUp::where('txnid','=',$input['tran_id'])->orderBy('created_at','desc')->first();
            $user = \App\Models\User::findOrFail($topUp->user_id);

            $user->balance = $user->balance + ($topUp->amount);
            $user->save();

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

            return redirect($success_url);
        }
        else {
            return redirect($cancel_url);
        }

    }
}