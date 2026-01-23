<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove merchant_ship_info column
 *
 * Old logic: Toggle to show/hide shipping menu for merchants
 * New logic: Shipping ownership determined by operator/user_id columns in shipping table:
 *   - operator=merchant_id, user_id=0 → Platform provides shipping
 *   - operator=0, user_id=merchant_id → Merchant provides shipping
 *   - Both 0 → Shipping method disabled
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'merchant_ship_info')) {
                $table->dropColumn('merchant_ship_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (!Schema::hasColumn('muaadhsettings', 'merchant_ship_info')) {
                $table->boolean('merchant_ship_info')->default(1)->after('affilate_banner');
            }
        });
    }
};
