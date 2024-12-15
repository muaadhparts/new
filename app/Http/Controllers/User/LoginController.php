<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Front\FrontBaseController;
use App\Models\Generalsetting;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Vonage\Laravel\Facade\Vonage;

class LoginController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest', ['except' => ['logout', 'userLogout']]);
    }

    public function showLoginForm()
    {

        return view('frontend.login');
    }

    public function showOtpLoginForm()
    {

        return view('frontend.otp_login');
    }

    public function showOtpLoginFormSubmit(Request $request)
    {

        $request->validate([
            "phone" => "required",
        ]);

        $user = User::wherePhone($request->phone)->exists();
        if (!$user) {
            return back()->with("unsuccess", "User Not Found");
        }

        $user = User::wherePhone($request->phone)->first();
        $otp = rand(pow(10, 6 - 1), pow(10, 6) - 1);
        $user->otp = $otp;
        $user->save();

        $gs = Generalsetting::first();

        try {

            $vonageKey = $gs->vonage_key;
            $vonageSecret = $gs->vonage_secret;

            // Set the retrieved values into the config dynamically
            config([
                'vonage.api_key' => $vonageKey,
                'vonage.api_secret' => $vonageSecret,
            ]);

            $text = new \Vonage\SMS\Message\SMS($user->phone, $gs->from_number, 'Your OTP : ' . $otp);
            Vonage::sms()->send($text);

            return redirect(route("user.opt.login.view"));
        } catch (Exception $e) {
            return redirect(route("user.opt.login.view"));
            return back()->with("unsuccess", $e->getMessage());
        }
    }

    public function showOtpLoginFormView()
    {
        return view("frontend.otp_view");
    }

    public function showOtpLoginFormViewSubmit(Request $request)
    {
        $request->validate([
            "otp" => "required|max:6",
        ]);

        $user = User::whereOtp($request->otp)->exists();
        if (!$user) {
            return back()->with("unsuccess", "Otp not match");
        }

        $user = User::whereOtp($request->otp)->first();
        auth()->guard('web')->login($user);
        return redirect(route("user-dashboard"));

    }

    public function status($status)
    {
        return view('user.success', compact('status'));
    }

    public function showVendorLoginForm()
    {

        return view('frontend.vendor-login');
    }

    public function login(Request $request)
    {
        //--- Validation Section
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $request->validate($rules);

        //--- Validation Section Ends

        // Attempt to log the user in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // if successful, then redirect to their intended location

            // Check If Email is verified or not
            if (Auth::guard('web')->user()->email_verified == 'No') {
                Auth::guard('web')->logout();
                return response()->json(array('errors' => [0 => 'Your Email is not Verified!']));
            }

            if (Auth::guard('web')->user()->ban == 1) {
                Auth::guard('web')->logout();
                return response()->json(array('errors' => [0 => 'Your Account Has Been Banned.']));
            }

            // Login as User
            return response()->json(route('user-dashboard'));
        }

        // if unsuccessful, then redirect back to the login with the form data
        return response()->json(array('errors' => [0 => 'Credentials Doesn\'t Match !']));
    }

    public function logout()
    {
        Auth::guard('web')->logout();
        return redirect('/');
    }

}
