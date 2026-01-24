<?php

namespace App\Domain\Catalog\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Catalog\Models\CatalogItem;

/**
 * Recalculate Ratings Command
 *
 * Recalculates product ratings from reviews.
 */
class RecalculateRatingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'catalog:recalculate-ratings
                            {--item= : Specific item ID to recalculate}
                            {--chunk=100 : Number of items to process at once}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate product ratings from reviews';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $itemId = $this->option('item');
        $chunkSize = (int) $this->option('chunk');

        if ($itemId) {
            return $this->recalculateSingle($itemId);
        }

        return $this->recalculateAll($chunkSize);
    }

    /**
     * Recalculate single item
     */
    protected function recalculateSingle(int $itemId): int
    {
        $item = CatalogItem::find($itemId);

        if (!$item) {
            $this->error("Item #{$itemId} not found.");
            return self::FAILURE;
        }

        $this->recalculateItem($item);
        $this->info("Rating recalculated for item #{$itemId}");

        return self::SUCCESS;
    }

    /**
     * Recalculate all items
     */
    protected function recalculateAll(int $chunkSize): int
    {
        $this->info('Starting ratings recalculation...');

        $count = 0;
        $bar = $this->output->createProgressBar(CatalogItem::count());

        CatalogItem::chunk($chunkSize, function ($items) use (&$count, $bar) {
            foreach ($items as $item) {
                $this->recalculateItem($item);
                $count++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Recalculated ratings for {$count} items.");

        return self::SUCCESS;
    }

    /**
     * Recalculate item rating
     */
    protected function recalculateItem(CatalogItem $item): void
    {
        $stats = $item->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        $item->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
            'rating_count' => $stats->count ?? 0,
        ]);
    }
}
