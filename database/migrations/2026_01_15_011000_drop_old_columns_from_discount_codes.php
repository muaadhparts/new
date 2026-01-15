<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حذف الأعمدة القديمة من جدول discount_codes
 *
 * الأعمدة المحذوفة:
 * - apply_to_old
 * - category_old
 * - sub_category_old
 * - child_category_old
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn([
                'apply_to_old',
                'category_old',
                'sub_category_old',
                'child_category_old'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->string('apply_to_old')->nullable();
            $table->unsignedBigInteger('category_old')->nullable();
            $table->unsignedBigInteger('sub_category_old')->nullable();
            $table->unsignedBigInteger('child_category_old')->nullable();
        });
    }
};
