<?php

namespace App\Domain\Shipping\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * Track Shipment Access Middleware
 *
 * Verifies that the user can access shipment tracking information.
 */
class TrackShipmentAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $parameterName = 'shipment'): Response
    {
        $shipment = $request->route($parameterName);

        if (!$shipment) {
            return $next($request);
        }

        // Handle both model instances and IDs
        if (is_numeric($shipment)) {
            $shipment = ShipmentTracking::find($shipment);
        }

        if (!$shipment) {
            return $this->notFound($request);
        }

        // Allow public tracking if tracking number is provided
        if ($request->has('tracking_number')) {
            if ($shipment->tracking_number === $request->get('tracking_number')) {
                return $next($request);
            }
        }

        $user = $request->user();

        // If authenticated, check ownership
        if ($user) {
            // Customer owns the order
            if ($shipment->merchantPurchase?->purchase?->user_id === $user->id) {
                return $next($request);
            }

            // Merchant owns the order
            if ($shipment->merchantPurchase?->merchant_id === $user->id) {
                return $next($request);
            }

            // Courier assigned to shipment
            if (auth('courier')->check() && $shipment->courier_id === auth('courier')->id()) {
                return $next($request);
            }
        }

        return $this->forbidden($request);
    }

    /**
     * Return not found response
     */
    protected function notFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('shipping.shipment_not_found'),
                'error' => 'not_found',
            ], 404);
        }

        abort(404, __('shipping.shipment_not_found'));
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('shipping.access_denied'),
                'error' => 'access_denied',
            ], 403);
        }

        abort(403, __('shipping.access_denied'));
    }
}
