<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Closure;

class Vendor
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
        if (!Auth::user()->IsVendor()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Vendor access required.'], 403);
            }
            return redirect()->route('user.login')->with('error', __('You must be a vendor to access this page.'));
        }

        return $next($request);
    }
}
