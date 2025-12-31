<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCatalogItemsPriceCommand extends Command
{
    /**
     * options:
     *  --user_id=   : target vendor user_id (default: 59)
     *  --margin=    : multiplier (e.g. 1.3 for +30%), default: 1.3
     */
    protected $signature = 'catalog-items:update-price
                            {--user_id=59 : Vendor user_id to update}
                            {--margin=1.3 : Price multiplier (e.g. 1.3 = +30%)}';

    protected $description = 'Update merchant_items.price from stock_all.cost_price * margin (default +30%) for the given vendor.';

    public function handle(): int
    {
        $userId = (int)$this->option('user_id');
        $margin = (float)$this->option('margin');

        // dd(['userId' => $userId, 'margin' => $margin]); // فحص سريع — أبقه مقفولًا

        if ($userId <= 0) {
            $this->error('Invalid --user_id value. It must be a positive integer.');
            return self::INVALID;
        }
        if ($margin <= 0) {
            $this->error('Invalid --margin value. It must be > 0 (e.g., 1.3 for +30%).');
            return self::INVALID;
        }

        $this->info("Updating merchant_items.price from stock_all.cost_price * {$margin} for user_id={$userId}...");

        // قبل التحديث: matches + needs_update
        $stats = DB::selectOne("
            SELECT
                COUNT(*) AS matches,
                SUM(CASE WHEN ROUND(s.cost_price * ?, 2) <> mp.price THEN 1 ELSE 0 END) AS needs_update
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            JOIN stock_all s ON p.sku = s.part_number
            WHERE mp.user_id = ?
        ", [$margin, $userId]);

        $matches = (int)($stats->matches ?? 0);
        $needsUpdate = (int)($stats->needs_update ?? 0);

        $this->line("Matches with stock_all: {$matches}");
        $this->line("Rows needing update:   {$needsUpdate}");

        // التحديث
        $affected = DB::update("
            UPDATE merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            JOIN stock_all s ON p.sku = s.part_number
            SET mp.price = ROUND(s.cost_price * ?, 2)
            WHERE mp.user_id = ?
              AND ROUND(s.cost_price * ?, 2) <> mp.price
        ", [$margin, $userId, $margin]);

        if ($affected > 0) {
            $this->info("Updated prices for {$affected} merchant items.");
        } else {
            $this->warn("No merchant items updated. Maybe data is already up-to-date.");
        }

        // بعد التحديث: تأكيد البواقي
        $remaining = DB::selectOne("
            SELECT
                SUM(CASE WHEN ROUND(s.cost_price * ?, 2) <> mp.price THEN 1 ELSE 0 END) AS remaining
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            JOIN stock_all s ON p.sku = s.part_number
            WHERE mp.user_id = ?
        ", [$margin, $userId]);

        $remainingDiff = (int)($remaining->remaining ?? 0);
        $this->line("Remaining mismatches:  {$remainingDiff}");

        return self::SUCCESS;
    }
}
