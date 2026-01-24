<?php

namespace App\Domain\Accounting\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Accounting\Observers\AccountingLedgerObserver;
use App\Domain\Accounting\Observers\WithdrawObserver;

/**
 * Accounting Domain Service Provider
 *
 * Registers accounting-specific services, observers, and policies.
 */
class AccountingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register accounting-specific bindings
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
        AccountingLedger::observe(AccountingLedgerObserver::class);
        Withdraw::observe(WithdrawObserver::class);
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
