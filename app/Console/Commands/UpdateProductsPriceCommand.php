<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductsPriceCommand extends Command
{
    protected $signature = 'products:update-price';
    protected $description = 'Update products.price from stock_all.cost_price (+30%) where user_id=0';

    public function handle(): int
    {
        $this->info('Updating products.price from stock_all.cost_price (+30%)...');

        $sql = "
            UPDATE products p
            JOIN stock_all s ON p.sku = s.part_number
            SET p.price = ROUND(s.cost_price * 1.3, 2)
            WHERE p.user_id = 0
        ";

        $affected = DB::update($sql);

        if ($affected > 0) {
           $this->info("✔ Updated prices for {$affected} products.");
        } else {
           $this->warn("ℹ No products updated. Maybe data is already up-to-date.");
        }

        return self::SUCCESS;
    }
}
