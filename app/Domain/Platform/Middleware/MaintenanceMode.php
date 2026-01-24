<?php

namespace App\Domain\Platform\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance Mode Middleware
 *
 * Checks if the platform is in maintenance mode.
 */
class MaintenanceMode
{
    /**
     * IPs that can bypass maintenance mode
     */
    protected array $allowedIps = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        $maintenanceEnabled = $this->isMaintenanceEnabled();

        if (!$maintenanceEnabled) {
            return $next($request);
        }

        // Allow operators through
        if ($this->isOperator($request)) {
            return $next($request);
        }

        // Allow specific IPs
        if ($this->isAllowedIp($request)) {
            return $next($request);
        }

        // Allow API health checks
        if ($request->is('api/health', 'api/status')) {
            return $next($request);
        }

        return $this->maintenanceResponse($request);
    }

    /**
     * Check if maintenance mode is enabled
     */
    protected function isMaintenanceEnabled(): bool
    {
        try {
            return (bool) \App\Domain\Platform\Models\PlatformSetting::getValue('maintenance_mode', false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if request is from an operator
     */
    protected function isOperator(Request $request): bool
    {
        return auth('operator')->check();
    }

    /**
     * Check if IP is allowed
     */
    protected function isAllowedIp(Request $request): bool
    {
        $allowedIps = config('app.maintenance_allowed_ips', $this->allowedIps);
        return in_array($request->ip(), $allowedIps);
    }

    /**
     * Return maintenance response
     */
    protected function maintenanceResponse(Request $request): Response
    {
        $message = \App\Domain\Platform\Models\PlatformSetting::getValue(
            'maintenance_message',
            __('platform.maintenance_mode')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'maintenance_mode',
            ], 503);
        }

        return response()->view('errors.maintenance', [
            'message' => $message,
        ], 503);
    }
}
