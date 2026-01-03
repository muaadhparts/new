<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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

        // حفظ اللغة والعملة فقط (بيانات أساسية)
        $languageBackup = Session::get('language');
        $currencyBackup = Session::get('currency');

        // Remember Me: إذا تم تحديد الخيار، يبقى مسجل الدخول
        $remember = $request->has('remember') || $request->remember == 1;

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
            // التحقق من تأكيد البريد الإلكتروني
            if (Auth::guard('web')->user()->email_verified == 'No') {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Email is not Verified!'));
            }

            // التحقق من الحظر
            if (Auth::guard('web')->user()->ban == 1) {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Account is Banned!'));
            }

            // استعادة اللغة والعملة
            if ($languageBackup) {
                Session::put('language', $languageBackup);
            }
            if ($currencyBackup) {
                Session::put('currency', $currencyBackup);
            }

            // إذا كان تسجيل دخول البائع
            if ($request->merchant == 1) {
                return redirect()->route('merchant.dashboard');
            }

            // التوجيه دائماً إلى لوحة تحكم المستخدم
            return redirect()->route('user-dashboard');
        }

        // فشل تسجيل الدخول
        return redirect()->back()->with('unsuccess', __('Invalid Email or Password!'));
    }

    public function logout()
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        return redirect('/');
    }
}
