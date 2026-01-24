<?php

namespace App\Domain\Merchant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Merchant Is Active Middleware
 *
 * Verifies that the merchant account is active and not suspended.
 */
class EnsureMerchantIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        if ($user->role !== 'merchant') {
            return $this->forbidden($request, __('merchant.not_merchant'));
        }

        if ($user->status !== 1) {
            return $this->forbidden($request, __('merchant.account_inactive'));
        }

        if ($user->is_suspended) {
            return $this->forbidden($request, __('merchant.account_suspended'));
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'merchant_inactive',
            ], 403);
        }

        abort(403, $message);
    }
}
