<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حذف أعمدة نطاق التطبيق من جدول discount_codes
 *
 * تم إلغاء منطق الخصم عبر الفئات/البراند/الكاتلوج
 * بسبب مشاكل الذاكرة عند تحميل الشجرة
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            // حذف الأعمدة إذا كانت موجودة
            if (Schema::hasColumn('discount_codes', 'brand_id')) {
                $table->dropColumn('brand_id');
            }
            if (Schema::hasColumn('discount_codes', 'catalog_id')) {
                $table->dropColumn('catalog_id');
            }
            if (Schema::hasColumn('discount_codes', 'new_category_id')) {
                $table->dropColumn('new_category_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('catalog_id')->nullable();
            $table->unsignedBigInteger('new_category_id')->nullable();
        });
    }
};
