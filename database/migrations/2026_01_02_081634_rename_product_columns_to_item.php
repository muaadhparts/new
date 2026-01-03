<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename all "product" columns to "item" or "catalog_item" for consistency.
 *
 * This is part of the terminology standardization:
 * - product → catalog_item / item
 * - vendor → merchant
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. catalog_items table
        if (Schema::hasColumn('catalog_items', 'cross_products')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->renameColumn('cross_products', 'cross_items');
            });
        }

        // 2. merchant_items table
        Schema::table('merchant_items', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_items', 'product_condition')) {
                $table->renameColumn('product_condition', 'item_condition');
            }
            if (Schema::hasColumn('merchant_items', 'product_type')) {
                $table->renameColumn('product_type', 'item_type');
            }
        });

        // 3. home_page_themes table
        if (Schema::hasTable('home_page_themes')) {
            Schema::table('home_page_themes', function (Blueprint $table) {
                if (Schema::hasColumn('home_page_themes', 'count_featured_products')) {
                    $table->renameColumn('count_featured_products', 'count_featured_items');
                }
                if (Schema::hasColumn('home_page_themes', 'order_featured_products')) {
                    $table->renameColumn('order_featured_products', 'order_featured_items');
                }
                if (Schema::hasColumn('home_page_themes', 'show_featured_products')) {
                    $table->renameColumn('show_featured_products', 'show_featured_items');
                }
                if (Schema::hasColumn('home_page_themes', 'title_featured_products')) {
                    $table->renameColumn('title_featured_products', 'title_featured_items');
                }
            });
        }

        // 4. muaadhsettings table
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $renames = [
                'affilate_product' => 'affilate_item',
                'product_affilate' => 'item_affilate',
                'product_affilate_bonus' => 'item_affilate_bonus',
                'product_page' => 'item_page',
                'seller_product_count' => 'seller_item_count',
                'theme_product_card_radius' => 'theme_item_card_radius',
                'theme_product_hover_scale' => 'theme_item_hover_scale',
                'theme_product_img_radius' => 'theme_item_img_radius',
                'theme_product_price_size' => 'theme_item_price_size',
                'theme_product_price_weight' => 'theme_item_price_weight',
                'theme_product_title_size' => 'theme_item_title_size',
                'theme_product_title_weight' => 'theme_item_title_weight',
                'verify_product' => 'verify_item',
            ];

            foreach ($renames as $old => $new) {
                if (Schema::hasColumn('muaadhsettings', $old)) {
                    $table->renameColumn($old, $new);
                }
            }
        });

        // 5. pagesettings table
        if (Schema::hasColumn('pagesettings', 'popular_products')) {
            Schema::table('pagesettings', function (Blueprint $table) {
                $table->renameColumn('popular_products', 'popular_items');
            });
        }

        // 6. subscriptions table
        if (Schema::hasColumn('subscriptions', 'allowed_products')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('allowed_products', 'allowed_items');
            });
        }

        // 7. user_subscriptions table
        if (Schema::hasColumn('user_subscriptions', 'allowed_products')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->renameColumn('allowed_products', 'allowed_items');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. catalog_items table
        if (Schema::hasColumn('catalog_items', 'cross_items')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->renameColumn('cross_items', 'cross_products');
            });
        }

        // 2. merchant_items table
        Schema::table('merchant_items', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_items', 'item_condition')) {
                $table->renameColumn('item_condition', 'product_condition');
            }
            if (Schema::hasColumn('merchant_items', 'item_type')) {
                $table->renameColumn('item_type', 'product_type');
            }
        });

        // 3. home_page_themes table
        if (Schema::hasTable('home_page_themes')) {
            Schema::table('home_page_themes', function (Blueprint $table) {
                if (Schema::hasColumn('home_page_themes', 'count_featured_items')) {
                    $table->renameColumn('count_featured_items', 'count_featured_products');
                }
                if (Schema::hasColumn('home_page_themes', 'order_featured_items')) {
                    $table->renameColumn('order_featured_items', 'order_featured_products');
                }
                if (Schema::hasColumn('home_page_themes', 'show_featured_items')) {
                    $table->renameColumn('show_featured_items', 'show_featured_products');
                }
                if (Schema::hasColumn('home_page_themes', 'title_featured_items')) {
                    $table->renameColumn('title_featured_items', 'title_featured_products');
                }
            });
        }

        // 4. muaadhsettings table
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $renames = [
                'affilate_item' => 'affilate_product',
                'item_affilate' => 'product_affilate',
                'item_affilate_bonus' => 'product_affilate_bonus',
                'item_page' => 'product_page',
                'seller_item_count' => 'seller_product_count',
                'theme_item_card_radius' => 'theme_product_card_radius',
                'theme_item_hover_scale' => 'theme_product_hover_scale',
                'theme_item_img_radius' => 'theme_product_img_radius',
                'theme_item_price_size' => 'theme_product_price_size',
                'theme_item_price_weight' => 'theme_product_price_weight',
                'theme_item_title_size' => 'theme_product_title_size',
                'theme_item_title_weight' => 'theme_product_title_weight',
                'verify_item' => 'verify_product',
            ];

            foreach ($renames as $old => $new) {
                if (Schema::hasColumn('muaadhsettings', $old)) {
                    $table->renameColumn($old, $new);
                }
            }
        });

        // 5. pagesettings table
        if (Schema::hasColumn('pagesettings', 'popular_items')) {
            Schema::table('pagesettings', function (Blueprint $table) {
                $table->renameColumn('popular_items', 'popular_products');
            });
        }

        // 6. subscriptions table
        if (Schema::hasColumn('subscriptions', 'allowed_items')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('allowed_items', 'allowed_products');
            });
        }

        // 7. user_subscriptions table
        if (Schema::hasColumn('user_subscriptions', 'allowed_items')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->renameColumn('allowed_items', 'allowed_products');
            });
        }
    }
};
