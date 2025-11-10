<?php

namespace App\Providers;

use App\Models\Currency;
use App\Models\Language;
use Illuminate\{Support\Facades\DB,
    Support\Collection,
    Support\Facades\URL,
    Support\ServiceProvider,
    Pagination\LengthAwarePaginator};

use App\Models\Font;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;


class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cache::flush(); // معطل مؤقتاً - يمسح الكاش في كل request
        Paginator::useBootstrap();



        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }


        view()->composer('*', function ($settings) {
            $settings->with('gs', cache()->remember('generalsettings', 3600, function () {
                return DB::table('generalsettings')->first();
            }));

            $settings->with('ps', cache()->remember('pagesettings', 3600, function () {
                return DB::table('pagesettings')->first();
            }));

            $settings->with('seo', cache()->remember('seotools', 3600, function () {
                return DB::table('seotools')->first();
            }));

            $settings->with('socialsetting', cache()->remember('socialsettings', 3600, function () {
                return DB::table('socialsettings')->first();
            }));

            $settings->with('default_font', cache()->remember('default_font', 3600, function () {
                return Font::whereIsDefault(1)->first();
            }));

            if (Session::has('currency')) {
                $settings->with('curr', Currency::find(Session::get('currency')));
            } else {
                $settings->with('curr', cache()->remember('default_currency', 3600, function () {
                    return Currency::where('is_default', '=', 1)->first();
                }));
            }

            if (Session::has('language')) {
                $settings->with('langg', Language::find(Session::get('language')));
            } else {
                $settings->with('langg', cache()->remember('default_language', 3600, function () {
                    return Language::where('is_default', '=', 1)->first();
                }));
            }
        });



//
//        view()->composer('*', function ($settings) {
//
//            $settings->with('gs', DB::table('generalsettings')->first());
//
//            $settings->with('ps', DB::table('pagesettings')->first());
//
//            $settings->with('seo',DB::table('seotools')->first());
//            $settings->with('socialsetting', DB::table('socialsettings')->first());
//
//            $settings->with('default_font', Font::whereIsDefault(1)->first());
//
//            if (Session::has('currency')) {
//                $settings->with('curr', Currency::find(Session::get('currency')));
//            } else {
//                $settings->with('curr', Currency::where('is_default', '=', 1)->first());
//            }
//
//            if (Session::has('language')) {
//                $settings->with('langg', Language::find(Session::get('language')));
//            } else {
//                $settings->with('langg', Language::where('is_default', '=', 1)->first());
//            }
//
//
//        });
    }

    public function register()
    {
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }
}