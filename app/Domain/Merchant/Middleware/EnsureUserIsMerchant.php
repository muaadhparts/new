<?php

namespace App\Domain\Merchant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Merchant Middleware
 *
 * Verifies that the authenticated user is a merchant.
 */
class EnsureUserIsMerchant
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
            return $this->forbidden($request);
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
    protected function forbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('merchant.not_merchant'),
                'error' => 'not_merchant',
            ], 403);
        }

        abort(403, __('merchant.not_merchant'));
    }
}
