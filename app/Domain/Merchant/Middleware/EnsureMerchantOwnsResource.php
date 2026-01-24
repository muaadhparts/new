<?php

namespace App\Domain\Merchant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Merchant Owns Resource Middleware
 *
 * Verifies that the merchant owns the requested resource.
 */
class EnsureMerchantOwnsResource
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $parameterName = 'id', string $column = 'merchant_id'): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        // Get the resource from route binding
        $resource = $request->route($parameterName);

        if ($resource === null) {
            return $next($request);
        }

        // If it's a model instance
        if (is_object($resource)) {
            $ownerId = $resource->{$column} ?? $resource->user_id ?? null;
        } else {
            // If it's just an ID, we can't check ownership here
            return $next($request);
        }

        if ($ownerId !== null && $ownerId !== $user->id) {
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
                'message' => __('merchant.not_owner'),
                'error' => 'not_owner',
            ], 403);
        }

        abort(403, __('merchant.not_owner'));
    }
}
