<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // إذا كان المستخدم يحاول الوصول لصفحات الفيندور
        if ($request->is('merchant/*') || $request->is('merchant')) {
            return route('user.login');
        }

        // إذا كان المستخدم يحاول الوصول لصفحات الأدمن
        if ($request->is('admin/*') || $request->is('admin')) {
            return route('operator.login');
        }

        return route('user.login');
    }
}
