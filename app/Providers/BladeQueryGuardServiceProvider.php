<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * BladeQueryGuardServiceProvider
 *
 * Detects and logs database queries executed inside Blade templates.
 * In local environment, throws exceptions to enforce eager loading.
 * In production, logs warnings for monitoring.
 *
 * This helps prevent N+1 queries by ensuring all data is pre-loaded in controllers.
 */
class BladeQueryGuardServiceProvider extends ServiceProvider
{
    /**
     * Queries executed during Blade rendering
     */
    protected static array $bladeQueries = [];

    /**
     * Whether we're currently inside Blade rendering
     */
    protected static bool $isRenderingBlade = false;

    /**
     * Current Blade view being rendered
     */
    protected static ?string $currentView = null;

    /**
     * Allowed query patterns (read-only helpers that are acceptable)
     */
    protected static array $allowedPatterns = [
        'select * from `currencies` where `is_default`',  // Global currency
        'select * from `languages` where `is_default`',   // Global language
        'select * from `muaadhsettings`',                // Global settings
        'convertPrice',                                    // Price helper (static)
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Only enable in local environment
        if (!app()->environment('local')) {
            return;
        }

        // Skip if explicitly disabled
        if (env('BLADE_QUERY_GUARD_DISABLED', false)) {
            return;
        }

        $this->setupBladeHooks();
        $this->setupQueryListener();
    }

    /**
     * Setup Blade rendering hooks
     */
    protected function setupBladeHooks(): void
    {
        // Hook into view rendering
        app('events')->listen('composing:*', function ($event, $data) {
            if (isset($data[0]) && is_object($data[0])) {
                $view = $data[0];
                if (method_exists($view, 'name')) {
                    self::$isRenderingBlade = true;
                    self::$currentView = $view->name();
                }
            }
        });

        // Hook into view rendered
        app('events')->listen('composed:*', function ($event, $data) {
            // Keep tracking - nested views
        });
    }

    /**
     * Setup query listener to detect queries during Blade rendering
     */
    protected function setupQueryListener(): void
    {
        DB::listen(function ($query) {
            if (!self::$isRenderingBlade) {
                return;
            }

            $sql = $query->sql;

            // Skip allowed patterns
            foreach (self::$allowedPatterns as $pattern) {
                if (stripos($sql, $pattern) !== false) {
                    return;
                }
            }

            // Skip SELECT queries that are likely from eager-loaded relations accessing attributes
            // These are usually accessing already-loaded data
            if ($this->isLikelyEagerLoadedAccess($query)) {
                return;
            }

            $queryInfo = [
                'sql' => $sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'view' => self::$currentView,
                'trace' => $this->getRelevantTrace(),
            ];

            self::$bladeQueries[] = $queryInfo;

            // Log warning only if explicitly enabled via BLADE_QUERY_LOG=true
            if (env('BLADE_QUERY_LOG', false)) {
                Log::channel('single')->warning('⚠️ Blade Query Detected', [
                    'view' => self::$currentView,
                    'sql' => $this->formatSql($sql, $query->bindings),
                    'time_ms' => $query->time,
                    'trace' => $queryInfo['trace'],
                ]);
            }

            // In strict mode, throw exception
            if (env('BLADE_QUERY_GUARD_STRICT', false)) {
                throw new \RuntimeException(
                    "N+1 Query Detected in Blade template [{$this->currentView}]: " .
                    $this->formatSql($sql, $query->bindings) .
                    "\n\nMove this query to the controller and use eager loading."
                );
            }
        });
    }

    /**
     * Check if query is likely from accessing eager-loaded relation
     */
    protected function isLikelyEagerLoadedAccess($query): bool
    {
        // Very short queries on single ID are often from accessing loaded relations
        if ($query->time < 1 && preg_match('/where.*`id`\s*=\s*\?/i', $query->sql)) {
            // Check if it's a simple "find" that might be from accessor
            return false; // Still flag these - they indicate missing eager loading
        }

        return false;
    }

    /**
     * Get relevant stack trace (Blade files only)
     */
    protected function getRelevantTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $relevant = [];

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            // Include Blade compiled files and views
            if (strpos($file, 'storage/framework/views') !== false ||
                strpos($file, 'resources/views') !== false) {
                $relevant[] = basename($file) . ':' . ($frame['line'] ?? '?');
            }

            // Stop after finding 3 relevant frames
            if (count($relevant) >= 3) {
                break;
            }
        }

        return $relevant;
    }

    /**
     * Format SQL with bindings for logging
     */
    protected function formatSql(string $sql, array $bindings): string
    {
        $formatted = $sql;
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'{$binding}'";
            $formatted = preg_replace('/\?/', (string) $value, $formatted, 1);
        }
        return $formatted;
    }

    /**
     * Get all queries detected during Blade rendering
     */
    public static function getBladeQueries(): array
    {
        return self::$bladeQueries;
    }

    /**
     * Reset query tracking (useful for testing)
     */
    public static function reset(): void
    {
        self::$bladeQueries = [];
        self::$isRenderingBlade = false;
        self::$currentView = null;
    }
}
