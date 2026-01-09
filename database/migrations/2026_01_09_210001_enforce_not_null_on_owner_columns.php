<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Enforce NOT NULL on Owner Columns
 *
 * ARCHITECTURAL PRINCIPLE:
 * - owner_id = 0 → Platform service (NEVER NULL)
 * - owner_id > 0 → Merchant service
 *
 * This migration:
 * 1. Converts all NULL values to 0 (platform)
 * 2. Adds NOT NULL constraints to prevent future NULL values
 * 3. Ensures data integrity from this point forward
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Clean NULL values - convert to 0 (platform)
        DB::statement("UPDATE merchant_purchases SET payment_owner_id = 0 WHERE payment_owner_id IS NULL");
        DB::statement("UPDATE merchant_purchases SET shipping_owner_id = 0 WHERE shipping_owner_id IS NULL");
        DB::statement("UPDATE merchant_purchases SET packing_owner_id = 0 WHERE packing_owner_id IS NULL");

        // Step 2: Modify columns to NOT NULL with default 0
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_owner_id')->default(0)->nullable(false)->change();
            $table->unsignedBigInteger('shipping_owner_id')->default(0)->nullable(false)->change();
            $table->unsignedBigInteger('packing_owner_id')->default(0)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Revert to nullable (not recommended)
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_owner_id')->default(0)->nullable()->change();
            $table->unsignedBigInteger('shipping_owner_id')->default(0)->nullable()->change();
            $table->unsignedBigInteger('packing_owner_id')->default(0)->nullable()->change();
        });
    }
};
