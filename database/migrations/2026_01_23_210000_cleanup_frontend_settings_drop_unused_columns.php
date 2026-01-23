<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظيف جدول frontend_settings من الأعمدة غير المستخدمة
 *
 * الأعمدة المحذوفة:
 * - site: غير مستخدم
 * - best_seller_banner, best_seller_banner_link: banners غير معروضة
 * - big_save_banner, big_save_banner_link: banners غير معروضة
 * - best_seller_banner1, best_seller_banner_link1: banners غير معروضة
 * - big_save_banner1, big_save_banner_link1: banners غير معروضة (كانت في page_banner)
 * - big_save_banner_subtitle, big_save_banner_name, big_save_banner_text: نصوص banner غير معروضة
 * - rightbanner1, rightbanner2, rightbannerlink1, rightbannerlink2: banners غير معروضة (كانت في right_banner)
 * - featured_promo: toggle غير مستخدم
 * - our_services: toggle غير مستخدم
 * - top_big_trending: toggle غير مستخدم
 * - top_banner, large_banner, bottom_banner: toggles غير مستخدمة
 * - best_selling: toggle غير مستخدم
 * - deal_of_the_day: toggle غير مستخدم
 * - best_sellers: toggle غير مستخدم
 * - third_left_banner: toggle غير مستخدم
 * - popular_items: toggle غير مستخدم
 * - flash_deal: toggle غير مستخدم
 * - top_brand: toggle غير مستخدم
 * - publication: toggle غير مستخدم
 * - brand, bottom_small: أعمدة قديمة غير مستخدمة
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frontend_settings', function (Blueprint $table) {
            $columns = [
                // Banner images and links
                'site',
                'best_seller_banner',
                'best_seller_banner_link',
                'big_save_banner',
                'big_save_banner_link',
                'best_seller_banner1',
                'best_seller_banner_link1',
                'big_save_banner1',
                'big_save_banner_link1',
                'big_save_banner_subtitle',
                'big_save_banner_name',
                'big_save_banner_text',
                'rightbanner1',
                'rightbanner2',
                'rightbannerlink1',
                'rightbannerlink2',
                'brand',
                'bottom_small',

                // Toggle columns
                'featured_promo',
                'our_services',
                'top_big_trending',
                'top_banner',
                'large_banner',
                'bottom_banner',
                'best_selling',
                'deal_of_the_day',
                'best_sellers',
                'third_left_banner',
                'popular_items',
                'flash_deal',
                'top_brand',
                'publication',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('frontend_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('frontend_settings', function (Blueprint $table) {
            // Banner images and links
            $table->text('site')->nullable();
            $table->text('best_seller_banner')->nullable();
            $table->text('best_seller_banner_link')->nullable();
            $table->text('big_save_banner')->nullable();
            $table->text('big_save_banner_link')->nullable();
            $table->text('best_seller_banner1')->nullable();
            $table->text('best_seller_banner_link1')->nullable();
            $table->text('big_save_banner1')->nullable();
            $table->text('big_save_banner_link1')->nullable();
            $table->string('big_save_banner_subtitle', 255)->nullable();
            $table->string('big_save_banner_name', 255)->nullable();
            $table->text('big_save_banner_text')->nullable();
            $table->text('rightbanner1')->nullable();
            $table->text('rightbanner2')->nullable();
            $table->text('rightbannerlink1')->nullable();
            $table->text('rightbannerlink2')->nullable();
            $table->text('brand')->nullable();
            $table->text('bottom_small')->nullable();

            // Toggle columns
            $table->tinyInteger('featured_promo')->default(1);
            $table->tinyInteger('our_services')->default(1);
            $table->tinyInteger('top_big_trending')->default(0);
            $table->integer('top_banner')->default(1);
            $table->integer('large_banner')->default(1);
            $table->integer('bottom_banner')->default(1);
            $table->integer('best_selling')->default(1);
            $table->integer('deal_of_the_day')->default(1);
            $table->tinyInteger('best_sellers')->default(1);
            $table->integer('third_left_banner')->default(0);
            $table->tinyInteger('popular_items')->default(1);
            $table->tinyInteger('flash_deal')->default(1);
            $table->tinyInteger('top_brand')->default(1);
            $table->tinyInteger('publication')->default(0);
        });
    }
};
