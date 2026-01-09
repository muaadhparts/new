<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Update Existing MerchantPurchase Financial Balances
 *
 * This migration recalculates financial balances for existing records
 * based on the new owner-based architecture:
 * - payment_owner_id = 0 → Platform received money → platform_owes_merchant = net_amount
 * - payment_owner_id ≠ 0 → Merchant received money → merchant_owes_platform = commission + tax
 */
return new class extends Migration
{
    public function up(): void
    {
        // For existing records where payment_owner_id = 0 (platform payment - default)
        // Platform owes merchant the net amount
        DB::statement("
            UPDATE merchant_purchases
            SET platform_owes_merchant = net_amount,
                merchant_owes_platform = 0
            WHERE payment_owner_id = 0 OR payment_owner_id IS NULL
        ");

        // For records where payment_owner_id != 0 (merchant payment)
        // Merchant owes platform the commission + tax + platform services
        DB::statement("
            UPDATE merchant_purchases
            SET merchant_owes_platform = commission_amount + tax_amount + COALESCE(platform_shipping_fee, 0) + COALESCE(platform_packing_fee, 0),
                platform_owes_merchant = 0
            WHERE payment_owner_id != 0 AND payment_owner_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Reset all financial balances to zero
        DB::statement("
            UPDATE merchant_purchases
            SET platform_owes_merchant = 0,
                merchant_owes_platform = 0
        ");
    }
};
