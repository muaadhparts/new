<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Administrator
{
    /**
     * Handle an incoming request.
     * Ensures only authenticated admins can access protected routes.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if admin is authenticated
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
