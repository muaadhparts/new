<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comprehensive Accounting Fields for MerchantPurchase
 *
 * This migration adds all necessary fields for tracking debts between:
 * - Platform ↔ Merchant
 * - Courier ↔ Merchant/Platform
 * - Shipping Company ↔ Merchant/Platform
 *
 * Supports both Online Payment and COD scenarios
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            // === Courier Debt Tracking ===
            // When COD via local courier, courier collects and owes
            $table->decimal('courier_owes_merchant', 10, 2)->default(0)
                ->after('platform_owes_merchant')
                ->comment('Amount courier owes to merchant (COD collected)');

            $table->decimal('courier_owes_platform', 10, 2)->default(0)
                ->after('courier_owes_merchant')
                ->comment('Amount courier owes to platform (if platform gateway)');

            // === Shipping Company Debt Tracking ===
            // When COD via shipping company (Tryoto, etc), they collect and owe
            $table->decimal('shipping_company_owes_merchant', 10, 2)->default(0)
                ->after('courier_owes_platform')
                ->comment('Amount shipping company owes to merchant (COD collected)');

            $table->decimal('shipping_company_owes_platform', 10, 2)->default(0)
                ->after('shipping_company_owes_merchant')
                ->comment('Amount shipping company owes to platform (if platform gateway)');

            // === COD Amount ===
            // Total amount to be collected (pay_amount + delivery_fee)
            $table->decimal('cod_amount', 10, 2)->default(0)
                ->after('shipping_company_owes_platform')
                ->comment('Total COD amount (purchase + delivery fee)');

            // === Money Holder ===
            // Who currently holds the money
            $table->enum('money_holder', ['platform', 'merchant', 'courier', 'shipping_company', 'pending'])
                ->default('pending')
                ->after('cod_amount')
                ->comment('Entity currently holding the payment');

            // === Delivery Method & Provider ===
            $table->enum('delivery_method', ['local_courier', 'shipping_company', 'pickup', 'digital', 'none'])
                ->nullable()
                ->after('money_holder')
                ->comment('How the order is delivered');

            $table->string('delivery_provider', 100)->nullable()
                ->after('delivery_method')
                ->comment('Specific provider: courier_id or tryoto/aramex/etc');

            // === Collection Status (for COD) ===
            $table->enum('collection_status', ['not_applicable', 'pending', 'collected', 'failed'])
                ->default('not_applicable')
                ->after('delivery_provider')
                ->comment('COD collection status');

            $table->timestamp('collected_at')->nullable()
                ->after('collection_status')
                ->comment('When COD was collected');

            $table->string('collected_by', 100)->nullable()
                ->after('collected_at')
                ->comment('Who collected COD (courier_id or shipping_company_name)');

            // === Indexes for Reporting ===
            $table->index('money_holder');
            $table->index('collection_status');
            $table->index(['settlement_status', 'money_holder']);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropIndex(['money_holder']);
            $table->dropIndex(['collection_status']);
            $table->dropIndex(['settlement_status', 'money_holder']);

            $table->dropColumn([
                'courier_owes_merchant',
                'courier_owes_platform',
                'shipping_company_owes_merchant',
                'shipping_company_owes_platform',
                'cod_amount',
                'money_holder',
                'delivery_method',
                'delivery_provider',
                'collection_status',
                'collected_at',
                'collected_by',
            ]);
        });
    }
};
