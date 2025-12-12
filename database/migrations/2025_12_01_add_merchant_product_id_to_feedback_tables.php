<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة merchant_product_id لجداول التعليقات والتقييمات والبلاغات والنقرات
     */
    public function up(): void
    {
        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');
        // جدول التعليقات
        if (Schema::hasTable('comments') && !Schema::hasColumn('comments', 'merchant_product_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedInteger('merchant_product_id')->nullable()->after('product_id');
                $table->index('merchant_product_id');
            });
        }

        // جدول التقييمات
        if (Schema::hasTable('ratings') && !Schema::hasColumn('ratings', 'merchant_product_id')) {
            Schema::table('ratings', function (Blueprint $table) {
                $table->unsignedInteger('merchant_product_id')->nullable()->after('product_id');
                $table->index('merchant_product_id');
            });
        }

        // جدول البلاغات
        if (Schema::hasTable('reports') && !Schema::hasColumn('reports', 'merchant_product_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->unsignedInteger('merchant_product_id')->nullable()->after('product_id');
                $table->index('merchant_product_id');
            });
        }

        // جدول نقرات المنتجات
        if (Schema::hasTable('product_clicks') && !Schema::hasColumn('product_clicks', 'merchant_product_id')) {
            Schema::table('product_clicks', function (Blueprint $table) {
                $table->unsignedInteger('merchant_product_id')->nullable()->after('product_id');
                $table->index('merchant_product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        // حذف من جدول التعليقات
        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'merchant_product_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropIndex(['merchant_product_id']);
                $table->dropColumn('merchant_product_id');
            });
        }

        // حذف من جدول التقييمات
        if (Schema::hasTable('ratings') && Schema::hasColumn('ratings', 'merchant_product_id')) {
            Schema::table('ratings', function (Blueprint $table) {
                $table->dropIndex(['merchant_product_id']);
                $table->dropColumn('merchant_product_id');
            });
        }

        // حذف من جدول البلاغات
        if (Schema::hasTable('reports') && Schema::hasColumn('reports', 'merchant_product_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropIndex(['merchant_product_id']);
                $table->dropColumn('merchant_product_id');
            });
        }

        // حذف من جدول النقرات
        if (Schema::hasTable('product_clicks') && Schema::hasColumn('product_clicks', 'merchant_product_id')) {
            Schema::table('product_clicks', function (Blueprint $table) {
                $table->dropIndex(['merchant_product_id']);
                $table->dropColumn('merchant_product_id');
            });
        }
    }
};
