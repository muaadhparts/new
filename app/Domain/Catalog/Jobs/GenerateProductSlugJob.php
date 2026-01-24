<?php

namespace App\Domain\Catalog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Str;

/**
 * Generate Product Slug Job
 *
 * Generates unique slugs for products without slugs.
 */
class GenerateProductSlugJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $catalogItemId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->catalogItemId) {
            $this->generateForItem($this->catalogItemId);
            return;
        }

        $this->generateForAllWithoutSlug();
    }

    /**
     * Generate slug for single item
     */
    protected function generateForItem(int $itemId): void
    {
        $item = CatalogItem::find($itemId);

        if (!$item) {
            return;
        }

        $slug = $this->generateUniqueSlug($item);
        $item->update(['slug' => $slug]);
    }

    /**
     * Generate slugs for all items without slug
     */
    protected function generateForAllWithoutSlug(): void
    {
        CatalogItem::whereNull('slug')
            ->orWhere('slug', '')
            ->chunk(100, function ($items) {
                foreach ($items as $item) {
                    $slug = $this->generateUniqueSlug($item);
                    $item->update(['slug' => $slug]);
                }
            });
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(CatalogItem $item): string
    {
        $base = Str::slug($item->name ?: $item->sku ?: 'product-' . $item->id);
        $slug = $base;
        $count = 1;

        while (CatalogItem::where('slug', $slug)->where('id', '!=', $item->id)->exists()) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }
}
