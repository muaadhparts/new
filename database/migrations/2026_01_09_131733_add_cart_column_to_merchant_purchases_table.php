<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add cart column to merchant_purchases for storing merchant-specific cart items
 * Also add created_at to delivery_couriers if missing
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add cart column to merchant_purchases
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_purchases', 'cart')) {
                $table->json('cart')->nullable()->after('purchase_id');
            }
        });

        // Add timestamps to delivery_couriers if missing
        Schema::table('delivery_couriers', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_couriers', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('delivery_couriers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_purchases', 'cart')) {
                $table->dropColumn('cart');
            }
        });

        Schema::table('delivery_couriers', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_couriers', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('delivery_couriers', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
