<?php

namespace App\Domain\Platform\Schedule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Health Check Task
 *
 * Performs system health checks and logs status.
 */
class HealthCheckTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = !in_array(false, $checks, true);

        Log::channel($allHealthy ? 'daily' : 'slack')->log(
            $allHealthy ? 'info' : 'error',
            'System health check completed',
            [
                'status' => $allHealthy ? 'healthy' : 'unhealthy',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check cache connection.
     */
    protected function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 10);
            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): bool
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'test');
            $exists = \Storage::exists($testFile);
            \Storage::delete($testFile);
            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue connection.
     */
    protected function checkQueue(): bool
    {
        try {
            // Simple check - in real app, dispatch a test job
            return config('queue.default') !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'everyFiveMinutes';
    }
}
