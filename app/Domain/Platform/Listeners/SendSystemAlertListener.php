<?php

namespace App\Domain\Platform\Listeners;

use App\Domain\Platform\Notifications\SystemAlertNotification;
use App\Models\Operator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send System Alert Listener
 *
 * Sends system alerts to administrators.
 */
class SendSystemAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Alert levels that should be sent immediately.
     */
    protected array $urgentLevels = ['error', 'critical'];

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $title = $event->title ?? 'System Alert';
        $message = $event->message ?? '';
        $level = $event->level ?? 'info';

        // Get administrators to notify
        $admins = $this->getAdministrators($level);

        if ($admins->isEmpty()) {
            Log::warning('No administrators to notify for system alert', [
                'title' => $title,
                'level' => $level,
            ]);
            return;
        }

        Notification::send($admins, new SystemAlertNotification(
            $title,
            $message,
            $level,
            $event->actionUrl ?? null,
            $event->actionText ?? null
        ));

        Log::info('System alert sent', [
            'title' => $title,
            'level' => $level,
            'recipients' => $admins->count(),
        ]);
    }

    /**
     * Get administrators based on alert level.
     */
    protected function getAdministrators(string $level)
    {
        $query = Operator::where('status', 1);

        // For urgent alerts, only notify super admins
        if (in_array($level, $this->urgentLevels)) {
            $query->where('role_id', 1); // Super admin role
        }

        return $query->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Failed to send system alert', [
            'title' => $event->title ?? 'Unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
