<?php

namespace App\Domain\Shipping\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Shipping Available Middleware
 *
 * Verifies that shipping is available to the user's location.
 */
class EnsureShippingAvailable
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cityId = $this->getCityId($request);

        if (!$cityId) {
            return $this->noLocationSet($request);
        }

        // Check if shipping is available to this city
        $city = \App\Domain\Shipping\Models\City::find($cityId);

        if (!$city || !$city->status) {
            return $this->shippingUnavailable($request);
        }

        return $next($request);
    }

    /**
     * Get city ID from request or session
     */
    protected function getCityId(Request $request): ?int
    {
        // From request
        if ($request->has('city_id')) {
            return (int) $request->get('city_id');
        }

        // From session
        if (session()->has('shipping_city_id')) {
            return session('shipping_city_id');
        }

        // From authenticated user's default address
        if ($user = $request->user()) {
            $defaultAddress = $user->addresses()
                ->where('is_default', true)
                ->first();
            if ($defaultAddress) {
                return $defaultAddress->city_id;
            }
        }

        return null;
    }

    /**
     * Return no location set response
     */
    protected function noLocationSet(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('shipping.location_required'),
                'error' => 'location_required',
            ], 400);
        }

        return redirect()->route('shipping.select-location')
            ->with('warning', __('shipping.location_required'));
    }

    /**
     * Return shipping unavailable response
     */
    protected function shippingUnavailable(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('shipping.unavailable_to_location'),
                'error' => 'shipping_unavailable',
            ], 400);
        }

        return back()->with('error', __('shipping.unavailable_to_location'));
    }
}
