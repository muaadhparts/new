<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Settlement Type to MerchantSettlement
 *
 * ARCHITECTURAL PRINCIPLE:
 * - platform_pays_merchant: Platform collected payment, owes merchant
 * - merchant_pays_platform: Merchant collected payment, owes platform
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_settlements', function (Blueprint $table) {
            // Settlement direction
            $table->string('settlement_type', 50)->nullable()->after('status')
                ->comment('platform_pays_merchant or merchant_pays_platform');

            // Detailed balance tracking
            $table->decimal('platform_owes_merchant', 12, 2)->default(0)->after('net_payable')
                ->comment('Total platform owes merchant from platform payments');
            $table->decimal('merchant_owes_platform', 12, 2)->default(0)->after('platform_owes_merchant')
                ->comment('Total merchant owes platform from merchant payments');

            // Index for querying by type
            $table->index('settlement_type', 'ms_settlement_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_settlements', function (Blueprint $table) {
            $table->dropIndex('ms_settlement_type_idx');
            $table->dropColumn(['settlement_type', 'platform_owes_merchant', 'merchant_owes_platform']);
        });
    }
};
