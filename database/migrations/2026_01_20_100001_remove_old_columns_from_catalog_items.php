<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove deprecated columns from catalog_items:
 * - brand_id: Now in merchant_items (moved in previous migration)
 * - is_catalog: Old flag, no longer used
 * - catalog_id: Old tree system, replaced by newcategories system
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();

            // Drop foreign keys that reference these columns
            $foreignKeys = $sm->listTableForeignKeys('catalog_items');
            $fkNames = array_map(fn($fk) => $fk->getName(), $foreignKeys);

            // Drop brand_id FK if exists
            foreach ($foreignKeys as $fk) {
                $cols = $fk->getLocalColumns();
                if (in_array('brand_id', $cols)) {
                    $table->dropForeign($fk->getName());
                }
            }

            // Drop catalog_id FK if exists
            foreach ($foreignKeys as $fk) {
                $cols = $fk->getLocalColumns();
                if (in_array('catalog_id', $cols)) {
                    $table->dropForeign($fk->getName());
                }
            }
        });

        // Drop indexes related to these columns
        Schema::table('catalog_items', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('catalog_items');

            foreach ($indexes as $indexName => $index) {
                $cols = $index->getColumns();
                if (in_array('brand_id', $cols) || in_array('is_catalog', $cols) || in_array('catalog_id', $cols)) {
                    if ($indexName !== 'primary') {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index may not exist or have different name
                        }
                    }
                }
            }
        });

        // Drop the columns
        Schema::table('catalog_items', function (Blueprint $table) {
            // Drop brand_id - now in merchant_items
            if (Schema::hasColumn('catalog_items', 'brand_id')) {
                $table->dropColumn('brand_id');
            }

            // Drop is_catalog - old flag, no longer used
            if (Schema::hasColumn('catalog_items', 'is_catalog')) {
                $table->dropColumn('is_catalog');
            }

            // Drop catalog_id - old tree system, replaced by newcategories
            if (Schema::hasColumn('catalog_items', 'catalog_id')) {
                $table->dropColumn('catalog_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // Restore columns (data will be lost)
            if (!Schema::hasColumn('catalog_items', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('catalog_items', 'is_catalog')) {
                $table->tinyInteger('is_catalog')->default(0)->after('sale');
            }

            if (!Schema::hasColumn('catalog_items', 'catalog_id')) {
                $table->unsignedBigInteger('catalog_id')->nullable()->after('is_catalog');
            }
        });

        // Note: Data cannot be restored - this is a one-way migration
        // The brand_id data is now in merchant_items table
    }
};
