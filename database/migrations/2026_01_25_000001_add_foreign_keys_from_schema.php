<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add foreign key constraints after all tables are created.
 *
 * This migration runs AFTER the main table creation migration
 * to ensure all referenced tables exist.
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
        $constraints = $this->parseConstraints($content);

        foreach ($constraints as $constraint) {
            try {
                // Check if constraint already exists
                $existingConstraints = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND CONSTRAINT_NAME = ?
                ", [$constraint['table'], $constraint['name']]);

                if (!empty($existingConstraints)) {
                    continue; // Constraint already exists
                }

                $sql = "ALTER TABLE `{$constraint['table']}` ADD {$constraint['definition']}";
                DB::statement($sql);

            } catch (\Exception $e) {
                // Log error but continue with other constraints
                echo "  WARNING: Failed to add constraint on '{$constraint['table']}': " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DO NOT DROP CONSTRAINTS - Safety measure
        echo "  WARNING: DOWN migration intentionally does nothing to prevent data loss.\n";
    }

    /**
     * Parse foreign key constraints from schema descriptor.
     */
    protected function parseConstraints(string $content): array
    {
        $constraints = [];
        $currentTable = null;

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if this is a new table definition
            if (preg_match('/^TABLE\s+(\w+)$/i', $line, $matches)) {
                $currentTable = $matches[1];
                continue;
            }

            if ($currentTable === null) {
                continue;
            }

            // Parse CONSTRAINT lines
            if (preg_match('/^CONSTRAINT\s+`?(\w+)`?\s+(.+)$/i', $line, $matches)) {
                $constraints[] = [
                    'table' => $currentTable,
                    'name' => $matches[1],
                    'definition' => $line,
                ];
            }
        }

        return $constraints;
    }
};
