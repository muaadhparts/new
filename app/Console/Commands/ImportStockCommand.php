<?php

namespace App\Console\Commands;

use App\Actions\ImportStock;
use Illuminate\Console\Command;

class ImportStockCommand extends Command
{
    protected $signature = 'import:stock-data {--local=}';
    protected $description = 'Import stock data (DBF/CSV) into stocks table using upsert';

    public function handle(): int
    {
        $local = $this->option('local') ?: null;
        $count = ImportStock::run($local);

        $this->info("Imported/Upserted rows: {$count}");
        // dd('import:stock-data', $count); // ← فحص سريع

        return self::SUCCESS;
    }
}
