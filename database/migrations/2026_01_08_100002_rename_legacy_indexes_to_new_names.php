<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename legacy index and constraint names to match new terminology:
 * - vendor_* → merchant_*
 * - product_* → catalog_item_* or item_*
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. catalog_item_clicks: rename index
        DB::statement('ALTER TABLE `catalog_item_clicks`
            DROP INDEX `product_clicks_merchant_product_id_index`');
        DB::statement('ALTER TABLE `catalog_item_clicks`
            ADD INDEX `catalog_item_clicks_merchant_item_id_index` (`merchant_item_id`)');

        // 2. merchant_items: rename index
        DB::statement('ALTER TABLE `merchant_items`
            DROP INDEX `mi_product_type`');
        DB::statement('ALTER TABLE `merchant_items`
            ADD INDEX `mi_item_type` (`item_type`)');

        // 3. merchant_credentials: rename indexes and constraint
        // Drop foreign key first
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP FOREIGN KEY `vendor_credentials_user_id_foreign`');

        // Drop old indexes
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `vendor_service_key_env_unique`');
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `vendor_credentials_user_id_index`');
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `vendor_credentials_service_name_index`');

        // Add new indexes
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD UNIQUE INDEX `merchant_service_key_env_unique` (`user_id`, `service_name`, `key_name`, `environment`)');
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD INDEX `merchant_credentials_user_id_index` (`user_id`)');
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD INDEX `merchant_credentials_service_name_index` (`service_name`)');

        // Add foreign key back
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD CONSTRAINT `merchant_credentials_user_id_foreign`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');

        // 4. merchant_stock_updates: rename indexes and constraint
        // Drop foreign key first
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP FOREIGN KEY `vendor_stock_updates_user_id_foreign`');

        // Drop old indexes
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `vendor_stock_updates_user_id_index`');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `vendor_stock_updates_status_index`');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `vendor_stock_updates_update_type_index`');

        // Add new indexes
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `merchant_stock_updates_user_id_index` (`user_id`)');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `merchant_stock_updates_status_index` (`status`)');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `merchant_stock_updates_update_type_index` (`update_type`)');

        // Add foreign key back
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD CONSTRAINT `merchant_stock_updates_user_id_foreign`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // 1. catalog_item_clicks
        DB::statement('ALTER TABLE `catalog_item_clicks`
            DROP INDEX `catalog_item_clicks_merchant_item_id_index`');
        DB::statement('ALTER TABLE `catalog_item_clicks`
            ADD INDEX `product_clicks_merchant_product_id_index` (`merchant_item_id`)');

        // 2. merchant_items
        DB::statement('ALTER TABLE `merchant_items`
            DROP INDEX `mi_item_type`');
        DB::statement('ALTER TABLE `merchant_items`
            ADD INDEX `mi_product_type` (`item_type`)');

        // 3. merchant_credentials
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP FOREIGN KEY `merchant_credentials_user_id_foreign`');
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `merchant_service_key_env_unique`');
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `merchant_credentials_user_id_index`');
        DB::statement('ALTER TABLE `merchant_credentials`
            DROP INDEX `merchant_credentials_service_name_index`');

        DB::statement('ALTER TABLE `merchant_credentials`
            ADD UNIQUE INDEX `vendor_service_key_env_unique` (`user_id`, `service_name`, `key_name`, `environment`)');
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD INDEX `vendor_credentials_user_id_index` (`user_id`)');
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD INDEX `vendor_credentials_service_name_index` (`service_name`)');
        DB::statement('ALTER TABLE `merchant_credentials`
            ADD CONSTRAINT `vendor_credentials_user_id_foreign`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');

        // 4. merchant_stock_updates
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP FOREIGN KEY `merchant_stock_updates_user_id_foreign`');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `merchant_stock_updates_user_id_index`');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `merchant_stock_updates_status_index`');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            DROP INDEX `merchant_stock_updates_update_type_index`');

        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `vendor_stock_updates_user_id_index` (`user_id`)');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `vendor_stock_updates_status_index` (`status`)');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD INDEX `vendor_stock_updates_update_type_index` (`update_type`)');
        DB::statement('ALTER TABLE `merchant_stock_updates`
            ADD CONSTRAINT `vendor_stock_updates_user_id_foreign`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
    }
};
