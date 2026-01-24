<?php

namespace App\Domain\Platform\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Clear Expired Cache Command
 *
 * Clears expired cache entries and optimizes cache storage.
 */
class ClearExpiredCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'platform:clear-expired-cache
                            {--prefix= : Cache key prefix to clear}
                            {--all : Clear all platform caches}';

    /**
     * The console command description.
     */
    protected $description = 'Clear expired cache entries';

    /**
     * Cache prefixes used by the platform
     */
    protected array $platformPrefixes = [
        'catalog_',
        'category_',
        'merchant_',
        'settings_',
        'navigation_',
        'seo_',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $prefix = $this->option('prefix');
        $all = $this->option('all');

        if ($all) {
            return $this->clearAllPlatformCaches();
        }

        if ($prefix) {
            return $this->clearPrefixedCache($prefix);
        }

        return $this->showCacheInfo();
    }

    /**
     * Clear all platform caches
     */
    protected function clearAllPlatformCaches(): int
    {
        $this->info('Clearing all platform caches...');

        foreach ($this->platformPrefixes as $prefix) {
            $this->clearPrefixedCache($prefix);
        }

        $this->info('All platform caches cleared.');

        return self::SUCCESS;
    }

    /**
     * Clear cache by prefix
     */
    protected function clearPrefixedCache(string $prefix): int
    {
        $this->line("Clearing cache with prefix: {$prefix}");

        // For Redis driver
        if (config('cache.default') === 'redis') {
            try {
                $keys = Redis::keys(config('cache.prefix') . ':' . $prefix . '*');
                if (!empty($keys)) {
                    Redis::del($keys);
                    $this->info("Cleared " . count($keys) . " keys with prefix: {$prefix}");
                }
            } catch (\Exception $e) {
                $this->warn("Redis error: {$e->getMessage()}");
            }
        }

        // For file driver, we can't selectively clear by prefix
        // So we just note that

        return self::SUCCESS;
    }

    /**
     * Show cache information
     */
    protected function showCacheInfo(): int
    {
        $this->info('Platform Cache Prefixes:');

        $this->table(
            ['Prefix', 'Description'],
            [
                ['catalog_', 'Catalog item caches'],
                ['category_', 'Category tree caches'],
                ['merchant_', 'Merchant data caches'],
                ['settings_', 'Platform settings'],
                ['navigation_', 'Navigation menus'],
                ['seo_', 'SEO data caches'],
            ]
        );

        $this->newLine();
        $this->line('Use --prefix=<prefix> to clear specific cache');
        $this->line('Use --all to clear all platform caches');

        return self::SUCCESS;
    }
}
