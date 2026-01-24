<?php

namespace App\Domain\Platform\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Clear Cache Listener
 *
 * Clears related cache when data changes.
 */
class ClearCacheListener
{
    /**
     * Cache keys to clear by event type.
     */
    protected array $cacheMap = [
        'category_updated' => ['categories_tree', 'categories_menu', 'categories_list'],
        'brand_updated' => ['active_brands', 'brands_list'],
        'product_updated' => ['featured_items', 'new_arrivals'],
        'settings_updated' => ['platform_settings', 'site_settings'],
        'currency_updated' => ['default_currency', 'active_currencies'],
    ];

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $eventType = $event->type ?? $this->guessEventType($event);
        $keys = $this->cacheMap[$eventType] ?? [];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Also clear any entity-specific cache
        if (method_exists($event, 'getCacheKeys')) {
            foreach ($event->getCacheKeys() as $key) {
                Cache::forget($key);
            }
        }

        if (!empty($keys)) {
            Log::info('Cache cleared', [
                'event_type' => $eventType,
                'keys' => $keys,
            ]);
        }
    }

    /**
     * Guess event type from class name.
     */
    protected function guessEventType($event): string
    {
        $className = class_basename(get_class($event));

        // Convert CamelCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
