<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;

/**
 * Middleware للوصول الأساسي للتاجر (موثق أو غير موثق)
 *
 * يسمح بالوصول للتجار في الحالتين:
 * - is_merchant = 1: تاجر غير موثق (تحت المراجعة) - يمكنه رؤية الداشبورد ورفع المستندات فقط
 * - is_merchant = 2: تاجر موثق (معتمد) - وصول كامل
 *
 * للميزات التي تتطلب تاجر موثق فقط، استخدم middleware 'trusted.merchant'
 *
 * @see \App\Http\Middleware\TrustedMerchant
 */
class Merchant
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

        // التحقق من أن المستخدم تاجر (موثق أو غير موثق)
        // is_merchant = 0: ليس تاجر
        // is_merchant = 1: تاجر غير موثق
        // is_merchant = 2: تاجر موثق
        if (Auth::user()->is_merchant < 1) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Merchant access required.'], 403);
            }
            return redirect()->route('user.login')->with('error', __('You must be a merchant to access this page.'));
        }

        return $next($request);
    }
}
