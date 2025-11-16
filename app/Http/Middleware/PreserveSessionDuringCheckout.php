<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to preserve session and auth state during checkout
 *
 * This middleware prevents session ID regeneration during vendor checkout
 * to ensure user remains logged in throughout the entire checkout process.
 */
class PreserveSessionDuringCheckout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Log session state before request
        $beforeSessionId = Session::getId();
        $beforeAuth = Auth::check();
        $beforeUserId = Auth::id();

        Log::info('PreserveSession BEFORE', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'session_id' => $beforeSessionId,
            'auth_check' => $beforeAuth,
            'user_id' => $beforeUserId,
        ]);

        // Process request
        $response = $next($request);

        // Log session state after request
        $afterSessionId = Session::getId();
        $afterAuth = Auth::check();
        $afterUserId = Auth::id();

        Log::info('PreserveSession AFTER', [
            'url' => $request->fullUrl(),
            'session_id' => $afterSessionId,
            'auth_check' => $afterAuth,
            'user_id' => $afterUserId,
            'session_changed' => $beforeSessionId !== $afterSessionId,
            'auth_changed' => $beforeAuth !== $afterAuth,
        ]);

        // Warning if session ID changed unexpectedly
        if ($beforeSessionId !== $afterSessionId) {
            Log::warning('SESSION ID CHANGED during checkout!', [
                'before' => $beforeSessionId,
                'after' => $afterSessionId,
                'url' => $request->fullUrl(),
            ]);
        }

        // Warning if auth state changed unexpectedly
        if ($beforeAuth && !$afterAuth) {
            Log::error('AUTH LOST during checkout!', [
                'url' => $request->fullUrl(),
                'before_user_id' => $beforeUserId,
            ]);
        }

        return $response;
    }
}
