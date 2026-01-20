<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove homepage classification columns from merchant_items table.
 *
 * These features have been permanently removed from the system:
 * - featured: Featured items section
 * - top: Top rated items section
 * - big: Big save items section
 * - trending: Trending items section
 * - best: Best sellers section
 * - is_discount: Deal of the day discount flag
 * - discount_date: Deal of the day expiry date
 * - popular: Legacy popularity flag
 * - is_popular: Legacy popularity flag (duplicate)
 *
 * The entire homepage classification system has been deleted including:
 * - Controller methods
 * - Views
 * - Routes
 * - Model attributes
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Drop classification columns
            if (Schema::hasColumn('merchant_items', 'featured')) {
                $table->dropColumn('featured');
            }
            if (Schema::hasColumn('merchant_items', 'top')) {
                $table->dropColumn('top');
            }
            if (Schema::hasColumn('merchant_items', 'big')) {
                $table->dropColumn('big');
            }
            if (Schema::hasColumn('merchant_items', 'trending')) {
                $table->dropColumn('trending');
            }
            if (Schema::hasColumn('merchant_items', 'best')) {
                $table->dropColumn('best');
            }
            if (Schema::hasColumn('merchant_items', 'is_discount')) {
                $table->dropColumn('is_discount');
            }
            if (Schema::hasColumn('merchant_items', 'discount_date')) {
                $table->dropColumn('discount_date');
            }
            if (Schema::hasColumn('merchant_items', 'popular')) {
                $table->dropColumn('popular');
            }
            if (Schema::hasColumn('merchant_items', 'is_popular')) {
                $table->dropColumn('is_popular');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->tinyInteger('featured')->unsigned()->default(0)->after('features');
            $table->tinyInteger('top')->unsigned()->default(0)->after('featured');
            $table->tinyInteger('big')->unsigned()->default(0)->after('top');
            $table->tinyInteger('trending')->default(0)->after('big');
            $table->tinyInteger('best')->unsigned()->default(0)->after('trending');
            $table->tinyInteger('is_discount')->unsigned()->default(0)->after('best');
            $table->date('discount_date')->nullable()->after('is_discount');
            $table->tinyInteger('popular')->unsigned()->default(0)->after('discount_date');
            $table->tinyInteger('is_popular')->unsigned()->default(0)->after('popular');
        });
    }
};
