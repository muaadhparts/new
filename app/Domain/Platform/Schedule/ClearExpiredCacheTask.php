<?php

namespace App\Domain\Platform\Schedule;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

/**
 * Clear Expired Cache Task
 *
 * Clears expired cache entries and optimizes cache.
 */
class ClearExpiredCacheTask
{
    /**
     * Cache keys to clear periodically.
     */
    protected array $periodicClearKeys = [
        'categories_tree',
        'active_brands',
        'featured_items',
        'platform_settings',
    ];

    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $cleared = 0;

        foreach ($this->periodicClearKeys as $key) {
            if (Cache::forget($key)) {
                $cleared++;
            }
        }

        // Clear view cache
        Artisan::call('view:clear');

        Log::info('Cache cleanup completed', [
            'keys_cleared' => $cleared,
            'view_cache_cleared' => true,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'dailyAt';
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '04:00';
    }
}
