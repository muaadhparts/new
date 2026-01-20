<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove brand_id from merchant_items
 *
 * Rationale:
 * - Vehicle compatibility (brand) is a CATALOG fact, not a merchant decision
 * - The same part can fit MULTIPLE vehicle brands (Toyota AND Lexus)
 * - Brand association now lives in catalog_item_fitments table (many-to-many)
 * - This properly separates catalog data from merchant data
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Drop foreign key if exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('merchant_items');
            $fkNames = array_map(fn($fk) => $fk->getName(), $foreignKeys);

            if (in_array('fk_mi_brand', $fkNames)) {
                $table->dropForeign('fk_mi_brand');
            }

            // Drop index if exists
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

    public function down(): void
    {
        // Add back brand_id column (for rollback)
        if (!Schema::hasColumn('merchant_items', 'brand_id')) {
            Schema::table('merchant_items', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('catalog_item_id');
                $table->index('brand_id', 'idx_mi_brand_id');
            });
        }
    }
};
