<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Page;
use Illuminate\{Support\Facades\DB,
    Support\Collection,
    Support\Facades\URL,
    Support\ServiceProvider,
    Pagination\LengthAwarePaginator};

use App\Models\Font;
use App\View\Composers\HeaderComposer;
use App\Services\ApiCredentialService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;


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
            $settings->with('gs', cache()->remember('muaadhsettings', 3600, function () {
                return DB::table('muaadhsettings')->first();
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

            // Header data - Brands only (catalogs loaded on demand via AJAX)
            $settings->with('categories', cache()->remember('header_categories', 3600, function () {
                return Brand::where('status', 1)->orderBy('name')->get(['id', 'slug', 'name', 'name_ar', 'status']);
            }));

            $settings->with('pages', cache()->remember('header_pages', 3600, function () {
                return Page::all();
            }));

            $settings->with('currencies', cache()->remember('all_currencies', 3600, function () {
                return Currency::all();
            }));

            $settings->with('languges', cache()->remember('all_languages', 3600, function () {
                return Language::all();
            }));

            // Footer data - cached
            $settings->with('footerPages', cache()->remember('footer_pages', 3600, function () {
                return Page::where('footer', 1)->get();
            }));

            $settings->with('socialLinks', cache()->remember('footer_social_links', 3600, function () {
                return DB::table('social_links')->where('user_id', 0)->where('status', 1)->get();
            }));

            // Google Maps API Key - ONLY from api_credentials table, NO fallback to .env
            // POLICY: If key missing, Google Maps will NOT load (no empty key passed)
            $settings->with('googleMapsApiKey', cache()->remember('google_maps_api_key', 3600, function () {
                try {
                    $key = app(ApiCredentialService::class)->getGoogleMapsKey();
                    if (empty($key)) {
                        \Log::warning('Google Maps: API key not found in api_credentials table. Maps will be disabled.');
                        return null; // Return null, not empty string - Blade will check with !empty()
                    }
                    return $key;
                } catch (\Exception $e) {
                    \Log::error('Google Maps: Failed to retrieve API key from api_credentials', [
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            }));
        });

        // HeaderComposer: provides $authUser, $riderUser, $favoriteCount
        // Scoped to header-related views only (not all views)
        // Expected: 1 user query + 1 favorite query (cached) - NOT an error in Telescope
        View::composer([
            'includes.frontend.header',
            'includes.frontend.topbar',
            'includes.frontend.extra_head',
            'includes.frontend.mobile_menu',
        ], HeaderComposer::class);



//
//        view()->composer('*', function ($settings) {
//
//            $settings->with('gs', DB::table('muaadhsettings')->first());
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

        // =========================================================
        // BLADE DIRECTIVE: @themeStyles
        // =========================================================
        // Centralized theme CSS loading with cache-busting
        // Usage: @themeStyles in any blade template
        // Handles missing file gracefully
        Blade::directive('themeStyles', function () {
            return "<?php
                \$themeFile = public_path('assets/front/css/theme-colors.css');
                \$version = file_exists(\$themeFile) ? filemtime(\$themeFile) : time();
                echo '<link rel=\"stylesheet\" href=\"' . asset('assets/front/css/theme-colors.css') . '?v=' . \$version . '\">';
            ?>";
        });
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