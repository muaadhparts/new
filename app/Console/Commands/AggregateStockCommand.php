<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateStockCommand extends Command
{
    protected $signature = 'stock:aggregate';
    protected $description = 'Aggregate stocks into stock_all (sum qty and avg cost price)';

    public function handle(): int
    {
        $this->info('Aggregating stock data...');

        $sql = "
            INSERT INTO stock_all (part_number, qty, cost_price, created_at, updated_at)
            SELECT
                part_number,
                SUM(qty) AS total_qty,
                CASE 
                    WHEN SUM(qty) > 0 THEN ROUND(SUM(cost_price * qty) / SUM(qty), 4)
                    ELSE 0
                END AS avg_cost,
                NOW(),
                NOW()
            FROM stocks
            GROUP BY part_number
            ON DUPLICATE KEY UPDATE
                qty = VALUES(qty),
                cost_price = VALUES(cost_price),
                updated_at = NOW()
        ";

        DB::statement($sql);

        $this->info('Stock aggregation completed successfully.');

        return self::SUCCESS;
    }
}
