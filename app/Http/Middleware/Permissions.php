<?php

namespace App\Http\Middleware;
use Auth;
use Closure;

class Permissions
{

    public function handle($request, Closure $next,$data)
    {
        if (Auth::guard('operator')->check()) {
            if(Auth::guard('operator')->user()->id == 1){
                return $next($request);
            }
            if(Auth::guard('operator')->user()->role_id == 0){
                return redirect()->route('operator.dashboard')->with('unsuccess',"You don't have access to that section"); 
            }
            if (Auth::guard('operator')->user()->sectionCheck($data)){
                return $next($request);
            }
        }
        return redirect()->route('operator.dashboard')->with('unsuccess',"You don't have access to that section"); 
    }
}
