<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // GEOCODING - AJAX requests from checkout
        'geocoding/*',
        'geocoding/reverse',
        'geocoding/sync-country',
        'geocoding/search-cities',
        'geocoding/sync-progress',
        // CHECKOUT
        'checkout/payment/paytm-notify',
        'checkout/payment/razorpay-notify',
        'cflutter/notify',
        'checkout/payment/ssl-notify',
        // SUBSCRIPTION
        'user/paytm-notify',
        'user/razorpay-notify',
        'uflutter/notify',
        'user/ssl-notify',
        // TOP-UP
        'user/topup/paytm-notify',
        'user/topup/razorpay-notify',
        'dflutter/notify',
        'user/topup/ssl-notify',
        // api
        '/api/flutter/notify',
        '/api/razorpay-callback',
        '/api/paytm-callback',
        '/api/ssl/notify',
        '/user/api/flutter/notify',
        '/user/api/paytm-callback',
        '/user/api/razorpay-callback',
        '/user/api/ssl/notify',
        // TRYOTO WEBHOOK
        '/webhooks/tryoto',
        'webhooks/tryoto'
    ];

    /**
     * Handle a token mismatch exception with detailed logging
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Session\TokenMismatchException  $exception
     * @return void
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $request->session()->token();

        $matches = is_string($sessionToken) &&
                   is_string($token) &&
                   hash_equals($sessionToken, $token);

        if (!$matches) {
            \Log::warning('CSRF Token Mismatch Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'request_token' => $token ? substr($token, 0, 10) . '...' : 'null',
                'session_token' => $sessionToken ? substr($sessionToken, 0, 10) . '...' : 'null',
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'session_id' => $request->session()->getId()
            ]);
        }

        return $matches;
    }
}
