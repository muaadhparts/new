<?php

namespace App\Providers;

use App\Services\SEO\IndexingApiService;
use App\Services\SEO\SeoService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register SeoService as singleton
        $this->app->singleton(SeoService::class, function ($app) {
            return new SeoService();
        });

        // Register IndexingApiService
        $this->app->singleton(IndexingApiService::class, function ($app) {
            return new IndexingApiService();
        });

        // Alias for easier access
        $this->app->alias(SeoService::class, 'seo');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives
        $this->registerBladeDirectives();

        // =========================================================
        // ملاحظة مهمة:
        // تم نقل view()->composer('*', ...) إلى GlobalDataMiddleware
        // SEO schemas تُحمَّل الآن مرة واحدة في GlobalDataService
        // بدلاً من تكرارها لكل view
        // =========================================================
    }

    /**
     * Register Blade directives for SEO
     */
    protected function registerBladeDirectives(): void
    {
        // @seoMeta - Render meta tags
        Blade::directive('seoMeta', function () {
            return '<?php echo app(\App\Services\SEO\SeoService::class)->renderMeta(); ?>';
        });

        // @seoSchemas - Render all schemas
        Blade::directive('seoSchemas', function () {
            return '<?php echo app(\App\Services\SEO\SeoService::class)->renderSchemas(); ?>';
        });

        // @seoCanonical - Render canonical URL
        Blade::directive('seoCanonical', function () {
            return '<?php
                $canonical = app(\App\Services\SEO\SeoService::class)->getCanonical();
                if ($canonical) {
                    echo \'<link rel="canonical" href="\' . e($canonical) . \'">\';
                }
            ?>';
        });

        // @consentMode - Render consent mode scripts
        Blade::directive('consentMode', function () {
            return '<?php echo \App\Services\SEO\ConsentModeService::renderConsentInit(); ?>';
        });

        // @consentScripts - Render consent update functions
        Blade::directive('consentScripts', function () {
            return '<?php echo \App\Services\SEO\ConsentModeService::renderConsentUpdateScript(); ?>';
        });

        // @cookieBanner - Render cookie consent banner
        Blade::directive('cookieBanner', function () {
            return '<?php echo \App\Services\SEO\ConsentModeService::renderCookieBanner(); ?>';
        });

        // @globalSchemas - Render Organization & Website schemas
        Blade::directive('globalSchemas', function () {
            return '<?php echo view("includes.seo.global-schemas")->render(); ?>';
        });
    }
}
