<?php

namespace App\Providers;

use App\Domain\Shipping\Models\ShipmentTracking;
use App\Observers\ShipmentTrackingObserver;
use App\Domain\Platform\Services\GlobalData\GlobalDataService;
use App\Domain\Platform\Services\MonetaryUnitService;
use App\View\Composers\HeaderComposer;
use App\Domain\Merchant\ViewComposers\MerchantHeaderComposer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // === MonetaryUnitService Singleton ===
        // SINGLE SOURCE OF TRUTH for all currency operations
        // Usage: monetaryUnit()->format(100) or app(MonetaryUnitService::class)
        $this->app->singleton(MonetaryUnitService::class, function ($app) {
            return new MonetaryUnitService();
        });

        // === GlobalDataService Singleton ===
        // يُحمَّل مرة واحدة فقط لكل request
        // يُستخدم من GlobalDataMiddleware
        $this->app->singleton(GlobalDataService::class, function ($app) {
            return new GlobalDataService();
        });

        // === Collection Paginate Macro ===
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // === ShipmentTracking Observer ===
        ShipmentTracking::observe(ShipmentTrackingObserver::class);

        // === Pagination ===
        Paginator::useBootstrap();

        // === HTTPS in Production ===
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // =========================================================
        // ملاحظة مهمة:
        // تم نقل view()->composer('*', ...) إلى GlobalDataMiddleware
        // البيانات المشتركة تُحمَّل مرة واحدة في بداية الـ request
        // بدلاً من تكرارها لكل view
        // =========================================================

        // === HeaderComposer ===
        // يوفر $authUser, $courierUser, $favoriteCount
        // مُحدد لـ views معينة فقط (ليس كل الـ views)
        View::composer([
            'includes.frontend.header',
            'includes.frontend.topbar',
            'includes.frontend.extra_head',
            'includes.frontend.mobile_menu',
        ], HeaderComposer::class);

        // === MerchantHeaderComposer ===
        // يوفر $merchantNotifications للإشعارات
        // Blade Display Only - لا استعلامات في الـ views
        View::composer([
            'includes.merchant.header',
            'layouts.merchant',
        ], MerchantHeaderComposer::class);

        // === LocationViewComposer ===
        // يوفر $customerHasLocation, $customerLocationDisplay
        // لتجنب استدعاء Services في Views
        View::composer([
            'components.location-trigger',
        ], \App\Http\View\Composers\LocationViewComposer::class);

        // =========================================================
        // BLADE DIRECTIVE: @themeStyles
        // =========================================================
        Blade::directive('themeStyles', function () {
            return "<?php
                \$themeFile = public_path('assets/front/css/theme-colors.css');
                \$version = file_exists(\$themeFile) ? filemtime(\$themeFile) : time();
                echo '<link rel=\"stylesheet\" href=\"' . asset('assets/front/css/theme-colors.css') . '?v=' . \$version . '\">';
            ?>";
        });
    }
}
