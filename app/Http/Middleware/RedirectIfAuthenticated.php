<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle($request, Closure $next, $guard = null)
  {
    switch ($guard) {
      case 'operator':
        if (Auth::guard($guard)->check()) {
          return redirect()->route('operator.dashboard');
        }
        break;

      case 'courier':
        if (Auth::guard($guard)->check()) {
          return redirect()->route('courier-dashboard');
        }
        break;

      default:
        if (Auth::guard($guard)->check()) {
          return redirect()->route('user-dashboard');
        }
        break;
    }

    return $next($request);
  }
}
