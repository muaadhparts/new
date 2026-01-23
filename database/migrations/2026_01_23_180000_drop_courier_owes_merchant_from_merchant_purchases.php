<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove courier_owes_merchant column
 *
 * Reason: Couriers ALWAYS belong to the platform, never to merchants.
 * The money flow is: Courier → Platform → Merchant (never Courier → Merchant directly)
 *
 * - courier_owes_platform: Used (courier owes platform)
 * - courier_owes_merchant: Never used (always 0)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_purchases', 'courier_owes_merchant')) {
                $table->dropColumn('courier_owes_merchant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_purchases', 'courier_owes_merchant')) {
                $table->decimal('courier_owes_merchant', 15, 2)->default(0)->after('merchant_owes_platform');
            }
        });
    }
};
