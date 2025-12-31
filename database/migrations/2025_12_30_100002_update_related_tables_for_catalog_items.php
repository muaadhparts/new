<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update related tables to use new naming convention:
     * - product_id -> catalog_item_id
     * - merchant_product_id -> merchant_item_id
     * - Rename product_fitments -> catalog_item_fitments
     * - Rename product_clicks -> catalog_item_clicks
     */
    public function up(): void
    {
        // =====================================================
        // 1. Update product_fitments -> catalog_item_fitments
        // =====================================================

        // Skip if already renamed
        if (Schema::hasTable('product_fitments') && !Schema::hasTable('catalog_item_fitments')) {
            // Drop ALL foreign key constraints first (including those using the unique key)
            Schema::table('product_fitments', function (Blueprint $table) {
                $table->dropForeign('fk_pf_product');
                $table->dropForeign('fk_pf_category');
                $table->dropForeign('fk_pf_sub');
                $table->dropForeign('fk_pf_child');
            });

            // Now we can drop the unique constraint
            DB::statement('ALTER TABLE `product_fitments` DROP INDEX `uq_prod_cat_sub_child`');

            // Rename column
            Schema::table('product_fitments', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });

            // Rename table
            Schema::rename('product_fitments', 'catalog_item_fitments');

            // Re-add unique constraint with new name
            DB::statement('ALTER TABLE `catalog_item_fitments` ADD UNIQUE KEY `uq_catalog_item_cat_sub_child` (`catalog_item_id`, `category_id`, `subcategory_id`, `childcategory_id`)');

            // Re-add all foreign keys with new names
            Schema::table('catalog_item_fitments', function (Blueprint $table) {
                $table->foreign('catalog_item_id', 'fk_cif_catalog_item')
                    ->references('id')->on('catalog_items')
                    ->onDelete('cascade');
                $table->foreign('category_id', 'fk_cif_category')
                    ->references('id')->on('categories')
                    ->onDelete('cascade');
                $table->foreign('subcategory_id', 'fk_cif_sub')
                    ->references('id')->on('subcategories')
                    ->onDelete('cascade');
                $table->foreign('childcategory_id', 'fk_cif_child')
                    ->references('id')->on('childcategories')
                    ->onDelete('cascade');
            });
        }

        // =====================================================
        // 2. Update product_clicks -> catalog_item_clicks
        // =====================================================

        // Skip if already renamed
        if (Schema::hasTable('product_clicks') && !Schema::hasTable('catalog_item_clicks')) {
            // Rename columns
            Schema::table('product_clicks', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });

            if (Schema::hasColumn('product_clicks', 'merchant_product_id')) {
                Schema::table('product_clicks', function (Blueprint $table) {
                    $table->renameColumn('merchant_product_id', 'merchant_item_id');
                });
            }

            // Rename table
            Schema::rename('product_clicks', 'catalog_item_clicks');
        }

        // =====================================================
        // 3. Update favorites table
        // =====================================================

        if (Schema::hasColumn('favorites', 'product_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });
        }

        if (Schema::hasColumn('favorites', 'merchant_product_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->renameColumn('merchant_product_id', 'merchant_item_id');
            });
        }

        // =====================================================
        // 4. Update catalog_reviews table
        // =====================================================

        if (Schema::hasColumn('catalog_reviews', 'product_id')) {
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });
        }

        if (Schema::hasColumn('catalog_reviews', 'merchant_product_id')) {
            // Drop index first if exists
            try {
                DB::statement('ALTER TABLE `catalog_reviews` DROP INDEX `catalog_reviews_merchant_product_id_index`');
            } catch (\Exception $e) {
                // Index may not exist
            }

            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->renameColumn('merchant_product_id', 'merchant_item_id');
            });

            // Re-add index with new name
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->index('merchant_item_id', 'catalog_reviews_merchant_item_id_index');
            });
        }

        // =====================================================
        // 5. Update stock_reservations table
        // =====================================================

        if (Schema::hasColumn('stock_reservations', 'merchant_product_id')) {
            // Drop index first if exists
            try {
                DB::statement('ALTER TABLE `stock_reservations` DROP INDEX `stock_reservations_expires_at_merchant_product_id_index`');
            } catch (\Exception $e) {
                // Index may not exist
            }

            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->renameColumn('merchant_product_id', 'merchant_item_id');
            });

            // Re-add index with new name
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->index(['expires_at', 'merchant_item_id'], 'stock_reservations_expires_at_merchant_item_id_index');
            });
        }

        // =====================================================
        // 6. Update reports table
        // =====================================================

        if (Schema::hasColumn('reports', 'product_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });
        }

        if (Schema::hasColumn('reports', 'merchant_product_id')) {
            // Drop index first if exists
            try {
                DB::statement('ALTER TABLE `reports` DROP INDEX `reports_merchant_product_id_index`');
            } catch (\Exception $e) {
                // Index may not exist
            }

            Schema::table('reports', function (Blueprint $table) {
                $table->renameColumn('merchant_product_id', 'merchant_item_id');
            });

            // Re-add index with new name
            Schema::table('reports', function (Blueprint $table) {
                $table->index('merchant_item_id', 'reports_merchant_item_id_index');
            });
        }

        // =====================================================
        // 7. Update comments table
        // =====================================================

        // Disable strict mode temporarily for this operation
        DB::statement('SET SESSION sql_mode = ""');

        if (Schema::hasColumn('comments', 'product_id')) {
            DB::statement('ALTER TABLE `comments` CHANGE `product_id` `catalog_item_id` INT UNSIGNED NULL');
        }

        if (Schema::hasColumn('comments', 'merchant_product_id')) {
            DB::statement('ALTER TABLE `comments` CHANGE `merchant_product_id` `merchant_item_id` INT UNSIGNED NULL');
        }

        // Restore strict mode
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"');

        // =====================================================
        // 8. Update galleries table
        // =====================================================

        if (Schema::hasColumn('galleries', 'product_id')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->renameColumn('product_id', 'catalog_item_id');
            });
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // 1. Revert catalog_item_fitments
        Schema::table('catalog_item_fitments', function (Blueprint $table) {
            $table->dropForeign('fk_cif_catalog_item');
        });

        DB::statement('ALTER TABLE `catalog_item_fitments` DROP INDEX `uq_catalog_item_cat_sub_child`');

        Schema::table('catalog_item_fitments', function (Blueprint $table) {
            $table->renameColumn('catalog_item_id', 'product_id');
        });

        Schema::rename('catalog_item_fitments', 'product_fitments');

        Schema::table('product_fitments', function (Blueprint $table) {
            $table->unique(['product_id', 'category_id', 'subcategory_id', 'childcategory_id'], 'uq_prod_cat_sub_child');
            $table->foreign('product_id', 'fk_pf_product')
                ->references('id')->on('products')
                ->onDelete('cascade');
        });

        // 2. Revert catalog_item_clicks
        Schema::rename('catalog_item_clicks', 'product_clicks');

        Schema::table('product_clicks', function (Blueprint $table) {
            $table->renameColumn('catalog_item_id', 'product_id');
        });

        if (Schema::hasColumn('product_clicks', 'merchant_item_id')) {
            Schema::table('product_clicks', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 3. Revert favorites
        if (Schema::hasColumn('favorites', 'catalog_item_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'product_id');
            });
        }

        if (Schema::hasColumn('favorites', 'merchant_item_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 4. Revert catalog_reviews
        if (Schema::hasColumn('catalog_reviews', 'catalog_item_id')) {
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'product_id');
            });
        }

        if (Schema::hasColumn('catalog_reviews', 'merchant_item_id')) {
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 5. Revert stock_reservations
        if (Schema::hasColumn('stock_reservations', 'merchant_item_id')) {
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 6. Revert reports
        if (Schema::hasColumn('reports', 'catalog_item_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'product_id');
            });
        }

        if (Schema::hasColumn('reports', 'merchant_item_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 7. Revert comments
        if (Schema::hasColumn('comments', 'catalog_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'product_id');
            });
        }

        if (Schema::hasColumn('comments', 'merchant_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_product_id');
            });
        }

        // 8. Revert galleries
        if (Schema::hasColumn('galleries', 'catalog_item_id')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'product_id');
            });
        }
    }
};
