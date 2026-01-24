<?php

namespace App\Domain\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Models\Operator;
use App\Domain\Identity\Observers\UserObserver;
use App\Domain\Identity\Observers\OperatorObserver;

/**
 * Identity Domain Service Provider
 *
 * Registers identity-specific services, observers, and policies.
 */
class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register identity-specific bindings
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
        User::observe(UserObserver::class);
        Operator::observe(OperatorObserver::class);
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
