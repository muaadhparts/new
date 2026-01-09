<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Owner Tracking to MerchantPurchase
 *
 * ARCHITECTURAL PRINCIPLE:
 * - user_id = 0 → Platform service → Money goes to platform
 * - user_id ≠ 0 → Merchant service → Money goes directly to merchant
 *
 * This migration adds columns to track:
 * 1. Who owns each service (payment, shipping, packing)
 * 2. Who owes whom (merchant_owes_platform or platform_owes_merchant)
 *
 * Money Flow:
 * - Platform payment gateway → Platform receives money → platform_owes_merchant
 * - Merchant payment gateway → Merchant receives money → merchant_owes_platform
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            // Service Owner Tracking (user_id: 0 = platform, else = merchant who owns the service)
            $table->unsignedBigInteger('payment_owner_id')->default(0)->after('money_received_by')
                ->comment('Owner of payment gateway: 0=platform, else=merchant');
            $table->unsignedBigInteger('shipping_owner_id')->default(0)->after('payment_owner_id')
                ->comment('Owner of shipping service: 0=platform, else=merchant');
            $table->unsignedBigInteger('packing_owner_id')->default(0)->after('shipping_owner_id')
                ->comment('Owner of packing service: 0=platform, else=merchant');

            // Financial Balance Tracking
            // These are mutually exclusive: only one will have a positive value per record
            $table->decimal('merchant_owes_platform', 12, 2)->default(0)->after('net_amount')
                ->comment('Amount merchant owes platform (when merchant receives payment directly)');
            $table->decimal('platform_owes_merchant', 12, 2)->default(0)->after('merchant_owes_platform')
                ->comment('Amount platform owes merchant (when platform receives payment)');

            // Platform service costs (when merchant uses platform services)
            $table->decimal('platform_shipping_fee', 12, 2)->default(0)->after('courier_fee')
                ->comment('Platform shipping fee (if shipping_owner_id = 0)');
            $table->decimal('platform_packing_fee', 12, 2)->default(0)->after('platform_shipping_fee')
                ->comment('Platform packing fee (if packing_owner_id = 0)');

            // Indexes for reporting
            $table->index('payment_owner_id', 'mp_payment_owner_idx');
            $table->index('shipping_owner_id', 'mp_shipping_owner_idx');
            $table->index(['merchant_owes_platform', 'platform_owes_merchant'], 'mp_balance_idx');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropIndex('mp_payment_owner_idx');
            $table->dropIndex('mp_shipping_owner_idx');
            $table->dropIndex('mp_balance_idx');

            $table->dropColumn([
                'payment_owner_id',
                'shipping_owner_id',
                'packing_owner_id',
                'merchant_owes_platform',
                'platform_owes_merchant',
                'platform_shipping_fee',
                'platform_packing_fee',
            ]);
        });
    }
};
