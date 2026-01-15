<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تحديث جدول discount_codes للنظام الجديد للتصنيفات
 *
 * التغييرات:
 * - إعادة تسمية الأعمدة القديمة (category, sub_category, child_category, apply_to) بإضافة _old
 * - إضافة عمود new_category_id للإشارة إلى جدول newcategories
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            // إعادة تسمية الأعمدة القديمة للحفاظ على البيانات
            $table->renameColumn('apply_to', 'apply_to_old');
            $table->renameColumn('category', 'category_old');
            $table->renameColumn('sub_category', 'sub_category_old');
            $table->renameColumn('child_category', 'child_category_old');
        });

        Schema::table('discount_codes', function (Blueprint $table) {
            // إضافة العمود الجديد للتصنيفات
            $table->unsignedBigInteger('new_category_id')->nullable()->after('end_date');

            // إضافة الفهرس
            $table->index('new_category_id', 'discount_codes_new_category_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            // حذف العمود الجديد
            $table->dropIndex('discount_codes_new_category_id_index');
            $table->dropColumn('new_category_id');
        });

        Schema::table('discount_codes', function (Blueprint $table) {
            // إعادة الأعمدة القديمة لأسمائها الأصلية
            $table->renameColumn('apply_to_old', 'apply_to');
            $table->renameColumn('category_old', 'category');
            $table->renameColumn('sub_category_old', 'sub_category');
            $table->renameColumn('child_category_old', 'child_category');
        });
    }
};
