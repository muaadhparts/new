<?php

// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;

// class UpdateProductsStockCommand extends Command
// {
//     protected $signature = 'products:update-stock';
//     protected $description = 'Update products.stock from stock_all (sku vs part_number), default to 0 if not found, only for user_id=0';

//     public function handle(): int
//     {
//         $this->info('Updating products stock from stock_all...');

//         $sql = "
//             UPDATE products p
//             LEFT JOIN stock_all s ON p.sku = s.part_number
//             SET p.stock = COALESCE(s.qty, 0)
//             WHERE p.user_id = 0
//         ";

//         $affected = DB::update($sql);

//         $this->info("✔ Updated rows: {$affected}");
//         return self::SUCCESS;
//     }
// }

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductsStockCommand extends Command
{
    protected $signature = 'products:update-stock';
    protected $description = 'Update products.stock from stock_all (sku vs part_number), default to 0 if not found, only for user_id=0';

    public function handle(): int
    {
        $this->info('Updating products stock from stock_all...');

        $sql = "
            UPDATE products p
            LEFT JOIN stock_all s ON p.sku = s.part_number
            SET p.stock = COALESCE(s.qty, 0)
            WHERE p.user_id = 0
        ";

        $affected = DB::update($sql);

        if ($affected > 0) {
            $this->info("✔ Updated rows: {$affected}");
        } else {
            $this->warn("ℹ No differences found — all products are already up to date.");
        }

        return self::SUCCESS;
    }
}
