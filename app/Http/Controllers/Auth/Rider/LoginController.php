<?php

namespace App\Http\Controllers\Auth\Rider;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['except' => ['logout', 'userLogout']]);
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        // Attempt to log the user in
        if (Auth::guard('rider')->attempt(['email' => $request->email, 'password' => $request->password])) {
            // if successful, then redirect to their intended location

            // Check If Email is verified or not
            if (Auth::guard('rider')->user()->email_verified == 'No') {
                Auth::guard('rider')->logout();
                return redirect()->back()->with('unsuccess', __('Your Email is not Verified!'));
            }

            if (Auth::guard('rider')->user()->ban == 1) {
                Auth::guard('rider')->logout();
                return redirect()->back()->with('unsuccess', __('Your Account Has Been Banned.'));
            }

            // Login as User

            return redirect()->route('rider-dashboard');
        }

        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->back()->with('unsuccess', __('Credentials Doesn\'t Match !'));
    }

    public function logout()
    {
        Auth::guard('rider')->logout();
        return redirect('/');
    }
}
