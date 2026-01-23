<?php

namespace App\Http\Middleware;

use Closure;

class MaintenanceMode
{
    public function handle($request, Closure $next)
    {
        if (setting('is_maintain') == 1) {
            return redirect()->route('front-maintenance');
        }

        return $next($request);
    }
}
