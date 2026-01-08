<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\SEO\SeoService;
use App\Services\SEO\ConsentModeService;
use App\Services\SEO\IndexingApiService;
use App\Services\SEO\Schema\OrganizationSchema;
use App\Services\SEO\Schema\WebsiteSchema;

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

        // Share global schemas with all views
        $this->shareGlobalSchemas();
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

    /**
     * Share global schemas with views
     */
    protected function shareGlobalSchemas(): void
    {
        // Share global schemas using view composer
        view()->composer('*', function ($view) {
            // Only add if not already added
            if (!isset($view->getData()['globalSchemasLoaded'])) {
                $gs = $view->getData()['gs'] ?? null;
                $seo = $view->getData()['seo'] ?? null;
                $social = $view->getData()['socialsetting'] ?? null;

                if ($gs) {
                    $view->with('organizationSchema', OrganizationSchema::fromSettings($gs, $seo, $social));
                    $view->with('websiteSchema', WebsiteSchema::fromSettings($gs));
                    $view->with('globalSchemasLoaded', true);
                }
            }
        });
    }
}
