<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DebugbarForAdmins
{
    /**
     * تفعيل Debugbar للمدراء فقط في الإنتاج
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تفعيل Debugbar للمدراء فقط
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            $admin = Auth::guard('admin')->user();

            // تحقق أن المستخدم مسجل دخول كـ admin
            if ($admin) {
                \Barryvdh\Debugbar\Facades\Debugbar::enable();
            } else {
                \Barryvdh\Debugbar\Facades\Debugbar::disable();
            }
        }

        return $next($request);
    }
}
