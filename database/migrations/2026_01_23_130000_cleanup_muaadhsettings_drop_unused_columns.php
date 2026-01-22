<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove unused columns from muaadhsettings
 *
 * These columns are no longer used in the application:
 * - Old theme colors (replaced by Theme Builder)
 * - Old feature flags (features removed)
 * - Homepage count columns (homepage features removed)
 * - Product type flags (product types removed)
 * - Deal feature columns
 * - Disqus integration (removed)
 * - Old settings that have been replaced
 */
return new class extends Migration
{
    /**
     * Columns to drop - all confirmed unused
     */
    private array $columnsToDrop = [
        // Old currency/color system
        'sign_old',
        'colors_old',

        // Old theme colors (Theme Builder replaces these)
        'theme_primary_old',
        'theme_primary_hover_old',
        'theme_primary_dark_old',
        'theme_primary_light_old',
        'theme_secondary_old',
        'theme_secondary_hover_old',

        // Old feature flags
        'is_language_old',
        'is_loader_old',
        'is_disqus_old',
        'disqus_old',
        'guest_checkout_old',
        'shipping_cost_old',
        'is_smtp_old',
        'multiple_packaging_old',
        'footer_color_old',
        'copyright_color_old',
        'is_secure_old',
        'header_color_old',
        'is_buy_now_old',
        'version_old',
        'affilate_item_old',

        // Homepage count columns (features removed)
        'flash_count_old',
        'hot_count_old',
        'new_count_old',
        'sale_count_old',
        'best_seller_count_old',
        'popular_count_old',
        'top_rated_count_old',
        'big_save_count_old',
        'trending_count_old',
        'seller_item_count_old',
        'post_count_old',
        'favorite_page_old',
        'is_contact_seller_old',

        // Item affiliate (unused)
        'item_affilate_old',
        'item_affilate_bonus_old',

        // Product type flags (removed)
        'physical_old',
        'license_old',
        'listing_old',
        'affilite_old',

        // Deal feature columns
        'deal_name_old',
        'deal_details_old',
        'deal_time_old',
    ];

    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            foreach ($this->columnsToDrop as $column) {
                if (Schema::hasColumn('muaadhsettings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            // Recreate columns if needed (with nullable defaults)
            if (!Schema::hasColumn('muaadhsettings', 'sign_old')) {
                $table->string('sign_old', 10)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'colors_old')) {
                $table->string('colors_old', 191)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_primary_old')) {
                $table->string('theme_primary_old', 20)->nullable()->default('#c3002f');
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_primary_hover_old')) {
                $table->string('theme_primary_hover_old', 20)->nullable()->default('#a00025');
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_primary_dark_old')) {
                $table->string('theme_primary_dark_old', 20)->nullable()->default('#8a0020');
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_primary_light_old')) {
                $table->string('theme_primary_light_old', 20)->nullable()->default('#fef2f4');
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_secondary_old')) {
                $table->string('theme_secondary_old', 20)->nullable()->default('#1a1a1a');
            }
            if (!Schema::hasColumn('muaadhsettings', 'theme_secondary_hover_old')) {
                $table->string('theme_secondary_hover_old', 20)->nullable()->default('#333333');
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_language_old')) {
                $table->boolean('is_language_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_loader_old')) {
                $table->boolean('is_loader_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_disqus_old')) {
                $table->boolean('is_disqus_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'disqus_old')) {
                $table->longText('disqus_old')->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'guest_checkout_old')) {
                $table->boolean('guest_checkout_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'shipping_cost_old')) {
                $table->double('shipping_cost_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_smtp_old')) {
                $table->boolean('is_smtp_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'multiple_packaging_old')) {
                $table->tinyInteger('multiple_packaging_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'footer_color_old')) {
                $table->string('footer_color_old', 191)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'copyright_color_old')) {
                $table->string('copyright_color_old', 191)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_secure_old')) {
                $table->boolean('is_secure_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'header_color_old')) {
                $table->enum('header_color_old', ['light', 'dark'])->default('light');
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_buy_now_old')) {
                $table->tinyInteger('is_buy_now_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'version_old')) {
                $table->string('version_old', 40)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'affilate_item_old')) {
                $table->boolean('affilate_item_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'flash_count_old')) {
                $table->integer('flash_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'hot_count_old')) {
                $table->integer('hot_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'new_count_old')) {
                $table->integer('new_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'sale_count_old')) {
                $table->integer('sale_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'best_seller_count_old')) {
                $table->integer('best_seller_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'popular_count_old')) {
                $table->integer('popular_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'top_rated_count_old')) {
                $table->integer('top_rated_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'big_save_count_old')) {
                $table->integer('big_save_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'trending_count_old')) {
                $table->integer('trending_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'seller_item_count_old')) {
                $table->integer('seller_item_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'post_count_old')) {
                $table->boolean('post_count_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'favorite_page_old')) {
                $table->text('favorite_page_old')->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'is_contact_seller_old')) {
                $table->boolean('is_contact_seller_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'item_affilate_old')) {
                $table->boolean('item_affilate_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'item_affilate_bonus_old')) {
                $table->integer('item_affilate_bonus_old')->default(0);
            }
            if (!Schema::hasColumn('muaadhsettings', 'physical_old')) {
                $table->tinyInteger('physical_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'license_old')) {
                $table->tinyInteger('license_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'listing_old')) {
                $table->tinyInteger('listing_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'affilite_old')) {
                $table->tinyInteger('affilite_old')->default(1);
            }
            if (!Schema::hasColumn('muaadhsettings', 'deal_name_old')) {
                $table->string('deal_name_old', 255)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'deal_details_old')) {
                $table->string('deal_details_old', 600)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'deal_time_old')) {
                $table->date('deal_time_old')->nullable();
            }
        });
    }
};
