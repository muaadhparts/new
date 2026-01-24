<?php

namespace App\Domain\Commerce\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Cart Not Empty Middleware
 *
 * Verifies that the user's cart is not empty before checkout.
 */
class EnsureCartNotEmpty
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('cart.empty'),
                    'error' => 'cart_empty',
                ], 400);
            }

            return redirect()->route('cart.index')
                ->with('error', __('cart.empty'));
        }

        return $next($request);
    }
}
