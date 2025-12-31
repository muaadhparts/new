<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class StockManagerCommand extends Command
{
    /**
     * Usage:
     *  php artisan stock:manage download --branch=ATWJRY
     *  php artisan stock:manage import [--local=/path/to/file]
     *  php artisan stock:manage aggregate
     *  php artisan stock:manage update-stock --user_id=59
     *  php artisan stock:manage update-price --user_id=59 --margin=1.3
     *  php artisan stock:manage full-refresh --user_id=59 --margin=1.3 --branch=ATWJRY [--remote=] [--local=]
     *  php artisan stock:manage check --user_id=59
     *
     * Options:
     *  --all       : apply to all vendors (overrides --user_id)
     *  --user_id   : target vendor (default 59)
     *  --margin    : price multiplier (1.3 = +30%)
     *  --branch    : branch code (used as remote if --remote not provided)
     *  --remote    : explicit remote (download source)
     *  --local     : explicit local path (download/import)
     */
    protected $signature = 'stock:manage
                            {action : download|import|aggregate|update-stock|update-price|full-refresh|check}
                            {--user_id=59 : Vendor user_id}
                            {--all : Apply to all vendors}
                            {--margin=1.3 : Price multiplier (e.g., 1.3 = +30%)}
                            {--branch=ATWJRY : Branch code}
                            {--remote= : Remote source for download}
                            {--local= : Local path for download/import}';

    protected $description = 'Unified stock manager: download, import, aggregate, update stock/price, full-refresh, and diagnostics.';

    public function handle(): int
    {
        $action = strtolower($this->argument('action'));
        $userId = (int) $this->option('user_id');
        $all    = (bool) $this->option('all');
        $margin = (float) $this->option('margin');
        $branch = (string) $this->option('branch');
        $remote = $this->option('remote') ?: $branch; // fallback
        $local  = $this->option('local') ?: null;

        // dd(['action'=>$action,'userId'=>$userId,'all'=>$all,'margin'=>$margin,'branch'=>$branch,'remote'=>$remote,'local'=>$local]); // فحص سريع — أبقه مقفولًا

        if ($margin <= 0) {
            $this->error('Invalid --margin value. It must be > 0.');
            return self::INVALID;
        }
        if (!$all && $userId <= 0 && in_array($action, ['update-stock','update-price','check','full-refresh'], true)) {
            $this->error('Invalid --user_id value. Use a positive integer or pass --all.');
            return self::INVALID;
        }

        try {
            switch ($action) {
                case 'download':
                    return $this->doDownload($remote, $local);
                case 'import':
                    return $this->doImport($local);
                case 'aggregate':
                    return $this->doAggregate();
                case 'update-stock':
                    return $this->doUpdateStock($all, $userId);
                case 'update-price':
                    return $this->doUpdatePrice($all, $userId, $margin);
                case 'full-refresh':
                    return $this->doFullRefresh($all, $userId, $margin, $remote, $local);
                case 'check':
                    return $this->doCheck($all, $userId, $margin);
                default:
                    $this->error("Unknown action: {$action}");
                    return self::INVALID;
            }
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    // --------------------------
    // Actions
    // --------------------------

    protected function doDownload(?string $remote, ?string $local): int
    {
        $this->line("🔽 Downloading stock files from remote: " . ($remote ?: '(default)') . " ...");

        // نحاول استدعاء أكشن لو موجود، وإلا نستعمل أمر Artisan قديم إن توفر
        $downloadClass = 'App\\Actions\\DownloadStock';
        $result = null;

        if (class_exists($downloadClass) && method_exists($downloadClass, 'run')) {
            $result = $downloadClass::run($remote, $local);
        } else {
            // fallback لأمر قديم إن وجد (تجاهل إذا غير موجود)
            try {
                Artisan::call('stock:download', array_filter([
                    '--branch' => $remote,
                    '--remote' => $remote,
                    '--local'  => $local,
                ]));
                // لو ما يرجع مسارات، نكتفي برسالة عامة
                $this->info("✔ Download complete.");
                return self::SUCCESS;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // طباعة ذكية للنتيجة (string | array | object)
        if (is_array($result)) {
            // حاول استنتاج مصفوفة المسارات
            $paths = $result;
            if (isset($result['paths']) && is_array($result['paths'])) $paths = $result['paths'];
            elseif (isset($result['files']) && is_array($result['files'])) $paths = $result['files'];

            $count = count($paths);
            $this->info("✔ Download complete. Files: {$count}");
            // اطبع فقط العناصر النصية (تجنب 1/true)
            foreach ($paths as $p) {
                if (is_string($p)) $this->line("• {$p}");
            }
            return self::SUCCESS;
        }

        if (is_object($result)) {
            if (isset($result->paths) && is_array($result->paths)) {
                $count = count($result->paths);
                $this->info("✔ Download complete. Files: {$count}");
                foreach ($result->paths as $p) if (is_string($p)) $this->line("• {$p}");
                return self::SUCCESS;
            }
            if (isset($result->files) && is_array($result->files)) {
                $count = count($result->files);
                $this->info("✔ Download complete. Files: {$count}");
                foreach ($result->files as $p) if (is_string($p)) $this->line("• {$p}");
                return self::SUCCESS;
            }
            if (isset($result->path) && is_string($result->path)) {
                $this->info("✔ Stock file downloaded to: {$result->path}");
                return self::SUCCESS;
            }
        }

        if (is_string($result)) {
            $this->info("✔ Stock file downloaded to: {$result}");
        } else {
            $this->info("✔ Download finished.");
        }

        return self::SUCCESS;
    }

    protected function doImport(?string $local): int
    {
        $this->line("📥 Importing stock data into `stocks`...");

        $importClass = 'App\\Actions\\ImportStock';
        $res = null;

        if (class_exists($importClass) && method_exists($importClass, 'run')) {
            $res = $importClass::run($local);
        } else {
            // fallback لأمر Artisan قديم إن وجد
            try {
                Artisan::call('stock:import', array_filter([
                    '--local' => $local,
                ]));
                $this->info("✔ Import finished (via stock:import).");
                return self::SUCCESS;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // حاول استخراج أرقام مفيدة
        if (is_int($res)) {
            $this->info("✔ Imported/Upserted rows: {$res}");
        } elseif (is_array($res)) {
            $count = $res['count'] ?? $res['imported'] ?? $res['rows'] ?? null;
            if (is_numeric($count)) {
                $this->info("✔ Imported/Upserted rows: {$count}");
            } else {
                $this->info("✔ Import finished.");
            }
        } else {
            $this->info("✔ Import finished.");
        }

        return self::SUCCESS;
    }

    protected function doAggregate(): int
    {
        $this->line("📊 Aggregating into `stock_all`...");
        $this->line("Aggregating stock data...");

        // تجميع البيانات من جدول stocks مع brand_quality_id ثابت = 1
        $sql = "
            INSERT INTO stock_all (part_number, sku, brand_quality_id, qty, cost_price, updated_at)
            SELECT 
                s.part_number,
                s.part_number AS sku,
                1 AS brand_quality_id,  -- ✅ القيمة الثابتة الآن = 1 بدل NULL أو 0
                SUM(COALESCE(s.qty, 0)) AS qty,
                AVG(COALESCE(s.cost_price, 0)) AS cost_price,
                NOW() AS updated_at
            FROM stocks s
            GROUP BY s.part_number
            ON DUPLICATE KEY UPDATE
                qty = VALUES(qty),
                cost_price = VALUES(cost_price),
                updated_at = VALUES(updated_at)
        ";
        DB::statement($sql);

        // تحديث sku إذا كان فارغًا فقط
        $fixSkuSql = "
            UPDATE stock_all
            SET sku = part_number
            WHERE (sku IS NULL OR sku = '')
        ";
        DB::statement($fixSkuSql);

        $this->info("✔ Stock aggregation completed successfully (brand_quality_id=1) and SKU filled where missing.");
        return self::SUCCESS;
    }

    protected function doUpdateStock(bool $all, int $userId): int
    {
        return $all ? $this->updateStockForAll() : $this->updateStockForOne($userId);
    }

    protected function doUpdatePrice(bool $all, int $userId, float $margin): int
    {
        return $all ? $this->updatePriceForAll($margin) : $this->updatePriceForOne($userId, $margin);
    }

    protected function doFullRefresh(bool $all, int $userId, float $margin, ?string $remote, ?string $local): int
    {
        // إنشاء سجل في merchant_stock_updates
        $stockUpdate = null;
        if (!$all && $userId > 0) {
            $stockUpdate = DB::table('merchant_stock_updates')->insertGetId([
                'user_id' => $userId,
                'update_type' => 'automatic',
                'status' => 'processing',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            // 1) Download
            $this->doDownload($remote, $local);

            // 2) Import
            $this->doImport($local);

            // 3) Aggregate
            $this->doAggregate();

            // 4) Updates
            $this->line("🛠 Updating products from stock_all...");
            if ($all) {
                $this->line("Updating ALL vendors stock from stock_all...");
                $this->updateStockForAll();

                $this->line("Updating ALL vendors price from stock_all.cost_price * {$margin}...");
                $this->updatePriceForAll($margin);
            } else {
                $this->line("Updating merchant_items stock for user_id={$userId}...");
                $this->updateStockForOne($userId);

                $this->line("Updating merchant_items price for user_id={$userId} with margin={$margin}...");
                $this->updatePriceForOne($userId, $margin);
            }

            $this->info("🎉 Full refresh + product update completed successfully.");

            // تحديث السجل كـ مكتمل
            if ($stockUpdate) {
                DB::table('merchant_stock_updates')
                    ->where('id', $stockUpdate)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            // تحديث السجل كـ فاشل
            if ($stockUpdate) {
                DB::table('merchant_stock_updates')
                    ->where('id', $stockUpdate)
                    ->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_log' => $e->getMessage(),
                        'updated_at' => now(),
                    ]);
            }

            throw $e;
        }
    }

    protected function doCheck(bool $all, int $userId, float $margin): int
    {
        if ($all) {
            $merchantIds = DB::table('merchant_items')->distinct()->pluck('user_id')->filter()->values();
            if ($merchantIds->isEmpty()) {
                $this->warn('No vendors found in merchant_items.');
                return self::SUCCESS;
            }
            foreach ($merchantIds as $uid) {
                $this->line("== Vendor {$uid} ==");
                $this->printStockDiff($uid);
                $this->printPriceDiff($uid, $margin);
            }
            return self::SUCCESS;
        }

        $this->printStockDiff($userId);
        $this->printPriceDiff($userId, $margin);
        return self::SUCCESS;
    }

    // --------------------------
    // Helpers
    // --------------------------

    protected function updateStockForOne(int $userId): int
    {
        // 0) إدراج الصفوف الناقصة أولًا
        $insertMissingSql = "
            INSERT INTO merchant_items (
                catalog_item_id, user_id, brand_quality_id, stock, created_at, updated_at
            )
            SELECT
                p.id AS catalog_item_id,
                ?   AS user_id,
                s.brand_quality_id,
                COALESCE(s.qty, 0) AS stock,
                NOW(), NOW()
            FROM catalog_items p
            JOIN stock_all s  ON s.sku = p.sku
            LEFT JOIN merchant_items mp
                ON mp.catalog_item_id = p.id
                AND mp.user_id = ?
                AND mp.brand_quality_id = s.brand_quality_id
            WHERE mp.id IS NULL
        ";
        DB::insert($insertMissingSql, [$userId, $userId]);

        // 1) إحصائيات قبل التحديث — استخدم t.stock بدل mp.stock
        $stats = DB::selectOne("
            SELECT
                SUM(CASE WHEN COALESCE(s_qty, -1) <> COALESCE(t.stock, -1) THEN 1 ELSE 0 END) AS needs_update,
                COUNT(*) AS matches,
                SUM(CASE WHEN s_qty IS NULL THEN 1 ELSE 0 END) AS missing_in_stock_all
            FROM (
                SELECT
                    mp.id,
                    mp.stock AS stock,
                    COALESCE(se.qty, sf.qty) AS s_qty
                FROM merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id   -- exact
                LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1                      -- fallback
                WHERE mp.user_id = ?
            ) t
        ", [$userId]);

        $this->line("Matches with stock_all:   {$stats->matches}");
        $this->line("Rows needing update:      {$stats->needs_update}");
        $this->line("Missing in stock_all:     {$stats->missing_in_stock_all}");

        // 2) التحديث الفعلي للكميات (COALESCE بين exact و fallback)
        $affected = DB::update("
            UPDATE merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            SET mp.stock = COALESCE(se.qty, sf.qty, 0)
            WHERE mp.user_id = ?
            AND COALESCE(se.qty, sf.qty, 0) <> COALESCE(mp.stock, 0)
        ", [$userId]);

        if ($affected > 0) {
            $this->info("✔ Updated stock for {$affected} merchant items.");
        } else {
            $this->warn("ℹ No merchant items updated. Maybe stock is already up-to-date.");
        }

        // 3) المتبقّي (بعد التحديث)
        $remaining = DB::selectOne("
            SELECT
                SUM(CASE WHEN COALESCE(se.qty, sf.qty, 0) <> COALESCE(mp.stock, 0) THEN 1 ELSE 0 END) AS remaining
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            WHERE mp.user_id = ?
        ", [$userId]);

        $this->line("Remaining mismatches:     " . (int)($remaining->remaining ?? 0));
        return self::SUCCESS;
    }


    protected function updateStockForAll(): int
    {
        $merchantIds = DB::table('merchant_items')->distinct()->pluck('user_id')->filter()->values();
        if ($merchantIds->isEmpty()) {
            $this->warn('No vendors found in merchant_items.');
            return self::SUCCESS;
        }

        $totalMatches = 0;
        $totalNeeds   = 0;
        $totalMissing = 0;
        $totalUpdated = 0;

        foreach ($merchantIds as $uid) {
            // 0) إدراج الصفوف الناقصة لهذا التاجر
            $insertMissingSql = "
                INSERT INTO merchant_items (
                    catalog_item_id, user_id, brand_quality_id, stock, created_at, updated_at
                )
                SELECT
                    p.id AS catalog_item_id,
                    ?   AS user_id,
                    s.brand_quality_id,
                    COALESCE(s.qty, 0) AS stock,
                    NOW() AS created_at,
                    NOW() AS updated_at
                FROM catalog_items p
                JOIN stock_all s
                    ON s.sku = p.sku
                LEFT JOIN merchant_items mp
                    ON mp.catalog_item_id = p.id
                    AND mp.user_id = ?
                    AND mp.brand_quality_id = s.brand_quality_id
                WHERE mp.id IS NULL
            ";
            DB::insert($insertMissingSql, [$uid, $uid]);

            // إحصائيات
            $stats = DB::selectOne("
                SELECT
                    SUM(CASE WHEN s.sku IS NOT NULL THEN 1 ELSE 0 END) AS matches,
                    SUM(CASE WHEN s.sku IS NOT NULL AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0) THEN 1 ELSE 0 END) AS needs_update,
                    SUM(CASE WHEN s.sku IS NULL THEN 1 ELSE 0 END) AS missing_in_stock_all
                FROM merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                LEFT JOIN stock_all s
                    ON s.sku = p.sku
                AND (s.brand_quality_id = mp.brand_quality_id OR s.brand_quality_id = 1)
                WHERE mp.user_id = ?
            ", [$uid]);

            $matches = (int)($stats->matches ?? 0);
            $needs   = (int)($stats->needs_update ?? 0);
            $missing = (int)($stats->missing_in_stock_all ?? 0);

            $this->info("User {$uid}: matches={$matches}, needs_update={$needs}, missing={$missing}");

            // تحديث الكميات
            $affected = DB::update("
                UPDATE merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                LEFT JOIN stock_all s
                    ON s.sku = p.sku
                AND (s.brand_quality_id = mp.brand_quality_id OR s.brand_quality_id = 1)
                SET mp.stock = COALESCE(s.qty, 0)
                WHERE mp.user_id = ?
                AND s.sku IS NOT NULL
                AND COALESCE(s.qty,0) <> COALESCE(mp.stock,0)
            ", [$uid]);

            $this->line("User {$uid}: updated={$affected}");

            $totalMatches += $matches;
            $totalNeeds   += $needs;
            $totalMissing += $missing;
            $totalUpdated += (int)$affected;
        }

        $this->info("TOTAL: matches={$totalMatches}, needs_update={$totalNeeds}, missing={$totalMissing}, updated={$totalUpdated}");
        return self::SUCCESS;
    }


    protected function updatePriceForOne(int $userId, float $margin): int
    {
        // إحصائيات: عدّ الصفوف التي سعرها NULL أو مختلف
        $stats = DB::selectOne("
            SELECT
                COUNT(*) AS matches,
                SUM(
                    CASE
                        WHEN mp.price IS NULL THEN 1
                        WHEN ROUND(COALESCE(se.cost_price, sf.cost_price, 0) * ?, 2) <> mp.price THEN 1
                        ELSE 0
                    END
                ) AS needs_update
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            WHERE mp.user_id = ?
        ", [$margin, $userId]);

        $this->line("Matches with stock_all: {$stats->matches}");
        $this->line("Rows needing update:   {$stats->needs_update}");

        // التحديث: حدّث كل ما هو NULL أو مختلف
        $affected = DB::update("
            UPDATE merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            SET mp.price = ROUND(COALESCE(se.cost_price, sf.cost_price, 0) * ?, 2)
            WHERE mp.user_id = ?
            AND (
                    mp.price IS NULL
                OR ROUND(COALESCE(se.cost_price, sf.cost_price, 0) * ?, 2) <> mp.price
            )
        ", [$margin, $userId, $margin]);

        if ($affected > 0) {
            $this->info("✔ Updated prices for {$affected} merchant items.");
        } else {
            $this->warn("ℹ No merchant items updated. Maybe data is already up-to-date.");
        }

        return self::SUCCESS;
    }


    protected function updatePriceForAll(float $margin): int
    {
        $merchantIds = DB::table('merchant_items')->distinct()->pluck('user_id')->filter()->values();
        if ($merchantIds->isEmpty()) {
            $this->warn('No vendors found in merchant_items.');
            return self::SUCCESS;
        }

        $totalMatches = 0;
        $totalNeeds   = 0;
        $totalUpdated = 0;

        foreach ($merchantIds as $uid) {
            $stats = DB::selectOne("
                SELECT
                    COUNT(*) AS matches,
                    SUM(CASE WHEN ROUND(s.cost_price * ?, 2) <> mp.price THEN 1 ELSE 0 END) AS needs_update
                FROM merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                JOIN stock_all s
                    ON s.sku = p.sku
                AND s.brand_quality_id = mp.brand_quality_id
                WHERE mp.user_id = ?
            ", [$margin, $uid]);

            $matches = (int)($stats->matches ?? 0);
            $needs   = (int)($stats->needs_update ?? 0);

            $this->info("User {$uid}: matches={$matches}, needs_update={$needs}");

            $affected = DB::update("
                UPDATE merchant_items mp
                JOIN catalog_items p ON mp.catalog_item_id = p.id
                JOIN stock_all s
                    ON s.sku = p.sku
                AND s.brand_quality_id = mp.brand_quality_id
                SET mp.price = ROUND(s.cost_price * ?, 2)
                WHERE mp.user_id = ?
                AND ROUND(s.cost_price * ?, 2) <> mp.price
            ", [$margin, $uid, $margin]);

            $this->line("User {$uid}: updated={$affected}");

            $totalMatches += $matches;
            $totalNeeds   += $needs;
            $totalUpdated += (int)$affected;
        }

        $this->info("TOTAL: matches={$totalMatches}, needs_update={$totalNeeds}, updated={$totalUpdated}");
        return self::SUCCESS;
    }


    protected function printStockDiff(int $userId): void
    {
        $diff = DB::selectOne("
            SELECT
                COUNT(*) AS matches,
                SUM(CASE WHEN COALESCE(se.qty, sf.qty) IS NULL THEN 1 ELSE 0 END) AS missing_in_stock_all,
                SUM(CASE WHEN COALESCE(se.qty, sf.qty, 0) <> COALESCE(mp.stock, 0) THEN 1 ELSE 0 END) AS needs_update
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            WHERE mp.user_id = ?
        ", [$userId]);

        $this->line("Stock → matches={$diff->matches}, needs_update={$diff->needs_update}, missing={$diff->missing_in_stock_all}");
    }


    protected function printPriceDiff(int $userId, float $margin): void
    {
        $diff = DB::selectOne("
            SELECT
                COUNT(*) AS matches,
                SUM(
                    CASE
                        WHEN mp.price IS NULL THEN 1
                        WHEN ROUND(COALESCE(se.cost_price, sf.cost_price, 0) * ?, 2) <> mp.price THEN 1
                        ELSE 0
                    END
                ) AS needs_update
            FROM merchant_items mp
            JOIN catalog_items p ON mp.catalog_item_id = p.id
            LEFT JOIN stock_all se ON se.sku = p.sku AND se.brand_quality_id = mp.brand_quality_id
            LEFT JOIN stock_all sf ON sf.sku = p.sku AND sf.brand_quality_id = 1
            WHERE mp.user_id = ?
        ", [$margin, $userId]);

        $this->line("Price → matches={$diff->matches}, needs_update={$diff->needs_update} (margin={$margin})");
    }

}
