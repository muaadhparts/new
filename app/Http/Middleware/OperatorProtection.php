<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OperatorProtection
{
    /**
     * Master Protection Middleware
     *
     * This middleware protects the operator panel with a master password.
     * The password is hashed with bcrypt and cannot be reversed.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if already unlocked in this session
        if (session('operator_unlocked') === true && session('operator_unlock_time')) {
            // Check if unlock is still valid (24 hours)
            $unlockTime = session('operator_unlock_time');
            if (now()->diffInHours($unlockTime) < 24) {
                return $next($request);
            }
            // Expired, clear session
            session()->forget(['operator_unlocked', 'operator_unlock_time']);
        }

        // Allow access to the unlock page itself
        if ($request->routeIs('operator.unlock') || $request->routeIs('operator.unlock.verify')) {
            return $next($request);
        }

        // Redirect to unlock page
        return redirect()->route('operator.unlock');
    }
}
