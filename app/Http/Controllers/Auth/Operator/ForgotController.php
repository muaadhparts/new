<?php

namespace App\Http\Controllers\Auth\Operator;

use App\{
    Models\Operator,
    Classes\MuaadhMailer,
    Http\Controllers\Controller
};

use Illuminate\{
  Http\Request,
  Support\Facades\Hash
};

class ForgotController extends Controller
{
    public function __construct()
    {
      $this->middleware('guest:operator');
    }

    public function showForm()
    {
      return view('operator.forgot');
    }

    public function forgot(Request $request)
    {
      $input =  $request->all();
      if (Operator::where('email', '=', $request->email)->count() > 0) {
      // user found
      $operator = Operator::where('email', '=', $request->email)->first();
      $token = md5(time().$operator->name.$operator->email);
      $input['email_token'] = $token;
      $operator->update($input);
      $subject = "Reset Password Request";
      $msg = "Please click this link : ".'<a href="'.route('operator.change.token',$token).'">'.route('operator.change.token',$token).'</a>'.' to change your password.';

      $data = [
        'to' => $request->email,
        'subject' => $subject,
        'body' => $msg,
      ];

      $mailer = new MuaadhMailer();
      $mailer->sendCustomMail($data);                

      return response()->json(__('Verification Link Sent Successfully!. Please Check your email.'));
      }
      else{
      // user not found
      return response()->json(array('errors' => [ 0 => __('No Account Found With This Email.') ]));    
      }  
    }

    public function showChangePassForm($token)
    {
      if($token){
        if( Operator::where('email_token', $token)->exists() ){
          return view('operator.changepass',compact('token'));  
        }
      }
    }

    public function changepass(Request $request)
    {
        $token = $request->file_token;
        $operator = Operator::where('email_token', $token)->first();

        if($operator){
          if ($request->cpass){
            if (Hash::check($request->cpass, $operator->password)){
                if ($request->newpass == $request->renewpass){
                    $input['password'] = Hash::make($request->newpass);
                }else{
                    return response()->json(array('errors' => [ 0 => __('Confirm password does not match.') ]));
                }
            }else{
                return response()->json(array('errors' => [ 0 => __('Current password does not match.') ]));
            }
        }

        $operator->email_token = null;
        $operator->update($input);

        $msg = __('Successfully changed your password.').'<a href="'.route('operator.login').'"> '.__('Login Now').'</a>';
        return response()->json($msg);
        }else{
          return response()->json(array('errors' => [ 0 => __('Invalid Token.') ]));
        }
    }
}