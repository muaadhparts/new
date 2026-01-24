<?php

namespace App\Domain\Merchant\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Merchant Event Subscriber
 *
 * Handles all merchant-related events in one place.
 */
class MerchantEventSubscriber
{
    /**
     * Handle merchant registered events.
     */
    public function handleMerchantRegistered($event): void
    {
        Log::channel('merchants')->info('Merchant registered', [
            'merchant_id' => $event->merchant->id ?? null,
            'name' => $event->merchant->name ?? null,
        ]);
    }

    /**
     * Handle merchant approved events.
     */
    public function handleMerchantApproved($event): void
    {
        Log::channel('merchants')->info('Merchant approved', [
            'merchant_id' => $event->merchant->id ?? null,
        ]);
    }

    /**
     * Handle merchant suspended events.
     */
    public function handleMerchantSuspended($event): void
    {
        Log::channel('merchants')->warning('Merchant suspended', [
            'merchant_id' => $event->merchant->id ?? null,
            'reason' => $event->reason ?? null,
        ]);
    }

    /**
     * Handle merchant settings updated events.
     */
    public function handleSettingsUpdated($event): void
    {
        Log::channel('merchants')->info('Merchant settings updated', [
            'merchant_id' => $event->merchant->id ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Merchant\Events\MerchantRegistered' => 'handleMerchantRegistered',
            'App\Domain\Merchant\Events\MerchantApproved' => 'handleMerchantApproved',
            'App\Domain\Merchant\Events\MerchantSuspended' => 'handleMerchantSuspended',
            'App\Domain\Merchant\Events\MerchantSettingsUpdated' => 'handleSettingsUpdated',
        ];
    }
}
