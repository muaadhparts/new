<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class CheckStockCommand extends Command
{
    protected $signature = 'stock:check {--details : Show top 10 latest updated parts}';
    protected $description = 'عرض عدد الأصناف وتاريخ آخر تحديث في جدول stocks';

    public function handle(): int
    {
        $count = Stock::count();
        $last  = Stock::orderByDesc('updated_at')->first();

        $this->info("عدد الأصناف: {$count}");

        if ($last) {
            $this->info("آخر تحديث: " . $last->updated_at);
        } else {
            $this->warn("جدول stocks فارغ.");
            return self::SUCCESS;
        }

        if ($this->option('details')) {
            $this->line("\nأحدث 10 أصناف:");
            $latest = Stock::orderByDesc('updated_at')->take(10)->get(['part_number', 'qty', 'updated_at']);
            $this->table(['Part Number', 'Qty', 'Updated At'], $latest->toArray());
        }

        return self::SUCCESS;
    }
}
