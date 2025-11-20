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

        // ✅ CRITICAL FIX: Save ALL session data BEFORE Auth::attempt()
        // Auth::attempt() regenerates session ID, which can cause data loss
        // We backup critical data and restore it immediately after authentication
        $cartBackup = Session::get('cart');
        $checkoutVendorId = Session::get('checkout_vendor_id');
        $languageBackup = Session::get('language');
        $currencyBackup = Session::get('currency');
        $couponBackup = Session::get('coupon');
        $couponTotalBackup = Session::get('coupon_total');

        // Backup all vendor-specific session data
        $vendorSessionBackup = [];
        if ($checkoutVendorId) {
            $vendorSessionBackup['vendor_step1_' . $checkoutVendorId] = Session::get('vendor_step1_' . $checkoutVendorId);
            $vendorSessionBackup['vendor_step2_' . $checkoutVendorId] = Session::get('vendor_step2_' . $checkoutVendorId);
            $vendorSessionBackup['coupon_vendor_' . $checkoutVendorId] = Session::get('coupon_vendor_' . $checkoutVendorId);
            $vendorSessionBackup['coupon_total_vendor_' . $checkoutVendorId] = Session::get('coupon_total_vendor_' . $checkoutVendorId);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Email verification check
            if (Auth::guard('web')->user()->email_verified == 'No') {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Email is not Verified!'));
            }

            // Ban check
            if (Auth::guard('web')->user()->ban == 1) {
                Auth::guard('web')->logout();
                return back()->with('unsuccess', __('Your Account is Banned!'));
            }

            // ✅ ALWAYS restore session data first
            // Restore cart
            if ($cartBackup) {
                Session::put('cart', $cartBackup);
            }

            // Restore language and currency
            if ($languageBackup) {
                Session::put('language', $languageBackup);
            }
            if ($currencyBackup) {
                Session::put('currency', $currencyBackup);
            }

            // Restore coupon data
            if ($couponBackup) {
                Session::put('coupon', $couponBackup);
            }
            if ($couponTotalBackup) {
                Session::put('coupon_total', $couponTotalBackup);
            }

            // Restore checkout vendor ID if exists
            if ($checkoutVendorId) {
                Session::put('checkout_vendor_id', $checkoutVendorId);

                // Restore all vendor-specific session data
                foreach ($vendorSessionBackup as $key => $value) {
                    if ($value !== null) {
                        Session::put($key, $value);
                    }
                }

                Session::save(); // Force save immediately

                \Log::info('Login SUCCESS: Restored checkout session', [
                    'user_id' => Auth::id(),
                    'vendor_id' => $checkoutVendorId,
                    'has_cart' => !empty($cartBackup)
                ]);
            }

            // ✅ REDIRECT LOGIC - تحسين تجربة المستخدم

            // 1. إذا كان vendor login (من صفحة vendor login)
            if($request->vendor == 1) {
                \Log::info('Login: Vendor login detected', ['user_id' => Auth::id()]);
                return redirect()->route('vendor.dashboard');
            }

            // 2. إذا كان قادماً من checkout (لديه checkout_vendor_id في session)
            // فقط في هذه الحالة نعيده لـ checkout
            if ($checkoutVendorId) {
                \Log::info('Login: User came from checkout - redirecting back', [
                    'user_id' => Auth::id(),
                    'vendor_id' => $checkoutVendorId
                ]);
                return redirect()->route('front.checkout.vendor', $checkoutVendorId);
            }

            // 3. خلاف ذلك: العودة للوحة التحكم (السلوك الطبيعي)
            \Log::info('Login: Normal login - redirecting to dashboard', [
                'user_id' => Auth::id(),
                'has_cart' => !empty($cartBackup)
            ]);
            return redirect()->route('user-dashboard');
        }

        // Login failed
        return redirect()->back()->with('unsuccess', __('Invalid Email or Password!'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}
