<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merchant Settlement System
 *
 * This creates a complete merchant settlement tracking system.
 * Source of truth: MerchantPurchase table
 *
 * Money Flow:
 * - Platform collects payment (online or COD via courier/shipping)
 * - Platform deducts commission
 * - Platform pays merchant the net_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        // Main merchant settlements table
        Schema::create('merchant_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Merchant
            $table->string('settlement_number')->unique(); // MS-YYYYMMDD-XXXX

            // Period covered
            $table->date('period_start');
            $table->date('period_end');

            // Financial summary (all in base currency SAR)
            $table->decimal('total_sales', 12, 2)->default(0); // Sum of MerchantPurchase.price
            $table->decimal('total_commission', 12, 2)->default(0); // Sum of MerchantPurchase.commission_amount
            $table->decimal('total_tax', 12, 2)->default(0); // Sum of MerchantPurchase.tax_amount (collected by platform)
            $table->decimal('total_shipping', 12, 2)->default(0); // Sum of shipping costs
            $table->decimal('total_packing', 12, 2)->default(0); // Sum of packing costs
            $table->decimal('total_deductions', 12, 2)->default(0); // Any other deductions
            $table->decimal('net_payable', 12, 2)->default(0); // Final amount to pay merchant

            // Orders summary
            $table->integer('orders_count')->default(0);
            $table->integer('items_count')->default(0);

            // Status workflow
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');

            // Payment info (when paid)
            $table->string('payment_method')->nullable(); // bank_transfer, wallet, cash, etc.
            $table->string('payment_reference')->nullable(); // Transaction ID
            $table->timestamp('payment_date')->nullable();

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Note: Foreign key constraints removed for compatibility
            // Relationship enforced at application level
            $table->index('user_id');
            $table->index(['user_id', 'status']);
            $table->index('period_start');
            $table->index('period_end');
        });

        // Settlement line items (linked to MerchantPurchase)
        Schema::create('merchant_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_settlement_id');
            $table->unsignedBigInteger('merchant_purchase_id');

            // Snapshot of amounts at settlement time
            $table->decimal('sale_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->timestamps();

            // Note: Foreign key constraints removed for compatibility
            $table->index('merchant_settlement_id');
            $table->index('merchant_purchase_id');
            $table->unique(['merchant_settlement_id', 'merchant_purchase_id'], 'ms_mp_unique');
        });

        // Add settlement tracking to merchant_purchases
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_purchases', 'settlement_status')) {
                $table->enum('settlement_status', ['unsettled', 'pending', 'settled'])->default('unsettled')->after('status');
            }
            if (!Schema::hasColumn('merchant_purchases', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('settlement_status');
            }
            if (!Schema::hasColumn('merchant_purchases', 'merchant_settlement_id')) {
                $table->unsignedBigInteger('merchant_settlement_id')->nullable()->after('settled_at');
            }
        });

        // Platform revenue tracking (aggregated from all sources)
        Schema::create('platform_revenue_log', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('source', ['commission', 'tax', 'shipping_markup', 'courier_fee', 'other']);
            $table->string('reference_type')->nullable(); // MerchantSettlement, CourierSettlement, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropColumn(['settlement_status', 'settled_at', 'merchant_settlement_id']);
        });

        Schema::dropIfExists('platform_revenue_log');
        Schema::dropIfExists('merchant_settlement_items');
        Schema::dropIfExists('merchant_settlements');
    }
};
