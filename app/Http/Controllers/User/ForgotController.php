<?php

namespace App\Http\Controllers\User;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ForgotController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showForgotForm()
    {
        if (Session::has('language')) {
            $langg = DB::table('languages')->find(Session::get('language'));
        } else {
            $langg = DB::table('languages')->where('is_default', '=', 1)->first();
        }
        return view('user.forgot', compact('langg'));
    }

    public function forgot(Request $request)
    {
        $gs = Generalsetting::findOrFail(1);
        $input = $request->all();
        if (User::where('email', '=', $request->email)->count() > 0) {
            // user found
            $admin = User::where('email', '=', $request->email)->firstOrFail();
            $autopass = Str::random(8);
            $input['password'] = bcrypt($autopass);
            $admin->update($input);
            $subject = "Reset Password Request";
            $msg = "Your New Password is : " . $autopass;
            if ($gs->is_smtp == 1) {
                $data = [
                    'to' => $request->email,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new GeniusMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
                mail($request->email, $subject, $msg, $headers);
            }
            return back()->with('success', 'New Password has been sent to your email.');
        } else {
            return back()->with('unsuccess', 'No Account Found With This Email.');
        }
    }

}
