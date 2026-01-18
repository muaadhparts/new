<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add merchant_branch_id to merchant_items
 *
 * This migration adds branch support to merchant items, allowing:
 * - Same catalog item to exist in multiple branches of the same merchant
 * - Branch-scoped inventory and pricing
 * - Unique constraint: (catalog_item_id, user_id, merchant_branch_id)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Add merchant_branch_id column after user_id (if not exists)
            if (!Schema::hasColumn('merchant_items', 'merchant_branch_id')) {
                // Using bigInteger (not unsigned) to match merchant_branches.id column type
                $table->bigInteger('merchant_branch_id')->nullable()->after('user_id');
            }
        });

        // Add constraints in separate schema call to avoid issues
        Schema::table('merchant_items', function (Blueprint $table) {
            // Add foreign key constraint
            $table->foreign('merchant_branch_id', 'mi_merchant_branch_fk')
                ->references('id')
                ->on('merchant_branches')
                ->onDelete('set null');

            // Add index for performance
            $table->index('merchant_branch_id', 'mi_branch_id_index');

            // Add unique constraint: same item can exist in different branches
            // Note: We use a unique index that allows nulls for backward compatibility
            // Items without branch_id are legacy items that need to be assigned
            $table->unique(
                ['catalog_item_id', 'user_id', 'merchant_branch_id'],
                'mi_catalog_user_branch_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Drop unique constraint first
            $table->dropUnique('mi_catalog_user_branch_unique');

            // Drop index
            $table->dropIndex('mi_branch_id_index');

            // Drop foreign key
            $table->dropForeign('mi_merchant_branch_fk');

            // Drop column
            $table->dropColumn('merchant_branch_id');
        });
    }
};
