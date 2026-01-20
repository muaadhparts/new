<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Move brand_id from catalog_items to merchant_items
 *
 * Rationale:
 * - The same part (catalog_item) can be sold by different merchants for different vehicle brands
 * - Brand association belongs at the merchant_item level (seller-specific)
 * - This aligns with the multi-merchant architecture
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add brand_id column to merchant_items
        if (!Schema::hasColumn('merchant_items', 'brand_id')) {
            Schema::table('merchant_items', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('catalog_item_id');

                // Add index for filtering
                $table->index('brand_id', 'idx_mi_brand_id');
            });
        }

        // Step 2: Copy brand_id from catalog_items to merchant_items
        // Each merchant_item gets the brand_id from its linked catalog_item
        DB::statement("
            UPDATE merchant_items mi
            INNER JOIN catalog_items ci ON mi.catalog_item_id = ci.id
            SET mi.brand_id = ci.brand_id
            WHERE ci.brand_id IS NOT NULL
        ");

        // Step 3: Add foreign key constraint (optional - if brands table has proper structure)
        // Note: Only add FK if brands table exists and has the id column
        if (Schema::hasTable('brands')) {
            try {
                Schema::table('merchant_items', function (Blueprint $table) {
                    $table->foreign('brand_id', 'fk_mi_brand')
                          ->references('id')
                          ->on('brands')
                          ->onDelete('set null')
                          ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // FK may already exist or have issues - continue without it
                \Log::warning('Could not add brand_id foreign key to merchant_items: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Drop foreign key if exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('merchant_items');
            $fkNames = array_map(fn($fk) => $fk->getName(), $foreignKeys);

            if (in_array('fk_mi_brand', $fkNames)) {
                $table->dropForeign('fk_mi_brand');
            }

            // Drop index
            $indexes = $sm->listTableIndexes('merchant_items');
            if (isset($indexes['idx_mi_brand_id'])) {
                $table->dropIndex('idx_mi_brand_id');
            }

            // Drop column
            if (Schema::hasColumn('merchant_items', 'brand_id')) {
                $table->dropColumn('brand_id');
            }
        });
    }
};
