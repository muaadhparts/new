<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Platform\Services\SEO\IndexingApiService;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use Carbon\Carbon;

class IndexNewProducts extends Command
{
    protected $signature = 'seo:index-products
                            {--hours=24 : Index products created in last X hours}
                            {--limit=100 : Maximum URLs to submit}
                            {--dry-run : Show URLs without submitting}';

    protected $description = 'Submit new/updated products to Google Indexing API - إرسال المنتجات الجديدة لـ Google';

    public function handle(IndexingApiService $indexingApi)
    {
        $hours = $this->option('hours');
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        if (!$indexingApi->isConfigured()) {
            $this->error('Google Indexing API is not configured.');
            $this->info('Please set GOOGLE_INDEXING_CREDENTIALS in .env');
            return 1;
        }

        $this->info("Finding products created/updated in the last {$hours} hours...");

        // Get recently added/updated merchant items
        $merchantItems = MerchantItem::where('status', 1)
            ->where(function($q) use ($hours) {
                $q->where('created_at', '>=', Carbon::now()->subHours($hours))
                  ->orWhere('updated_at', '>=', Carbon::now()->subHours($hours));
            })
            ->with(['catalogItem', 'user'])
            ->limit($limit)
            ->get();

        if ($merchantItems->isEmpty()) {
            $this->info('No new products found.');
            return 0;
        }

        $this->info("Found {$merchantItems->count()} products to index.");

        $urls = [];
        foreach ($merchantItems as $item) {
            if (!$item->catalogItem || !$item->catalogItem->part_number) continue;

            $url = route('front.part-result', $item->catalogItem->part_number);

            $urls[] = $url;
        }

        if ($dryRun) {
            $this->info('Dry run - URLs that would be submitted:');
            foreach ($urls as $url) {
                $this->line("  - {$url}");
            }
            return 0;
        }

        $this->info('Submitting URLs to Google Indexing API...');
        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($urls as $url) {
            $result = $indexingApi->notifyUrlUpdated($url);

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
                $this->newLine();
                $this->warn("Failed: {$url} - " . ($result['error'] ?? 'Unknown error'));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Completed: {$success} succeeded, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
