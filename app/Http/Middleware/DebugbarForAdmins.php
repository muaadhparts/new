<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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

        // تحقق إذا كان المستخدم admin بعدة طرق:
        $isAdmin = $this->checkIfAdmin();

        if (!$isAdmin) {
            // ليس admin - عطّل Debugbar
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }

        return $next($request);
    }

    /**
     * تحقق من أن المستخدم admin بعدة طرق
     */
    protected function checkIfAdmin(): bool
    {
        // 1. تحقق من session flag (يعمل في كل مكان)
        if (Session::get('is_admin_logged_in') === true) {
            return true;
        }

        // 2. تحقق من admin guard
        if (Auth::guard('admin')->check()) {
            return true;
        }

        return false;
    }
}
