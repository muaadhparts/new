<?php

namespace App\Domain\Commerce\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Commerce\Models\Purchase;

/**
 * Ensure Order Belongs To User Middleware
 *
 * Verifies that the order belongs to the authenticated user.
 */
class EnsureOrderBelongsToUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $parameterName = 'purchase'): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        $purchase = $request->route($parameterName);

        if (!$purchase) {
            return $next($request);
        }

        // Handle both model instances and IDs
        if (is_numeric($purchase)) {
            $purchase = Purchase::find($purchase);
        }

        if (!$purchase) {
            return $this->notFound($request);
        }

        if ($purchase->user_id !== $user->id) {
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
     * Return not found response
     */
    protected function notFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('order.not_found'),
                'error' => 'not_found',
            ], 404);
        }

        abort(404, __('order.not_found'));
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('order.not_owner'),
                'error' => 'not_owner',
            ], 403);
        }

        abort(403, __('order.not_owner'));
    }
}
