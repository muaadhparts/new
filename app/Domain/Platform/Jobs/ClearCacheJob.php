<?php

namespace App\Domain\Platform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Clear Cache Job
 *
 * Clears specific cache keys or tags.
 */
class ClearCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $keys = [],
        public array $tags = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Clear specific keys
        foreach ($this->keys as $key) {
            Cache::forget($key);
        }

        // Clear tagged caches
        foreach ($this->tags as $tag) {
            try {
                Cache::tags($tag)->flush();
            } catch (\Exception $e) {
                // Tag caching might not be supported
                \Log::warning("Could not flush cache tag: {$tag}");
            }
        }

        \Log::info('Cache cleared', [
            'keys' => count($this->keys),
            'tags' => count($this->tags),
        ]);
    }
}
