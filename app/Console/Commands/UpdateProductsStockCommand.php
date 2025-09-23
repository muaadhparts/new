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
    protected $description = 'Update merchant_products.stock from stock_all (sku vs part_number), default to 0 if not found, for admin vendor (user_id=1)';

    public function handle(): int
    {
        $this->info('Updating merchant_products stock from stock_all...');

        // Update merchant_products table instead of products table
        $sql = "
            UPDATE merchant_products mp
            JOIN products p ON mp.product_id = p.id
            LEFT JOIN stock_all s ON p.sku = s.part_number
            SET mp.stock = COALESCE(s.qty, 0)
            WHERE mp.user_id = 1
        ";

        $affected = DB::update($sql);

        if ($affected > 0) {
            $this->info("✔ Updated rows: {$affected}");
        } else {
            $this->warn("ℹ No differences found — all merchant products are already up to date.");
        }

        return self::SUCCESS;
    }
}
