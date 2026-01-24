<?php

namespace App\Domain\Platform\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Platform\Models\PlatformSetting;
use App\Domain\Platform\Observers\MonetaryUnitObserver;
use App\Domain\Platform\Observers\PlatformSettingObserver;

/**
 * Platform Domain Service Provider
 *
 * Registers platform-specific services, observers, and policies.
 */
class PlatformServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register platform-specific bindings
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
        MonetaryUnit::observe(MonetaryUnitObserver::class);
        PlatformSetting::observe(PlatformSettingObserver::class);
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
