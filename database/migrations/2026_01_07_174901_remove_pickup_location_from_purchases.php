<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove pickup_location from purchases
 *
 * Self-pickup/collection feature is being removed.
 * All deliveries will be:
 * - Local courier (from merchant_locations to customer)
 * - Shipping company (from merchant_locations to customer)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remove pickup_location column from purchases
        if (Schema::hasColumn('purchases', 'pickup_location')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('pickup_location');
            });
        }
    }

    public function down(): void
    {
        // Re-add pickup_location column
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('pickup_location')->nullable()->after('shipping');
        });
    }
};
