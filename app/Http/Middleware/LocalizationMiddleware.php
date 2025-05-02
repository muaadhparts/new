<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use App\Models\Language;
use App\Models\Currency;

class LocalizationMiddleware
{
    public function handle($request, $next)
    {
        // Language setup
        if (Session::has('language')) {
            $language = Language::find(Session::get('language'));
        } else {
            $language = Language::where('is_default', 1)->first();
        }

        // Currency setup
        if (Session::has('currency')) {
            $currency = Currency::find(Session::get('currency'));
        } else {
            $currency = Currency::where('is_default', 1)->first();
        }

        // Share variables with all views
        view()->share('langg', $language);
        app()->setLocale($language->name);
        // dd($language);
        // Popup handling
        if (!Session::has('popup')) {
            view()->share('visited', 1);
        }
        Session::put('popup', 1);

        return $next($request);
    }
}
