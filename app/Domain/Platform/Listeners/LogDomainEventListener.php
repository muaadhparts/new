<?php

namespace App\Domain\Platform\Listeners;

use App\Domain\Platform\Events\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Domain Event Listener
 *
 * Logs all domain events for auditing and debugging.
 * This listener should be registered for all Domain Events.
 *
 * Channel Independence: This listener works for all channels
 * (Web, Mobile, API, WhatsApp) - same event = same logging.
 */
class LogDomainEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Number of times to retry
     */
    public int $tries = 3;

    /**
     * The queue to use
     */
    public string $queue = 'domain-events';

    /**
     * Handle the domain event.
     */
    public function handle(DomainEvent $event): void
    {
        Log::channel('domain')->info('Domain event dispatched', $event->toArray());
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        Log::channel('domain')->error('Failed to log domain event', [
            'event_id' => $event->eventId,
            'event_name' => $event->eventName(),
            'error' => $exception->getMessage(),
        ]);
    }
}
