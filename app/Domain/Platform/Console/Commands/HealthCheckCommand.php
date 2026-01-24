<?php

namespace App\Domain\Platform\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Health Check Command
 *
 * Performs system health checks.
 */
class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'platform:health-check
                            {--json : Output as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Perform system health checks';

    /**
     * Health check results
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        $this->checkDatabase();
        $this->checkCache();
        $this->checkRedis();
        $this->checkStorage();
        $this->checkQueue();

        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
            return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
        }

        $this->displayResults();

        return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->results['database'] = [
                'status' => 'ok',
                'message' => 'Connected to ' . config('database.default'),
            ];
        } catch (\Exception $e) {
            $this->results['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache
     */
    protected function checkCache(): void
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');

            $this->results['cache'] = [
                'status' => $value === 'ok' ? 'ok' : 'error',
                'message' => 'Using ' . config('cache.default'),
            ];
        } catch (\Exception $e) {
            $this->results['cache'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis
     */
    protected function checkRedis(): void
    {
        if (config('cache.default') !== 'redis') {
            $this->results['redis'] = [
                'status' => 'skip',
                'message' => 'Not using Redis',
            ];
            return;
        }

        try {
            Redis::ping();
            $this->results['redis'] = [
                'status' => 'ok',
                'message' => 'Connected',
            ];
        } catch (\Exception $e) {
            $this->results['redis'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage
     */
    protected function checkStorage(): void
    {
        try {
            $disk = Storage::disk('public');
            $testFile = 'health_check_' . time() . '.txt';
            $disk->put($testFile, 'ok');
            $disk->delete($testFile);

            $this->results['storage'] = [
                'status' => 'ok',
                'message' => 'Writable',
            ];
        } catch (\Exception $e) {
            $this->results['storage'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue
     */
    protected function checkQueue(): void
    {
        $driver = config('queue.default');

        $this->results['queue'] = [
            'status' => 'ok',
            'message' => 'Using ' . $driver,
        ];
    }

    /**
     * Display results as table
     */
    protected function displayResults(): void
    {
        $rows = [];

        foreach ($this->results as $check => $result) {
            $status = match ($result['status']) {
                'ok' => '<fg=green>✓ OK</>',
                'error' => '<fg=red>✗ ERROR</>',
                'skip' => '<fg=yellow>- SKIP</>',
                default => $result['status'],
            };

            $rows[] = [ucfirst($check), $status, $result['message']];
        }

        $this->table(['Check', 'Status', 'Message'], $rows);
    }

    /**
     * Check if any health check failed
     */
    protected function hasFailures(): bool
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'error') {
                return true;
            }
        }

        return false;
    }
}
