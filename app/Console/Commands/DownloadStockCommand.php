<?php

namespace App\Console\Commands;

use App\Actions\DownloadStock;
use Illuminate\Console\Command;

class DownloadStockCommand extends Command
{
    protected $signature = 'download:stock {--remote=} {--local=}';
    protected $description = 'Download stock file from remote disk to local storage';

    public function handle(): int
    {
        $remote = $this->option('remote') ?: null;
        $local  = $this->option('local') ?: null;

        $path = DownloadStock::run($remote, $local);

        $this->info("Stock file downloaded to: {$path}");
        // dd('download:stock', $path); // ← فحص سريع

        return self::SUCCESS;
    }
}
