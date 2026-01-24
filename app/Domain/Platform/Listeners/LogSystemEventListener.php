<?php

namespace App\Domain\Platform\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log System Event Listener
 *
 * Logs important system events for auditing.
 */
class LogSystemEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $eventName = get_class($event);
        $eventData = $this->extractEventData($event);

        Log::channel('system')->info('System event occurred', [
            'event' => $eventName,
            'data' => $eventData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Extract loggable data from event.
     */
    protected function extractEventData($event): array
    {
        $data = [];

        // Extract public properties
        $reflection = new \ReflectionClass($event);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $value = $property->getValue($event);

            // Don't log sensitive data
            if (in_array($name, ['password', 'token', 'secret'])) {
                $data[$name] = '[REDACTED]';
            } else {
                $data[$name] = is_object($value) ? get_class($value) : $value;
            }
        }

        return $data;
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Failed to log system event', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }
}
