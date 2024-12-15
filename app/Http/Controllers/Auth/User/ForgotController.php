<?php

namespace App\Http\Controllers\Auth\User;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class ForgotController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index()
    {
        if (Session::has('language')) {
            $langg = DB::table('languages')->find(Session::get('language'));
        } else {
            $langg = DB::table('languages')->where('is_default', '=', 1)->first();
        }
        return view('frontend.forget', compact('langg'));

    }

    public function forgot(Request $request)
    {
        $input = $request->all();

        if (User::where('email', '=', $request->email)->count() > 0) {
            // user found
            $admin = User::where('email', '=', $request->email)->first();
            $token = md5(time() . $admin->name . $admin->email);
            $input['email_token'] = $token;
            $admin->update($input);
            $subject = "Reset Password Request";
            $msg = "Please click this link : " . '<a href="' . route('user.change.token', $token) . '">' . route('user.change.token', $token) . '</a>' . ' to change your password.';

            $data = [
                'to' => $request->email,
                'subject' => $subject,
                'body' => $msg,
            ];

            $mailer = new GeniusMailer();
            $mailer->sendCustomMail($data);

            return back()->with('success', 'New Password has been sent to your email.');
        } else {
            return back()->with('unsuccess', 'No Account Found With This Email.');
        }
    }

    public function showChangePassForm($token)
    {
        if ($token) {
            if (User::where('email_token', $token)->exists()) {
                return view('user.changepass', compact('token'));
            }
        }
    }

    public function changepass(Request $request)
    {
        $token = $request->token;
        $admin = User::where('email_token', $token)->first();
        if ($admin) {

            if ($request->newpass == $request->renewpass) {
                $input['password'] = Hash::make($request->newpass);
                $admin->email_token = null;
                $admin->update($input);
            } else {
              
                
                return back()->with("unsuccess","Confirm password does not match.");
            }

            return redirect(route("user.login"))->with("success","Successfully changed your password.");
        } else {
            return back()->with("error","__('Invalid Token.')");
        }
    }

}
