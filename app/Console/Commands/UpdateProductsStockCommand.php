<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductsStockCommand extends Command
{
    /**
     * options:
     *  --user_id= : target vendor user_id (default: 59)
     */
    protected $signature = 'products:update-stock 
                            {--user_id=59 : Vendor user_id to update stock for}';

    protected $description = 'Update merchant_products.stock from stock_all.qty for the given vendor.';

    public function handle(): int
    {
        $userId = (int) $this->option('user_id');

        // dd(['userId' => $userId]); // فحص سريع — أبقه مقفولًا

        if ($userId <= 0) {
            $this->error('Invalid --user_id value. It must be a positive integer.');
            return self::INVALID;
        }

        $this->info("Updating merchant_products.stock from stock_all.qty for user_id={$userId}...");

        // إحصائيات قبل التحديث:
        $stats = DB::selectOne("
            SELECT
                SUM(CASE WHEN s.part_number IS NOT NULL THEN 1 ELSE 0 END) AS matches,
                SUM(CASE WHEN s.part_number IS NOT NULL AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0) THEN 1 ELSE 0 END) AS needs_update,
                SUM(CASE WHEN s.part_number IS NULL THEN 1 ELSE 0 END) AS missing_in_stock_all
            FROM merchant_products mp
            JOIN products p ON p.id = mp.product_id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            WHERE mp.user_id = ?
        ", [$userId]);

        $matches = (int)($stats->matches ?? 0);
        $needs   = (int)($stats->needs_update ?? 0);
        $missing = (int)($stats->missing_in_stock_all ?? 0);

        $this->line("Matches with stock_all:   {$matches}");
        $this->line("Rows needing update:      {$needs}");
        $this->line("Missing in stock_all:     {$missing}");

        // التحديث الفعلي — فقط المختلفين
        $affected = DB::update("
            UPDATE merchant_products mp
            JOIN products p ON mp.product_id = p.id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            SET mp.stock = COALESCE(s.qty, 0)
            WHERE mp.user_id = ?
              AND s.part_number IS NOT NULL
              AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0)
        ", [$userId]);

        if ($affected > 0) {
            $this->info("✔ Updated stock for {$affected} merchant products.");
        } else {
            $this->warn("ℹ No merchant products updated. Maybe stock is already up-to-date.");
        }

        // تأكيد بعد التحديث
        $remaining = DB::selectOne("
            SELECT
                SUM(CASE WHEN s.part_number IS NOT NULL AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0) THEN 1 ELSE 0 END) AS remaining
            FROM merchant_products mp
            JOIN products p ON p.id = mp.product_id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            WHERE mp.user_id = ?
        ", [$userId]);

        $remainingDiff = (int)($remaining->remaining ?? 0);
        $this->line("Remaining mismatches:     {$remainingDiff}");

        return self::SUCCESS;
    }
}
