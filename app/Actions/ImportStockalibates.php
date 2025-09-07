<?php

namespace App\Actions;

use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use XBase\TableReader;

class ImportStockalibates
{
    use AsAction;

    public string $commandSignature = 'import:stock-data';
    public string $commandDescription = 'Import stock CSV/DBF files from storage/app/stock/csv into stocks_alibates table';

    public function handle(): int
    {
        $files = Storage::disk('local')->allFiles('stock/csv');
        $records = new Collection();

        foreach ($files as $file) {
            $table = new TableReader(
                Storage::disk('local')->path($file),
                ['encoding' => 'cp1251'] // غيّرها إلى cp1256 لو الملفات بالعربي
            );

            while ($record = $table->nextRecord()) {
                $records->push([
                    'part_number' => (string) trim($record->get('fitemno')),
                    'branch_id'   => (int) $record->get('fbranch'),
                    'location'    => $record->get('flocation'),
                    'qty'         => (int) $record->get('fqtyonhand'),
                    'sell_price'  => $record->get('fsellprice'),
                    'comp_cost'   => $record->get('fcompcost'),
                    'cost_price'  => $record->get('fcostprice'),
                ]);
            }
        }

        // صَفّر الكميات قبل الاستيراد الجديد
        Stock::query()->update(['qty' => 0]);

        $batchSize = 300;
        $total = 0;

        $records->chunk($batchSize)->each(function ($chunk) use (&$total) {
            Stock::upsert(
                $chunk->toArray(),
                ['part_number', 'location'], // المفاتيح الأساسية
                ['branch_id', 'qty', 'sell_price', 'comp_cost', 'cost_price', 'updated_at']
            );
            $total += $chunk->count();
        });

        return $total;
    }

    public function asCommand(Command $command): void
    {
        $count = $this->handle();
        $command->info("Imported/Upserted rows: {$count}");
        $command->info('Done!');
    }
}
