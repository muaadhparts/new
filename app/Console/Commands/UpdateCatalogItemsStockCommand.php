<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCatalogItemsStockCommand extends Command
{
    /**
     * options:
     *  --user_id= : target merchant user_id (default: 59)
     */
    protected $signature = 'catalog-items:update-stock
                            {--user_id=59 : Merchant user_id to update stock for}';

    protected $description = 'Update merchant_items.stock from stock_all.qty for the given merchant.';

    public function handle(): int
    {
        $userId = (int) $this->option('user_id');

        // dd(['userId' => $userId]); // فحص سريع — أبقه مقفولًا

        if ($userId <= 0) {
            $this->error('Invalid --user_id value. It must be a positive integer.');
            return self::INVALID;
        }

        $this->info("Updating merchant_items.stock from stock_all.qty for user_id={$userId}...");

        // إحصائيات قبل التحديث:
        $stats = DB::selectOne("
            SELECT
                SUM(CASE WHEN s.part_number IS NOT NULL THEN 1 ELSE 0 END) AS matches,
                SUM(CASE WHEN s.part_number IS NOT NULL AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0) THEN 1 ELSE 0 END) AS needs_update,
                SUM(CASE WHEN s.part_number IS NULL THEN 1 ELSE 0 END) AS missing_in_stock_all
            FROM merchant_items mp
            JOIN catalog_items p ON p.id = mp.catalog_item_id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            WHERE mp.user_id = ?
        ", [$userId]);

        $matches = (int)($stats->matches ?? 0);
        $needs   = (int)($stats->needs_update ?? 0);
        $missing = (int)($stats->missing_in_stock_all ?? 0);

        $this->line("Matches with stock_all:   {$matches}");
        $this->line("Rows needing update:      {$needs}");
        $this->line("Missing in stock_all:     {$missing}");

        // عرض تفصيلي للمنتجات الناقصة (التي سيتم تصفيرها)
        if ($missing > 0) {
            $this->warn("{$missing} catalog items will be ZEROED (not found in stock_all)");
        }

        // التحديث الفعلي — تحديث المختلفين + تصفير الناقصين
        $affected = DB::update("
            UPDATE merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            SET mp.stock = COALESCE(s.qty, 0)
            WHERE mp.user_id = ?
              AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0)
        ", [$userId]);

        if ($affected > 0) {
            $this->info("Updated stock for {$affected} merchant items.");

            // عرض عدد المنتجات المصفرة
            $zeroedCount = DB::selectOne("
                SELECT COUNT(*) as count
                FROM merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                LEFT JOIN stock_all s ON s.part_number = p.sku
                WHERE mp.user_id = ?
                  AND s.part_number IS NULL
                  AND mp.stock = 0
            ", [$userId]);

            if ($zeroedCount && $zeroedCount->count > 0) {
                $this->line("   Including {$zeroedCount->count} catalog items ZEROED (not found in stock_all - stock depleted)");
            }
        } else {
            $this->warn("No merchant items updated. Maybe stock is already up-to-date.");
        }

        // تأكيد بعد التحديث
        $remaining = DB::selectOne("
            SELECT
                SUM(CASE WHEN s.part_number IS NOT NULL AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0) THEN 1 ELSE 0 END) AS remaining
            FROM merchant_items mp
            JOIN catalog_items p ON p.id = mp.catalog_item_id
            LEFT JOIN stock_all s ON s.part_number = p.sku
            WHERE mp.user_id = ?
        ", [$userId]);

        $remainingDiff = (int)($remaining->remaining ?? 0);
        $this->line("Remaining mismatches:     {$remainingDiff}");

        return self::SUCCESS;
    }
}
