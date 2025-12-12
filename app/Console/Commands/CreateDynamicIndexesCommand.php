<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDynamicIndexesCommand extends Command
{
    protected $signature = 'indexes:create-dynamic
                            {--catalog= : Specific catalog code (e.g., y61gl)}
                            {--dry-run : Show SQL without executing}
                            {--force : Skip confirmation}';

    protected $description = 'Create performance indexes on dynamic catalog tables (parts_*, section_parts_*, part_spec_groups_*, part_spec_group_items_*)';

    protected array $indexDefinitions = [
        'parts' => [
            'idx_p_callout' => ['callout'],
            'idx_p_part_number' => ['part_number'],
        ],
        'section_parts' => [
            'idx_sp_section_part' => ['section_id', 'part_id'],
            'idx_sp_part_id' => ['part_id'],
        ],
        'part_spec_groups' => [
            // Critical index for the slow query
            'idx_psg_part_section_catalog' => ['part_id', 'section_id', 'catalog_id'],
            'idx_psg_part_id' => ['part_id'],
            'idx_psg_section_id' => ['section_id'],
        ],
        'part_spec_group_items' => [
            'idx_psgi_group_id' => ['group_id'],
            'idx_psgi_spec_item' => ['specification_item_id'],
        ],
        'part_periods' => [
            'idx_pp_dates' => ['begin_date', 'end_date'],
        ],
    ];

    public function handle(): int
    {
        $specificCatalog = $this->option('catalog');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== Dynamic Table Index Creator ===');
        $this->newLine();

        // Get all catalog codes from the database
        $catalogCodes = $this->getCatalogCodes($specificCatalog);

        if (empty($catalogCodes)) {
            $this->error('No catalogs found.');
            return 1;
        }

        $this->info('Found ' . count($catalogCodes) . ' catalog(s) to process.');
        $this->newLine();

        if (!$dryRun && !$force) {
            if (!$this->confirm('This will add indexes to dynamic tables. Continue?')) {
                return 0;
            }
        }

        $totalIndexes = 0;
        $createdIndexes = 0;
        $skippedIndexes = 0;
        $errors = [];

        foreach ($catalogCodes as $code) {
            $this->info("Processing catalog: {$code}");

            foreach ($this->indexDefinitions as $tableBase => $indexes) {
                $tableName = strtolower("{$tableBase}_{$code}");

                if (!Schema::hasTable($tableName)) {
                    $this->line("  <comment>Table {$tableName} does not exist, skipping.</comment>");
                    continue;
                }

                foreach ($indexes as $indexName => $columns) {
                    $fullIndexName = "{$indexName}_{$code}";
                    $totalIndexes++;

                    if ($this->hasIndex($tableName, $fullIndexName)) {
                        $this->line("  <comment>Index {$fullIndexName} already exists on {$tableName}</comment>");
                        $skippedIndexes++;
                        continue;
                    }

                    // Also check for similar index names without the catalog suffix
                    if ($this->hasIndex($tableName, $indexName)) {
                        $this->line("  <comment>Index {$indexName} already exists on {$tableName}</comment>");
                        $skippedIndexes++;
                        continue;
                    }

                    $columnsStr = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
                    $sql = "CREATE INDEX `{$fullIndexName}` ON `{$tableName}` ({$columnsStr})";

                    if ($dryRun) {
                        $this->line("  <info>[DRY-RUN]</info> {$sql}");
                        $createdIndexes++;
                    } else {
                        try {
                            $this->line("  Creating index {$fullIndexName} on {$tableName}...");
                            DB::statement($sql);
                            $this->line("  <info>Created index {$fullIndexName}</info>");
                            $createdIndexes++;
                        } catch (\Exception $e) {
                            $this->line("  <error>Failed: {$e->getMessage()}</error>");
                            $errors[] = [
                                'table' => $tableName,
                                'index' => $fullIndexName,
                                'error' => $e->getMessage(),
                            ];
                        }
                    }
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('=== Summary ===');
        $this->line("Total indexes checked: {$totalIndexes}");
        $this->line("Indexes created: {$createdIndexes}");
        $this->line("Indexes skipped (already exist): {$skippedIndexes}");

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $err) {
                $this->line("  - {$err['table']}.{$err['index']}: {$err['error']}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. No changes were made. Run without --dry-run to execute.');
        }

        return empty($errors) ? 0 : 1;
    }

    protected function getCatalogCodes(?string $specific): array
    {
        if ($specific) {
            return [strtolower($specific)];
        }

        // Get all unique catalog codes from catalogs table
        return DB::table('catalogs')
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn($c) => strtolower($c))
            ->unique()
            ->values()
            ->toArray();
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
