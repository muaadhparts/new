<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove homepage classification columns from home_page_themes table.
 *
 * These features have been permanently removed from the system:
 * - Featured items section
 * - Deal of the day section
 * - Top rated section
 * - Big save section
 * - Trending section
 * - Best sellers section
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('home_page_themes', function (Blueprint $table) {
            // Drop show_* columns
            if (Schema::hasColumn('home_page_themes', 'show_featured_items')) {
                $table->dropColumn('show_featured_items');
            }
            if (Schema::hasColumn('home_page_themes', 'show_deal_of_day')) {
                $table->dropColumn('show_deal_of_day');
            }
            if (Schema::hasColumn('home_page_themes', 'show_top_rated')) {
                $table->dropColumn('show_top_rated');
            }
            if (Schema::hasColumn('home_page_themes', 'show_big_save')) {
                $table->dropColumn('show_big_save');
            }
            if (Schema::hasColumn('home_page_themes', 'show_trending')) {
                $table->dropColumn('show_trending');
            }
            if (Schema::hasColumn('home_page_themes', 'show_best_sellers')) {
                $table->dropColumn('show_best_sellers');
            }

            // Drop order_* columns
            if (Schema::hasColumn('home_page_themes', 'order_featured_items')) {
                $table->dropColumn('order_featured_items');
            }
            if (Schema::hasColumn('home_page_themes', 'order_deal_of_day')) {
                $table->dropColumn('order_deal_of_day');
            }
            if (Schema::hasColumn('home_page_themes', 'order_top_rated')) {
                $table->dropColumn('order_top_rated');
            }
            if (Schema::hasColumn('home_page_themes', 'order_big_save')) {
                $table->dropColumn('order_big_save');
            }
            if (Schema::hasColumn('home_page_themes', 'order_trending')) {
                $table->dropColumn('order_trending');
            }
            if (Schema::hasColumn('home_page_themes', 'order_best_sellers')) {
                $table->dropColumn('order_best_sellers');
            }

            // Drop name_* columns
            if (Schema::hasColumn('home_page_themes', 'name_arrival')) {
                $table->dropColumn('name_arrival');
            }
            if (Schema::hasColumn('home_page_themes', 'name_featured_items')) {
                $table->dropColumn('name_featured_items');
            }
            if (Schema::hasColumn('home_page_themes', 'name_deal_of_day')) {
                $table->dropColumn('name_deal_of_day');
            }
            if (Schema::hasColumn('home_page_themes', 'name_top_rated')) {
                $table->dropColumn('name_top_rated');
            }
            if (Schema::hasColumn('home_page_themes', 'name_big_save')) {
                $table->dropColumn('name_big_save');
            }
            if (Schema::hasColumn('home_page_themes', 'name_trending')) {
                $table->dropColumn('name_trending');
            }
            if (Schema::hasColumn('home_page_themes', 'name_best_sellers')) {
                $table->dropColumn('name_best_sellers');
            }

            // Drop count_* columns
            if (Schema::hasColumn('home_page_themes', 'count_featured_items')) {
                $table->dropColumn('count_featured_items');
            }
            if (Schema::hasColumn('home_page_themes', 'count_top_rated')) {
                $table->dropColumn('count_top_rated');
            }
            if (Schema::hasColumn('home_page_themes', 'count_big_save')) {
                $table->dropColumn('count_big_save');
            }
            if (Schema::hasColumn('home_page_themes', 'count_trending')) {
                $table->dropColumn('count_trending');
            }
            if (Schema::hasColumn('home_page_themes', 'count_best_sellers')) {
                $table->dropColumn('count_best_sellers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_page_themes', function (Blueprint $table) {
            // Restore show_* columns
            $table->boolean('show_featured_items')->default(true)->after('show_arrival');
            $table->boolean('show_deal_of_day')->default(true)->after('show_featured_items');
            $table->boolean('show_top_rated')->default(true)->after('show_deal_of_day');
            $table->boolean('show_big_save')->default(true)->after('show_top_rated');
            $table->boolean('show_trending')->default(true)->after('show_big_save');
            $table->boolean('show_best_sellers')->default(true)->after('show_trending');

            // Restore order_* columns
            $table->integer('order_featured_items')->default(5)->after('order_arrival');
            $table->integer('order_deal_of_day')->default(6)->after('order_featured_items');
            $table->integer('order_top_rated')->default(7)->after('order_deal_of_day');
            $table->integer('order_big_save')->default(8)->after('order_top_rated');
            $table->integer('order_trending')->default(9)->after('order_big_save');
            $table->integer('order_best_sellers')->default(10)->after('order_trending');

            // Restore name_* columns
            $table->string('name_arrival')->nullable()->after('name_categories');
            $table->string('name_featured_items')->nullable()->after('name_arrival');
            $table->string('name_deal_of_day')->nullable()->after('name_featured_items');
            $table->string('name_top_rated')->nullable()->after('name_deal_of_day');
            $table->string('name_big_save')->nullable()->after('name_top_rated');
            $table->string('name_trending')->nullable()->after('name_big_save');
            $table->string('name_best_sellers')->nullable()->after('name_trending');

            // Restore count_* columns
            $table->integer('count_featured_items')->default(8)->after('name_blogs');
            $table->integer('count_top_rated')->default(6)->after('count_featured_items');
            $table->integer('count_big_save')->default(6)->after('count_top_rated');
            $table->integer('count_trending')->default(6)->after('count_big_save');
            $table->integer('count_best_sellers')->default(8)->after('count_trending');
        });
    }
};
