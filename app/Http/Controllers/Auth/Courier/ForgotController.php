<?php

namespace App\Http\Controllers\Auth\Courier;

use App\{
    Models\Courier,
    Classes\MuaadhMailer,
    Http\Controllers\Controller
};

use Illuminate\{
  Http\Request,
  Support\Facades\Hash
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ForgotController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index(){
        if (Session::has('language'))
            {
                $langg = DB::table('languages')->find(Session::get('language'));
            }
            else
            {
                $langg = DB::table('languages')->where('is_default','=',1)->first();
            }
      return view('courier.forgot',compact('langg'));

    }

    public function forgot(Request $request)
    {
      $input =  $request->all();
      if (Courier::where('email', '=', $request->email)->count() > 0) {
      $courier = Courier::where('email', '=', $request->email)->first();
      $token = md5(time().$courier->name.$courier->email);
      $input['email_token'] = $token;
      $courier->update($input);
      $subject = "Reset Password Request";
      $msg = "Please click this link : ".'<a href="'.route('courier.change.token',$token).'">'.route('courier.change.token',$token).'</a>'.' to change your password.';

      $data = [
        'to' => $request->email,
        'subject' => $subject,
        'body' => $msg,
      ];

      $mailer = new MuaadhMailer();
      $mailer->sendCustomMail($data);

      return back()->with('success',__('Verification Link Sent Successfully!. Please Check your email.'));
      }
      else{
      return back()->with('unsuccess',__('No Account Found With This Email.'));
      }
    }

    public function showChangePassForm($token)
    {
      if($token){
        if( Courier::where('email_token', $token)->exists() ){
          return view('user.changepass',compact('token'));
        }
      }
    }

    public function changepass(Request $request)
    {
        $token = $request->token;
        $courier =  Courier::where('email_token', $token)->first();
        if($courier){

        if ($request->newpass == $request->renewpass){
            $input['password'] = Hash::make($request->newpass);
            $courier->email_token = null;
            $courier->update($input);
        }else{
            return response()->json(array('errors' => [ 0 => __('Confirm password does not match.') ]));
        }




        $msg = __('Successfully changed your password.').'<a href="'.route('user.login').'"> '.__('Login Now').'</a>';
        return response()->json($msg);
        }else{
          return response()->json(array('errors' => [ 0 => __('Invalid Token.') ]));
        }
    }

}
