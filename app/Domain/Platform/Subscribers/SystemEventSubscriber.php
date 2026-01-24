<?php

namespace App\Domain\Platform\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * System Event Subscriber
 *
 * Handles all system-related events in one place.
 */
class SystemEventSubscriber
{
    /**
     * Handle settings changed events.
     */
    public function handleSettingsChanged($event): void
    {
        Log::channel('system')->info('Settings changed', [
            'key' => $event->key ?? null,
            'changed_by' => $event->changedBy ?? null,
        ]);
    }

    /**
     * Handle cache cleared events.
     */
    public function handleCacheCleared($event): void
    {
        Log::channel('system')->info('Cache cleared', [
            'type' => $event->type ?? 'all',
            'cleared_by' => $event->clearedBy ?? 'system',
        ]);
    }

    /**
     * Handle maintenance mode toggled events.
     */
    public function handleMaintenanceToggled($event): void
    {
        Log::channel('system')->warning('Maintenance mode toggled', [
            'enabled' => $event->enabled ?? false,
            'message' => $event->message ?? null,
        ]);
    }

    /**
     * Handle currency changed events.
     */
    public function handleCurrencyChanged($event): void
    {
        Log::channel('system')->info('Default currency changed', [
            'old_currency' => $event->oldCurrency ?? null,
            'new_currency' => $event->newCurrency ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Platform\Events\SettingsChanged' => 'handleSettingsChanged',
            'App\Domain\Platform\Events\CacheCleared' => 'handleCacheCleared',
            'App\Domain\Platform\Events\MaintenanceToggled' => 'handleMaintenanceToggled',
            'App\Domain\Platform\Events\CurrencyChanged' => 'handleCurrencyChanged',
        ];
    }
}
