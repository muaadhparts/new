<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportDatabaseSchema extends Command
{
    protected $signature = 'schema:export
                            {--table= : Export specific table only}
                            {--with-data : Include sample data (first 5 rows)}';

    protected $description = 'Export database table schemas to SQL files in database/schema';

    public function handle()
    {
        $schemaPath = database_path('schema');

        // Create directory if not exists
        if (!is_dir($schemaPath)) {
            mkdir($schemaPath, 0755, true);
        }

        $specificTable = $this->option('table');
        $withData = $this->option('with-data');

        // Get all tables
        $database = config('database.connections.mysql.database');
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        $tablesToExport = collect($tables)->pluck($tableKey);

        if ($specificTable) {
            $tablesToExport = $tablesToExport->filter(fn($t) => $t === $specificTable);
            if ($tablesToExport->isEmpty()) {
                $this->error("Table '{$specificTable}' not found.");
                return 1;
            }
        }

        // Filter out Laravel internal tables
        $excludedTables = ['migrations', 'failed_jobs', 'cache', 'cache_locks', 'jobs', 'job_batches'];
        $tablesToExport = $tablesToExport->reject(fn($t) => in_array($t, $excludedTables));

        $bar = $this->output->createProgressBar($tablesToExport->count());
        $bar->start();

        foreach ($tablesToExport as $table) {
            $this->exportTable($table, $schemaPath, $withData);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Exported {$tablesToExport->count()} tables to {$schemaPath}");

        return 0;
    }

    protected function exportTable(string $table, string $path, bool $withData = false): void
    {
        $createStatement = DB::select("SHOW CREATE TABLE `{$table}`");
        $createSql = $createStatement[0]->{'Create Table'} ?? '';

        $content = "-- Schema for table: {$table}\n";
        $content .= "-- Exported: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $content .= "DROP TABLE IF EXISTS `{$table}`;\n\n";
        $content .= $createSql . ";\n";

        if ($withData) {
            $rows = DB::table($table)->limit(5)->get();
            if ($rows->isNotEmpty()) {
                $content .= "\n-- Sample data (first 5 rows)\n";
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';

                foreach ($rows as $row) {
                    $values = array_map(function ($val) {
                        if (is_null($val)) return 'NULL';
                        if (is_numeric($val)) return $val;
                        return "'" . addslashes($val) . "'";
                    }, (array) $row);

                    $content .= "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                }
            }
        }

        file_put_contents("{$path}/{$table}.sql", $content);
    }
}
