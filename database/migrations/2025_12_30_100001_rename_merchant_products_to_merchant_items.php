<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename merchant_items table to merchant_items
     * Also rename catalog_item_id to catalog_item_id
     * SAFE APPROACH: Create new table, migrate data, rename old table with _old suffix
     */
    public function up(): void
    {
        // Step 1: Create merchant_items table with updated structure
        Schema::create('merchant_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('catalog_item_id')->unsigned(); // renamed from catalog_item_id
            $table->integer('user_id')->unsigned();
            $table->bigInteger('brand_quality_id')->unsigned();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('previous_price', 10, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->tinyInteger('is_discount')->default(0);
            $table->date('discount_date')->nullable();
            $table->text('whole_sell_qty')->nullable();
            $table->text('whole_sell_discount')->nullable();
            $table->tinyInteger('preordered')->default(0);
            $table->string('minimum_qty', 191)->nullable();
            $table->integer('stock_check')->default(0);
            $table->integer('popular')->default(0);
            $table->tinyInteger('status')->unsigned()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->integer('is_popular')->default(0);
            $table->string('licence_type', 255)->nullable();
            $table->text('license_qty')->nullable();
            $table->text('license')->nullable();
            $table->string('ship', 191)->nullable();
            $table->tinyInteger('product_condition')->default(2);
            $table->text('color_all')->nullable();
            $table->text('color_price')->nullable();
            $table->text('details')->nullable();
            $table->text('policy')->nullable();
            $table->text('features')->nullable();
            $table->text('colors')->nullable();
            $table->string('size', 191)->nullable();
            $table->string('size_qty', 191)->nullable();
            $table->enum('product_type', ['normal', 'affiliate'])->default('normal');
            $table->string('size_price', 191)->nullable();
            $table->text('affiliate_link')->nullable();
            $table->tinyInteger('featured')->unsigned()->default(0);
            $table->tinyInteger('top')->unsigned()->default(0);
            $table->tinyInteger('big')->unsigned()->default(0);
            $table->tinyInteger('trending')->default(0);
            $table->tinyInteger('best')->unsigned()->default(0);

            // Unique constraint with new column name
            $table->unique(['catalog_item_id', 'user_id', 'brand_quality_id'], 'uniq_catalog_item_user');

            // Indexes
            $table->index('user_id', 'fk_mi_user');
            $table->index('brand_quality_id', 'idx_mi_brand_quality');
            $table->index('product_type', 'mi_product_type');

            // Foreign keys
            $table->foreign('catalog_item_id', 'fk_mi_catalog_item')
                ->references('id')->on('catalog_items')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_mi_user')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('brand_quality_id', 'fk_mi_brand_quality')
                ->references('id')->on('brand_qualities')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        // Step 2: Copy all data from merchant_items to merchant_items
        // Note: catalog_item_id is mapped to catalog_item_id
        DB::statement('
            INSERT INTO `merchant_items` (
                `id`, `catalog_item_id`, `user_id`, `brand_quality_id`, `price`, `previous_price`,
                `stock`, `is_discount`, `discount_date`, `whole_sell_qty`, `whole_sell_discount`,
                `preordered`, `minimum_qty`, `stock_check`, `popular`, `status`, `created_at`,
                `updated_at`, `is_popular`, `licence_type`, `license_qty`, `license`, `ship`,
                `product_condition`, `color_all`, `color_price`, `details`, `policy`, `features`,
                `colors`, `size`, `size_qty`, `product_type`, `size_price`, `affiliate_link`,
                `featured`, `top`, `big`, `trending`, `best`
            )
            SELECT
                `id`, `catalog_item_id`, `user_id`, `brand_quality_id`, `price`, `previous_price`,
                `stock`, `is_discount`, `discount_date`, `whole_sell_qty`, `whole_sell_discount`,
                `preordered`, `minimum_qty`, `stock_check`, `popular`, `status`, `created_at`,
                `updated_at`, `is_popular`, `licence_type`, `license_qty`, `license`, `ship`,
                `product_condition`, `color_all`, `color_price`, `details`, `policy`, `features`,
                `colors`, `size`, `size_qty`, `product_type`, `size_price`, `affiliate_link`,
                `featured`, `top`, `big`, `trending`, `best`
            FROM `merchant_items`
        ');

        // Step 3: Reset auto_increment to match merchant_items table
        $maxId = DB::table('merchant_items')->max('id');
        if ($maxId) {
            DB::statement("ALTER TABLE `merchant_items` AUTO_INCREMENT = " . ($maxId + 1));
        }

        // Step 4: Rename merchant_items to merchant_items_old (NEVER DELETE!)
        Schema::rename('merchant_items', 'merchant_items_old');
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // Rename merchant_items_old back to merchant_items
        if (Schema::hasTable('merchant_items_old')) {
            Schema::rename('merchant_items_old', 'merchant_items');
        }

        // Drop merchant_items table (safe because data is in merchant_items)
        Schema::dropIfExists('merchant_items');
    }
};
