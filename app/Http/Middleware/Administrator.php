<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Administrator
{
    /**
     * Handle an incoming request.
     * Ensures only authenticated operators can access protected routes.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if operator is authenticated
        if (!Auth::guard('operator')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('operator.login');
        }

        return $next($request);
    }
}
