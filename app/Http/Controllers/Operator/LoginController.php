<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Domain\Platform\Models\Operator;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;

use Validator;


class LoginController extends Controller
{
    public function __construct()
    {
      $this->middleware('guest:operator', ['except' => ['logout']]);
    }

    public function showLoginForm()
    {
      return view('operator.login');
    }

    public function login(Request $request)
    {
        //--- Validation Section
        $rules = [
                  'email'   => 'required|email',
                  'password' => 'required'
                ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

      // Attempt to log the user in
      if (Auth::guard('operator')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
        // if successful, then redirect to their intended location
        return response()->json(route('operator.dashboard'));
      }

      // if unsuccessful, then redirect back to the login with the form data
          return response()->json(array('errors' => [ 0 => 'Credentials Doesn\'t Match !' ]));
    }

    public function showForgotForm()
    {
      return view('operator.forgot');
    }

    public function forgot(Request $request)
    {
      $ps = platformSettings();
      $input =  $request->all();
      if (Operator::where('email', '=', $request->email)->count() > 0) {
      // user found
      $operator = Operator::where('email', '=', $request->email)->firstOrFail();
      $token = md5(time().$operator->name.$operator->email);

      $file = fopen(public_path().'/project/storage/tokens/'.$token.'.data','w+');
      fwrite($file,$operator->id);
      fclose($file);

      $subject = "Reset Password Request";
      $msg = "Please click this link : ".'<a href="'.route('operator.change.token',$token).'">'.route('operator.change.token',$token).'</a>'.' to change your password.';
      if ($ps->get('mail_driver'))
      {
          $data = [
                  'to' => $request->email,
                  'subject' => $subject,
                  'body' => $msg,
          ];

          $mailer = new MuaadhMailer();
          $mailer->sendCustomMail($data);
      }
      else
      {
          $headers = "From: ".$ps->get('from_name')."<".$ps->get('from_email').">";
          mail($request->email,$subject,$msg,$headers);
      }
      return response()->json('Verification Link Sent Successfully!. Please Check your email.');
      }
      else{
      // user not found
      return response()->json(array('errors' => [ 0 => 'No Account Found With This Email.' ]));
      }
    }

    public function showChangePassForm($token)
    {
      if (file_exists(public_path().'/project/storage/tokens/'.$token.'.data')){
        $id = file_get_contents(public_path().'/project/storage/tokens/'.$token.'.data');
        return view('operator.changepass',compact('id','token'));
      }
    }

    public function changepass(Request $request)
    {
        $id = $request->operator_id;
        $operator = Operator::findOrFail($id);
        $token = $request->file_token;
        if ($request->cpass){
            if (Hash::check($request->cpass, $operator->password)){
                if ($request->newpass == $request->renewpass){
                    $input['password'] = Hash::make($request->newpass);
                }else{
                    return response()->json(array('errors' => [ 0 => 'Confirm password does not match.' ]));
                }
            }else{
                return response()->json(array('errors' => [ 0 => 'Current password Does not match.' ]));
            }
        }
        $operator->update($input);

        unlink(public_path().'/project/storage/tokens/'.$token.'.data');

        $msg = 'Successfully changed your password.<a href="'.route('operator.login').'"> Login Now</a>';
        return response()->json($msg);
    }

    public function logout()
    {
        Auth::guard('operator')->logout();
        return redirect('/');
    }
}
