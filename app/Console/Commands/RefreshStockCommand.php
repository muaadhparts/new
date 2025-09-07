<?php

namespace App\Console\Commands;

use App\Actions\DownloadStock;
use App\Actions\ImportStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshStockCommand extends Command
{
    protected $signature = 'stock:refresh {--remote=} {--local=} {--with-aggregate}';
    protected $description = 'Download stock file then import it (full refresh)';

    public function handle(): int
    {
        $remote = $this->option('remote') ?: null;
        $local  = $this->option('local') ?: null;

        $path = DownloadStock::run($remote, $local);
        $this->info("Downloaded: {$path}");

        $count = ImportStock::run($local);
        $this->info("Imported rows: {$count}");

        // لو فيه خيار --with-aggregate
        if ($this->option('with-aggregate')) {
            $this->info("Running aggregation...");
            Artisan::call('stock:aggregate');
            $this->info("Aggregation done.");
        }

        return self::SUCCESS;
    }
}
