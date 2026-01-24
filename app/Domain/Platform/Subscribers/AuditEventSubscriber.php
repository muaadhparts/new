<?php

namespace App\Domain\Platform\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Audit Event Subscriber
 *
 * Handles all audit-related events for security logging.
 */
class AuditEventSubscriber
{
    /**
     * Handle model created events for auditing.
     */
    public function handleModelCreated($event): void
    {
        Log::channel('audit')->info('Model created', [
            'model' => get_class($event->model),
            'id' => $event->model->id ?? null,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle model updated events for auditing.
     */
    public function handleModelUpdated($event): void
    {
        Log::channel('audit')->info('Model updated', [
            'model' => get_class($event->model),
            'id' => $event->model->id ?? null,
            'changes' => $event->model->getChanges(),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle model deleted events for auditing.
     */
    public function handleModelDeleted($event): void
    {
        Log::channel('audit')->warning('Model deleted', [
            'model' => get_class($event->model),
            'id' => $event->model->id ?? null,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle admin action events.
     */
    public function handleAdminAction($event): void
    {
        Log::channel('audit')->info('Admin action performed', [
            'action' => $event->action ?? null,
            'operator_id' => $event->operatorId ?? null,
            'target' => $event->target ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Platform\Events\ModelCreated' => 'handleModelCreated',
            'App\Domain\Platform\Events\ModelUpdated' => 'handleModelUpdated',
            'App\Domain\Platform\Events\ModelDeleted' => 'handleModelDeleted',
            'App\Domain\Platform\Events\AdminAction' => 'handleAdminAction',
        ];
    }
}
