<?php

namespace App\Domain\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Observers\CatalogItemObserver;
use App\Domain\Catalog\Observers\CatalogReviewObserver;
use App\Domain\Catalog\Observers\CategoryObserver;
use App\Domain\Catalog\Observers\BrandObserver;

/**
 * Catalog Domain Service Provider
 *
 * Registers catalog-specific services, observers, and policies.
 */
class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register catalog-specific bindings
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerObservers();
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        CatalogItem::observe(CatalogItemObserver::class);
        CatalogReview::observe(CatalogReviewObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
