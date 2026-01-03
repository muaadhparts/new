<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename catalogItems table to catalog_items
     * SAFE APPROACH: Create new table, migrate data, rename old table with _old suffix
     */
    public function up(): void
    {
        // Step 1: Create catalog_items table with same structure as catalogItems
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('brand_id')->default(2);
            $table->string('sku', 100)->nullable()->unique('uq_sku');
            $table->string('parent_category', 100)->nullable();
            $table->integer('category_id')->unsigned();
            $table->integer('subcategory_id')->unsigned()->nullable();
            $table->integer('childcategory_id')->unsigned()->nullable();
            $table->string('label_en', 255)->nullable();
            $table->string('label_ar', 255)->nullable();
            $table->text('attributes')->nullable();
            $table->text('name');
            $table->text('slug')->nullable();
            $table->string('photo', 255)->default('LargePNG/SVG/noimage.png');
            $table->string('thumbnail', 255)->default('SmallPNG/SVG/noimage.png');
            $table->string('file', 191)->nullable();
            $table->decimal('weight', 10, 2)->nullable()->default(1.00);
            $table->integer('views')->unsigned()->default(0);
            $table->string('tags', 191)->nullable();
            $table->tinyInteger('is_meta')->default(0);
            $table->text('meta_tag')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('youtube', 191)->nullable();
            $table->enum('type', ['Physical', 'Digital', 'License', 'Listing']);
            $table->text('link')->nullable();
            $table->string('platform', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->string('measure', 191)->nullable();
            $table->tinyInteger('hot')->unsigned()->default(0);
            $table->tinyInteger('latest')->unsigned()->default(0);
            $table->tinyInteger('sale')->default(0);
            $table->timestamps();
            $table->tinyInteger('is_catalog')->default(0);
            $table->integer('catalog_id')->default(0);
            $table->string('cross_products', 255)->nullable();
            $table->string('length', 191)->nullable();
            $table->string('height', 191)->nullable();
            $table->decimal('width', 10, 2)->nullable()->comment('CatalogItem width in cm for volumetric weight calculation');

            // Indexes
            $table->index('sku', 'idx_catalog_items_sku');
            $table->index('category_id', 'idx_catalog_items_category_id');
        });

        // Add fulltext indexes separately (Laravel doesn't support them directly in Blueprint)
        DB::statement('ALTER TABLE `catalog_items` ADD FULLTEXT KEY `name` (`name`)');
        DB::statement('ALTER TABLE `catalog_items` ADD FULLTEXT KEY `attributes` (`attributes`)');

        // Step 2: Copy all data from catalogItems to catalog_items
        DB::statement('
            INSERT INTO `catalog_items` (
                `id`, `brand_id`, `sku`, `parent_category`, `category_id`, `subcategory_id`,
                `childcategory_id`, `label_en`, `label_ar`, `attributes`, `name`, `slug`,
                `photo`, `thumbnail`, `file`, `weight`, `views`, `tags`, `is_meta`,
                `meta_tag`, `meta_description`, `youtube`, `type`, `link`, `platform`,
                `region`, `measure`, `hot`, `latest`, `sale`, `created_at`, `updated_at`,
                `is_catalog`, `catalog_id`, `cross_products`, `length`, `height`, `width`
            )
            SELECT
                `id`, `brand_id`, `sku`, `parent_category`, `category_id`, `subcategory_id`,
                `childcategory_id`, `label_en`, `label_ar`, `attributes`, `name`, `slug`,
                `photo`, `thumbnail`, `file`, `weight`, `views`, `tags`, `is_meta`,
                `meta_tag`, `meta_description`, `youtube`, `type`, `link`, `platform`,
                `region`, `measure`, `hot`, `latest`, `sale`, `created_at`, `updated_at`,
                `is_catalog`, `catalog_id`, `cross_products`, `length`, `height`, `width`
            FROM `catalogItems`
        ');

        // Step 3: Reset auto_increment to match catalogItems table
        $maxId = DB::table('catalog_items')->max('id');
        if ($maxId) {
            DB::statement("ALTER TABLE `catalog_items` AUTO_INCREMENT = " . ($maxId + 1));
        }

        // Step 4: Rename catalogItems to products_old (NEVER DELETE!)
        Schema::rename('catalogItems', 'products_old');
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // Rename products_old back to catalogItems
        if (Schema::hasTable('products_old')) {
            Schema::rename('products_old', 'catalogItems');
        }

        // Drop catalog_items table (safe because data is in catalogItems)
        Schema::dropIfExists('catalog_items');
    }
};
