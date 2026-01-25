<?php

namespace App\Http\Controllers\User;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Controller;
use App\Domain\Identity\Models\User;
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
        $ps = platformSettings();
        $input = $request->all();
        if (User::where('email', '=', $request->email)->count() > 0) {
            // user found
            $user = User::where('email', '=', $request->email)->firstOrFail();
            $autopass = Str::random(8);
            $input['password'] = bcrypt($autopass);
            $user->update($input);
            $subject = "Reset Password Request";
            $msg = "Your New Password is : " . $autopass;
            if ($ps->get('mail_driver')) {
                $data = [
                    'to' => $request->email,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $ps->get('from_name') . "<" . $ps->get('from_email') . ">";
                mail($request->email, $subject, $msg, $headers);
            }
            return back()->with('success', 'New Password has been sent to your email.');
        } else {
            return back()->with('unsuccess', 'No Account Found With This Email.');
        }
    }

}
