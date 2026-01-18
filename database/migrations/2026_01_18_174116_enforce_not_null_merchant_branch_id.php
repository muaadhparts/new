<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 9.4: Hard DB Constraints Migration
 *
 * This migration enforces NOT NULL constraint on merchant_branch_id.
 * IMPORTANT: Phase 9.3 must run first to assign branches to all items.
 *
 * Constraints added:
 * 1. NOT NULL on merchant_branch_id (no more NULL values allowed)
 * 2. Foreign key with RESTRICT (not SET NULL) - cannot delete branch with items
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safety check: verify no NULL values exist
        $nullCount = DB::table('merchant_items')
            ->whereNull('merchant_branch_id')
            ->count();

        if ($nullCount > 0) {
            throw new \Exception(
                "Cannot add NOT NULL constraint: {$nullCount} items still have NULL merchant_branch_id. " .
                "Run Phase 9.3 migration first."
            );
        }

        // Find and drop all foreign keys on merchant_branch_id column
        $dbName = config('database.connections.mysql.database');
        $existingFks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = ?
              AND TABLE_NAME = 'merchant_items'
              AND COLUMN_NAME = 'merchant_branch_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName]);

        foreach ($existingFks as $fk) {
            DB::statement("ALTER TABLE merchant_items DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Change column to BIGINT (signed, to match merchant_branches.id) and NOT NULL
        // Note: merchant_branches.id is bigint (signed), so we need to match it
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->bigInteger('merchant_branch_id')->nullable(false)->change();
        });

        // Add proper foreign key with RESTRICT
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->foreign('merchant_branch_id')
                ->references('id')
                ->on('merchant_branches')
                ->onDelete('restrict')  // Cannot delete branch with items
                ->onUpdate('cascade');  // Update if branch ID changes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->dropForeign(['merchant_branch_id']);
        });

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->unsignedBigInteger('merchant_branch_id')->nullable()->change();
        });
    }
};
