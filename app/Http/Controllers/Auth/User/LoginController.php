<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            if (Auth::guard('web')->user()->email_verified == 'No') {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Email is not Verified!'));
            }

            if (Auth::guard('web')->user()->ban == 1) {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Account is Banned!'));
            }

            if($request->vendor == 1) {
                return redirect()->route('vendor.dashboard');
            }

            

            return redirect()->route('user-dashboard');
        }
        return redirect()->back()->with('unsuccess', __('Invalid Email or Password!'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}
