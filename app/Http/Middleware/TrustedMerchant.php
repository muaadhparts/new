<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;

/**
 * Middleware للتحقق من أن التاجر موثق (is_merchant = 2)
 *
 * حالات is_merchant:
 * - 0: مستخدم عادي (ليس تاجر)
 * - 1: تاجر غير موثق (تحت المراجعة)
 * - 2: تاجر موثق (معتمد)
 */
class TrustedMerchant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // التحقق من تسجيل الدخول أولاً
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('user.login');
        }

        $user = Auth::user();

        // التحقق من أن المستخدم تاجر موثق (is_merchant = 2)
        if ($user->is_merchant != 2) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Trusted merchant access required.',
                    'error' => 'merchant_not_trusted'
                ], 403);
            }

            // إذا كان تاجر غير موثق (is_merchant = 1)، وجهه لرفع المستندات
            if ($user->is_merchant == 1) {
                return redirect()->route('merchant.dashboard')
                    ->with('warning', __('Please complete your trust badge verification to access this feature.'));
            }

            // إذا لم يكن تاجر أصلاً (is_merchant = 0)
            return redirect()->route('user-dashboard')
                ->with('error', __('You must be a trusted merchant to access this page.'));
        }

        return $next($request);
    }
}
