<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupDynamicIndexesCommand extends Command
{
    protected $signature = 'indexes:cleanup-dynamic
                            {--catalog= : Specific catalog code}
                            {--dry-run : Show indexes without deleting}
                            {--force : Skip confirmation}
                            {--show-all : Show all indexes (not just old ones)}';

    protected $description = 'Discover and remove old/duplicate indexes from dynamic catalog tables';

    /**
     * الفهارس المطلوبة (الجديدة) - أي فهرس آخر يعتبر قديم
     */
    protected array $requiredIndexes = [
        'parts' => [
            'idx_p_callout',
            'idx_p_part_number',
            'idx_p_label_en',
            'idx_p_label_ar',
        ],
        'section_parts' => [
            'idx_sp_section_part',
            'idx_sp_part_id',
        ],
        'part_spec_groups' => [
            'idx_psg_part_section_catalog',
            'idx_psg_section_catalog',
            'idx_psg_period',
        ],
        'part_spec_group_items' => [
            'idx_psgi_group_id',
            'idx_psgi_spec_item',
        ],
        'part_periods' => [
            'idx_pp_dates',
        ],
        'part_extensions' => [
            'idx_pe_composite',
            'idx_pe_part_section',
        ],
    ];

    /**
     * الفهارس التي يجب عدم حذفها (PRIMARY, UNIQUE, FK)
     */
    protected array $protectedPatterns = [
        'PRIMARY',
        'UNIQUE',
        'fk_',
        'FK_',
        'foreign_',
    ];

    public function handle(): int
    {
        $specificCatalog = $this->option('catalog');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $showAll = $this->option('show-all');

        $this->info('=== Dynamic Table Index Cleanup ===');
        $this->newLine();

        $catalogCodes = $this->getCatalogCodes($specificCatalog);

        if (empty($catalogCodes)) {
            $this->error('No catalogs found.');
            return 1;
        }

        $this->info('Found ' . count($catalogCodes) . ' catalog(s) to analyze.');
        $this->newLine();

        $allOldIndexes = [];
        $allCurrentIndexes = [];

        foreach ($catalogCodes as $code) {
            foreach (array_keys($this->requiredIndexes) as $tableBase) {
                $tableName = strtolower("{$tableBase}_{$code}");

                if (!Schema::hasTable($tableName)) {
                    continue;
                }

                $indexes = $this->getTableIndexes($tableName);
                $requiredForTable = $this->getRequiredIndexNames($tableBase, $code);

                foreach ($indexes as $index) {
                    $indexName = $index->Key_name;

                    // تخطي الفهارس المحمية
                    if ($this->isProtectedIndex($indexName)) {
                        continue;
                    }

                    $indexInfo = [
                        'table' => $tableName,
                        'catalog' => $code,
                        'index_name' => $indexName,
                        'column' => $index->Column_name,
                        'non_unique' => $index->Non_unique,
                    ];

                    if (in_array($indexName, $requiredForTable)) {
                        $allCurrentIndexes[] = $indexInfo;
                    } else {
                        $allOldIndexes[] = $indexInfo;
                    }
                }
            }
        }

        // عرض الفهارس الحالية (إذا طُلب)
        if ($showAll) {
            $this->info('=== Current Valid Indexes ===');
            $this->displayIndexes($allCurrentIndexes, 'info');
            $this->newLine();
        }

        // عرض الفهارس القديمة
        $this->warn('=== Old/Unknown Indexes (candidates for removal) ===');

        if (empty($allOldIndexes)) {
            $this->info('No old indexes found. All indexes are up to date!');
            return 0;
        }

        $this->displayIndexes($allOldIndexes, 'comment');
        $this->newLine();

        // ملخص
        $uniqueOldIndexes = collect($allOldIndexes)->unique('index_name')->count();
        $this->info("Found {$uniqueOldIndexes} unique old index name(s) across " . count($allOldIndexes) . " table(s).");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY-RUN mode: No changes made.');
            $this->line('Run without --dry-run to delete these indexes.');
            return 0;
        }

        // تأكيد الحذف
        if (!$force) {
            if (!$this->confirm('Do you want to DELETE these old indexes?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // حذف الفهارس القديمة
        $deleted = 0;
        $errors = [];
        $processedIndexes = [];

        foreach ($allOldIndexes as $index) {
            $key = $index['table'] . '.' . $index['index_name'];

            // تجنب محاولة حذف نفس الفهرس مرتين
            if (isset($processedIndexes[$key])) {
                continue;
            }
            $processedIndexes[$key] = true;

            try {
                $sql = "DROP INDEX `{$index['index_name']}` ON `{$index['table']}`";
                DB::statement($sql);
                $this->line("<info>Deleted:</info> {$index['index_name']} from {$index['table']}");
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index['index_name'],
                    'table' => $index['table'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->line("Deleted: {$deleted} index(es)");

        if (!empty($errors)) {
            $this->error("Errors: " . count($errors));
            foreach ($errors as $err) {
                $this->line("  <error>{$err['table']}.{$err['index']}:</error> {$err['error']}");
            }
        }

        return empty($errors) ? 0 : 1;
    }

    protected function getCatalogCodes(?string $specific): array
    {
        if ($specific) {
            return [strtolower($specific)];
        }

        return DB::table('catalogs')
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn($c) => strtolower($c))
            ->unique()
            ->values()
            ->toArray();
    }

    protected function getTableIndexes(string $table): array
    {
        try {
            return DB::select("SHOW INDEX FROM `{$table}`");
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getRequiredIndexNames(string $tableBase, string $code): array
    {
        $indexes = $this->requiredIndexes[$tableBase] ?? [];
        return array_map(fn($idx) => "{$idx}_{$code}", $indexes);
    }

    protected function isProtectedIndex(string $indexName): bool
    {
        foreach ($this->protectedPatterns as $pattern) {
            if (stripos($indexName, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function displayIndexes(array $indexes, string $style = 'line'): void
    {
        $grouped = collect($indexes)->groupBy('table');

        foreach ($grouped as $table => $tableIndexes) {
            $this->line("  <comment>{$table}:</comment>");

            $uniqueIndexes = $tableIndexes->unique('index_name');
            foreach ($uniqueIndexes as $index) {
                $columns = $tableIndexes
                    ->where('index_name', $index['index_name'])
                    ->pluck('column')
                    ->implode(', ');

                $this->line("    - {$index['index_name']} ({$columns})");
            }
        }
    }
}
