<?php

namespace App\Domain\Commerce\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate Checkout Session Middleware
 *
 * Ensures checkout session data is valid and not expired.
 */
class ValidateCheckoutSession
{
    /**
     * Session expiry time in minutes
     */
    protected int $expiryMinutes = 30;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $checkoutSession = session('checkout', []);

        // Check if checkout session exists
        if (empty($checkoutSession)) {
            return $this->invalidSession($request, __('checkout.session_expired'));
        }

        // Check if session has expired
        $startedAt = $checkoutSession['started_at'] ?? null;
        if ($startedAt) {
            $expiresAt = \Carbon\Carbon::parse($startedAt)->addMinutes($this->expiryMinutes);
            if (now()->gt($expiresAt)) {
                session()->forget('checkout');
                return $this->invalidSession($request, __('checkout.session_expired'));
            }
        }

        // Validate required checkout data
        $requiredKeys = ['cart_snapshot', 'shipping_address'];
        foreach ($requiredKeys as $key) {
            if (!isset($checkoutSession[$key])) {
                return $this->invalidSession($request, __('checkout.incomplete_data'));
            }
        }

        return $next($request);
    }

    /**
     * Return invalid session response
     */
    protected function invalidSession(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'invalid_checkout_session',
            ], 400);
        }

        return redirect()->route('checkout.index')
            ->with('error', $message);
    }
}
