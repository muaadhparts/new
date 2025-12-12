<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DebugbarForAdmins
{
    /**
     * تفعيل Debugbar للمدراء فقط
     *
     * يتطلب: DEBUGBAR_ENABLED=true في .env
     * النتيجة: يظهر للمدراء فقط، مخفي للزوار
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق من وجود Debugbar
        if (!class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            return $next($request);
        }

        // تحقق من تفعيل Debugbar في الـ config
        if (!config('debugbar.enabled')) {
            return $next($request);
        }

        // تحقق إذا كان المستخدم admin
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            // ليس admin - عطّل Debugbar
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }
        // إذا كان admin - Debugbar يبقى مفعّل (الافتراضي)

        return $next($request);
    }
}
