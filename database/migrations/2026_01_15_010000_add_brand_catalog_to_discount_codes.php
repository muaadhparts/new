<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة أعمدة brand_id و catalog_id لجدول discount_codes
 *
 * الشجرة الجديدة:
 * Brand → Catalog → NewCategory
 *
 * الخصم يمكن أن يطبق على:
 * - جميع المنتجات (بدون تحديد)
 * - منتجات علامة تجارية معينة (brand_id)
 * - منتجات كتالوج معين (catalog_id)
 * - منتجات تصنيف معين (new_category_id)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('end_date');
            $table->unsignedBigInteger('catalog_id')->nullable()->after('brand_id');

            // إضافة الفهارس
            $table->index('brand_id', 'discount_codes_brand_id_index');
            $table->index('catalog_id', 'discount_codes_catalog_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropIndex('discount_codes_brand_id_index');
            $table->dropIndex('discount_codes_catalog_id_index');
            $table->dropColumn(['brand_id', 'catalog_id']);
        });
    }
};
