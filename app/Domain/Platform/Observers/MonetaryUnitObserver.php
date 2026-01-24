<?php

namespace App\Domain\Platform\Observers;

use App\Domain\Platform\Models\MonetaryUnit;
use Illuminate\Support\Facades\Cache;

/**
 * Monetary Unit Observer
 *
 * Handles MonetaryUnit model lifecycle events.
 */
class MonetaryUnitObserver
{
    /**
     * Handle the MonetaryUnit "creating" event.
     */
    public function creating(MonetaryUnit $unit): void
    {
        // Ensure only one default currency
        if ($unit->is_default) {
            MonetaryUnit::where('is_default', true)->update(['is_default' => false]);
        }
    }

    /**
     * Handle the MonetaryUnit "created" event.
     */
    public function created(MonetaryUnit $unit): void
    {
        $this->clearCurrencyCache();
    }

    /**
     * Handle the MonetaryUnit "updating" event.
     */
    public function updating(MonetaryUnit $unit): void
    {
        // Ensure only one default currency
        if ($unit->isDirty('is_default') && $unit->is_default) {
            MonetaryUnit::where('id', '!=', $unit->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }

    /**
     * Handle the MonetaryUnit "updated" event.
     */
    public function updated(MonetaryUnit $unit): void
    {
        $this->clearCurrencyCache();
    }

    /**
     * Handle the MonetaryUnit "deleted" event.
     */
    public function deleted(MonetaryUnit $unit): void
    {
        $this->clearCurrencyCache();

        // If default was deleted, set first available as default
        if ($unit->is_default) {
            MonetaryUnit::first()?->update(['is_default' => true]);
        }
    }

    /**
     * Clear currency-related cache.
     */
    protected function clearCurrencyCache(): void
    {
        Cache::forget('default_currency');
        Cache::forget('active_currencies');
        Cache::forget('currency_rates');
    }
}
