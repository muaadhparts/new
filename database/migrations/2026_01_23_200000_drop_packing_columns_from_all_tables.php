<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove Packing/Packaging System Completely
 *
 * The packaging feature has been removed from the project.
 * This migration drops all packing-related columns from all tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop from merchant_purchases
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'packing_cost',
                'platform_packing_fee',
                'packing_owner_id',
            ]);
        });

        // Drop from purchases if exists
        if (Schema::hasColumn('purchases', 'packing_cost')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('packing_cost');
            });
        }

        // Drop from accounting_ledger if exists
        if (Schema::hasColumn('accounting_ledger', 'packing_amount')) {
            Schema::table('accounting_ledger', function (Blueprint $table) {
                $table->dropColumn('packing_amount');
            });
        }
    }

    public function down(): void
    {
        // Packing system removed permanently - no rollback
    }
};
