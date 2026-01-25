<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master migration that creates all database tables.
 *
 * Generated from: database/schema-descriptor/schema-descriptor.txt
 * Date: 2026-01-25
 *
 * This migration uses CREATE TABLE IF NOT EXISTS to safely create tables
 * without risking data loss if tables already exist.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schemaFile = database_path('schema-descriptor/schema-descriptor.txt');

        if (!file_exists($schemaFile)) {
            throw new \RuntimeException("Schema file not found: {$schemaFile}");
        }

        $content = file_get_contents($schemaFile);
        $tables = $this->parseSchemaDescriptor($content);

        foreach ($tables as $tableName => $tableDefinition) {
            if (Schema::hasTable($tableName)) {
                echo "  Skipping existing table: {$tableName}\n";
                continue;
            }

            $sql = $this->buildCreateTableSQL($tableName, $tableDefinition);

            try {
                DB::statement($sql);
                echo "  Created table: {$tableName}\n";
            } catch (\Exception $e) {
                echo "  ERROR creating table '{$tableName}': " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This will NOT drop tables to prevent data loss.
     */
    public function down(): void
    {
        // DO NOT DROP TABLES - This is a safety measure
        // Tables should be managed manually if needed
        echo "  WARNING: DOWN migration intentionally does nothing to prevent data loss.\n";
    }

    /**
     * Parse the schema descriptor file into table definitions.
     */
    protected function parseSchemaDescriptor(string $content): array
    {
        $tables = [];
        $currentTable = null;
        $currentDefinition = [];

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if this is a new table definition
            if (preg_match('/^TABLE\s+(\w+)$/i', $line, $matches)) {
                // Save previous table if exists
                if ($currentTable !== null) {
                    $tables[$currentTable] = $currentDefinition;
                }

                $currentTable = $matches[1];
                $currentDefinition = [
                    'columns' => [],
                    'keys' => [],
                    'constraints' => [],
                ];
                continue;
            }

            if ($currentTable === null) {
                continue;
            }

            // Parse line content
            if (preg_match('/^`(\w+)`\s+(.+)$/i', $line, $matches)) {
                // Column definition
                $currentDefinition['columns'][] = $line;
            } elseif (preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY|FULLTEXT KEY)/i', $line)) {
                // Index definition
                $currentDefinition['keys'][] = $line;
            } elseif (preg_match('/^CONSTRAINT/i', $line)) {
                // Foreign key constraint
                $currentDefinition['constraints'][] = $line;
            }
        }

        // Save last table
        if ($currentTable !== null) {
            $tables[$currentTable] = $currentDefinition;
        }

        return $tables;
    }

    /**
     * Build CREATE TABLE SQL from parsed definition.
     */
    protected function buildCreateTableSQL(string $tableName, array $definition): string
    {
        $parts = [];

        // Add columns (with fixes for invalid defaults)
        foreach ($definition['columns'] as $column) {
            $column = $this->fixColumnDefinition($column);
            $parts[] = '  ' . $column;
        }

        // Add keys
        foreach ($definition['keys'] as $key) {
            $parts[] = '  ' . $key;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";
        $sql .= implode(",\n", $parts);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $sql;
    }

    /**
     * Fix column definitions with invalid default values.
     */
    protected function fixColumnDefinition(string $column): string
    {
        // Fix invalid timestamp defaults ('0000-00-00 00:00:00')
        $column = preg_replace(
            "/DEFAULT\s+'0000-00-00 00:00:00'/i",
            "DEFAULT CURRENT_TIMESTAMP",
            $column
        );

        // Fix NOT NULL without default for timestamp columns
        $column = preg_replace(
            "/(`\w+`\s+timestamp)\s+NOT NULL(?!\s+DEFAULT)/i",
            "$1 NULL DEFAULT NULL",
            $column
        );

        return $column;
    }
};
