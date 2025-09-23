<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductsPriceCommand extends Command
{
    protected $signature = 'products:update-price';
    protected $description = 'Update merchant_products.price from stock_all.cost_price (+30%) for admin vendor (user_id=1)';

    public function handle(): int
    {
        $this->info('Updating merchant_products.price from stock_all.cost_price (+30%)...');

        // Update merchant_products table instead of products table
        $sql = "
            UPDATE merchant_products mp
            JOIN products p ON mp.product_id = p.id
            JOIN stock_all s ON p.sku = s.part_number
            SET mp.price = ROUND(s.cost_price * 1.3, 2)
            WHERE mp.user_id = 1
        ";

        $affected = DB::update($sql);

        if ($affected > 0) {
           $this->info("✔ Updated prices for {$affected} merchant products.");
        } else {
           $this->warn("ℹ No merchant products updated. Maybe data is already up-to-date.");
        }

        return self::SUCCESS;
    }
}
