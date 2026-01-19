<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename quality_brand_id to quality_brand_id in merchant_items
     */
    public function up(): void
    {
        // The unique constraint is used by fk_mi_catalog_item foreign key
        // We need to drop the FK first, then the unique, rename, and recreate

        // Step 1: Drop foreign key that uses the unique constraint
        DB::statement('ALTER TABLE `merchant_items` DROP FOREIGN KEY `fk_mi_catalog_item`');

        // Step 2: Drop the unique constraint that includes quality_brand_id
        DB::statement('ALTER TABLE `merchant_items` DROP INDEX `uniq_catalog_item_user`');

        // Step 3: Rename the column
        DB::statement('ALTER TABLE `merchant_items` CHANGE `quality_brand_id` `quality_brand_id` BIGINT UNSIGNED NULL');

        // Step 4: Recreate the unique constraint with new column name
        DB::statement('ALTER TABLE `merchant_items` ADD UNIQUE `uniq_catalog_item_user` (`catalog_item_id`, `user_id`, `merchant_branch_id`, `quality_brand_id`, `item_condition`)');

        // Step 5: Recreate the foreign key on catalog_item_id
        DB::statement('ALTER TABLE `merchant_items` ADD CONSTRAINT `fk_mi_catalog_item` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');

        // Step 6: Add index on quality_brand_id for better query performance
        DB::statement('ALTER TABLE `merchant_items` ADD INDEX `idx_mi_quality_brand` (`quality_brand_id`)');

        // Step 7: Add foreign key constraint to quality_brands table
        DB::statement('ALTER TABLE `merchant_items` ADD CONSTRAINT `fk_mi_quality_brand` FOREIGN KEY (`quality_brand_id`) REFERENCES `quality_brands` (`id`) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop the foreign key constraint on quality_brand_id
        DB::statement('ALTER TABLE `merchant_items` DROP FOREIGN KEY `fk_mi_quality_brand`');

        // Step 2: Drop the index on quality_brand_id
        DB::statement('ALTER TABLE `merchant_items` DROP INDEX `idx_mi_quality_brand`');

        // Step 3: Drop the foreign key on catalog_item_id to allow modifying unique constraint
        DB::statement('ALTER TABLE `merchant_items` DROP FOREIGN KEY `fk_mi_catalog_item`');

        // Step 4: Drop the unique constraint
        DB::statement('ALTER TABLE `merchant_items` DROP INDEX `uniq_catalog_item_user`');

        // Step 5: Rename back
        DB::statement('ALTER TABLE `merchant_items` CHANGE `quality_brand_id` `quality_brand_id` BIGINT UNSIGNED NOT NULL');

        // Step 6: Restore old unique constraint
        DB::statement('ALTER TABLE `merchant_items` ADD UNIQUE `uniq_catalog_item_user` (`catalog_item_id`, `user_id`, `merchant_branch_id`, `quality_brand_id`, `item_condition`)');

        // Step 7: Restore the foreign key on catalog_item_id
        DB::statement('ALTER TABLE `merchant_items` ADD CONSTRAINT `fk_mi_catalog_item` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }
};
