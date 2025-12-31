<?php

namespace App\Http\Middleware;

use App\Models\Muaadhsetting;
use Closure;
use Illuminate\Support\Facades\DB;

class MaintenanceMode
{
    public function handle($request, Closure $next)

    {

        $gs = cache()->remember('muaadhsettings', now()->addDay(), function () {
            return DB::table('muaadhsettings')->first();
        });


        if ($gs->is_maintain == 1) {
            return redirect()->route('front-maintenance');
        }


        return $next($request);
    }
}
