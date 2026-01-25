<?php

namespace App\Http\Controllers\User;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Front\FrontBaseController;
use App\Domain\Catalog\Models\CatalogEvent;
use App\Domain\Identity\Models\User;
use Auth;
use Illuminate\Http\Request;
use Validator;

class RegisterController extends FrontBaseController
{

    public function showRegisterForm()
    {

        return view('frontend.register');
    }
    public function showMerchantRegisterForm()
    {
        if (setting('reg_merchant') == 1) {
            return view('frontend.merchant-register');
        } else {
            return back()->with('unsuccess', 'Merchant Registration is currently disabled by Admin. Please try again later.');
        }

    }

    public function register(Request $request)
    {

  
        //--- Validation Section

        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $user = new User;
        $input = $request->all();
        $input['password'] = bcrypt($request['password']);
        $token = md5(time() . $request->name . $request->email);
        $input['verification_link'] = $token;
        $input['affilate_code'] = md5($request->name . $request->email);

        if (!empty($request->merchant)) {
            //--- Validation Section
            $rules = [
                'shop_name' => 'unique:users',
                'shop_number' => 'max:10',
            ];
            $customs = [
                'shop_name.unique' => 'This Shop Name has already been taken.',
                'shop_number.max' => 'Shop Number Must Be Less Then 10 Digit.',
            ];

            $validator = Validator::make($request->all(), $rules, $customs);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            $input['is_merchant'] = 1;

        }

        $user->fill($input)->save();
        $ps = platformSettings();
        if ($ps->get('is_verification_email') == 1) {
            $to = $request->email;
            $subject = 'Verify your email address.';
            $msg = "Dear Customer,<br> We noticed that you need to verify your email address. <a href=" . url('user/register/verify/' . $token) . ">Simply click here to verify. </a>";
            //Sending Email To Customer
            if ($ps->get('mail_driver')) {
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $ps->get('from_name') . "<" . $ps->get('from_email') . ">";
                mail($to, $subject, $msg, $headers);
            }
            return response()->json('We need to verify your email address. We have sent an email to ' . $to . ' to verify your email address. Please click link in that email to continue.');
        } else {

            $user->email_verified = 'Yes';
            $user->update();
            $notification = new CatalogEvent;
            $notification->user_id = $user->id;
            $notification->save();
            Auth::guard('web')->login($user);
            return response()->json(1);
        }

    }

    public function token($token)
    {
        if (setting('is_verification_email') == 1) {
            $user = User::where('verification_link', '=', $token)->first();
            if (isset($user)) {
                $user->email_verified = 'Yes';
                $user->update();
                $notification = new CatalogEvent;
                $notification->user_id = $user->id;
                $notification->save();
                Auth::guard('web')->login($user);
                return redirect()->route('user-dashboard')->with('success', 'Email Verified Successfully');
            }
        } else {
            return redirect()->back();
        }
    }
}
