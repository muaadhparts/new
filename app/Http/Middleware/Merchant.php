<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Closure;

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

        // التحقق من أن المستخدم تاجر
        if (!Auth::user()->IsMerchant()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Merchant access required.'], 403);
            }
            return redirect()->route('user.login')->with('error', __('You must be a merchant to access this page.'));
        }

        return $next($request);
    }
}
