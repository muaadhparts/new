<?php

namespace App\Console\Commands;

use App\Actions\DownloadStock;
use App\Actions\ImportStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class FullRefreshStockCommand extends Command
{
    protected $signature = 'stock:full-refresh {--branch=ATWJRY}';
    protected $description = 'Download stock files, import into stocks, aggregate into stock_all, and update catalogItems table';

    public function handle(): int
    {
        try {
            $branch = $this->option('branch');
            $this->info("🔽 Downloading stock files for branch: {$branch} ...");

            $downloaded = DownloadStock::run();
            if (empty($downloaded)) {
                $this->error("❌ No stock files found to download.");
                return self::FAILURE;
            }
            $this->info("✔ Download complete. Files: " . count($downloaded));

            $this->info("📥 Importing stock data into `stocks`...");
            $imported = ImportStock::run();
            $this->info("✔ Imported rows: {$imported}");

            $this->info("📊 Aggregating into `stock_all`...");
            Artisan::call('stock:aggregate');
            $this->line(Artisan::output());

            $this->info("🛠 Updating catalog items from stock_all...");
            Artisan::call('catalog-items:update-stock');
            $this->line(Artisan::output());

            $this->info("🎉 Full refresh + catalogItem update completed successfully.");
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("❌ Full refresh failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
