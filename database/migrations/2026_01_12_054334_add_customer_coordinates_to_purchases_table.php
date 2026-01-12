<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة إحداثيات العميل للاستخدام في الشحن
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('customer_latitude', 10, 7)->nullable()->after('customer_zip');
            $table->decimal('customer_longitude', 10, 7)->nullable()->after('customer_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['customer_latitude', 'customer_longitude']);
        });
    }
};
