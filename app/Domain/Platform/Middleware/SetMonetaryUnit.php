<?php

namespace App\Domain\Platform\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Monetary Unit Middleware
 *
 * Sets the monetary unit based on user preference or session.
 */
class SetMonetaryUnit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $monetaryUnitId = $this->determineMonetaryUnit($request);

        if ($monetaryUnitId) {
            monetaryUnit()->setCurrentById($monetaryUnitId);
        }

        return $next($request);
    }

    /**
     * Determine the monetary unit to use
     */
    protected function determineMonetaryUnit(Request $request): ?int
    {
        // 1. Check query parameter
        if ($request->has('currency')) {
            $currency = $request->get('currency');
            $unit = \App\Domain\Platform\Models\MonetaryUnit::where('code', $currency)
                ->where('status', 1)
                ->first();
            if ($unit) {
                session(['monetary_unit_id' => $unit->id]);
                return $unit->id;
            }
        }

        // 2. Check session
        if (session()->has('monetary_unit_id')) {
            return session('monetary_unit_id');
        }

        // 3. Return null to use default
        return null;
    }
}
