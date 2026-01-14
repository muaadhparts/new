<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة عمود tryoto_warehouse_code لجدول merchant_locations
 *
 * هذا العمود يخزن الـ Warehouse Code من لوحة تحكم Tryoto
 * يجب أن يتطابق مع الـ Code المسجل في حساب التاجر في Tryoto
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_locations', function (Blueprint $table) {
            // Tryoto Warehouse Code - يجب أن يتطابق مع الـ Code في لوحة تحكم Tryoto
            $table->string('tryoto_warehouse_code', 50)->nullable()->after('warehouse_name')
                ->comment('Warehouse Code from Tryoto dashboard');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->dropColumn('tryoto_warehouse_code');
        });
    }
};
