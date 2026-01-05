<?php

namespace App\Http\Middleware;
use Auth;
use Closure;

class SuperAdmin
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('operator')->check()) {
            if (Auth::guard('operator')->user()->IsSuper()){
                return $next($request);
            }
        }
        return redirect()->route('operator.dashboard')->with('unsuccess',"You don't have access to that section"); 
    }
}
