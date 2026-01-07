<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename pickup_points to merchant_locations
 *
 * Conceptual clarification:
 * - merchant_locations = Merchant warehouse/origin locations (for shipping)
 * - pickup (in checkout) = Customer pickup from store option
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename the main table
        Schema::rename('pickup_points', 'merchant_locations');

        // 2. Rename foreign key column in delivery_couriers
        if (Schema::hasColumn('delivery_couriers', 'pickup_point_id')) {
            Schema::table('delivery_couriers', function (Blueprint $table) {
                $table->renameColumn('pickup_point_id', 'merchant_location_id');
            });
        }

        // 3. Rename foreign key column in merchant_purchases (if exists)
        if (Schema::hasColumn('merchant_purchases', 'pickup_point_id')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('pickup_point_id', 'merchant_location_id');
            });
        }
    }

    public function down(): void
    {
        // Reverse: Rename back to pickup_points
        Schema::rename('merchant_locations', 'pickup_points');

        if (Schema::hasColumn('delivery_couriers', 'merchant_location_id')) {
            Schema::table('delivery_couriers', function (Blueprint $table) {
                $table->renameColumn('merchant_location_id', 'pickup_point_id');
            });
        }

        if (Schema::hasColumn('merchant_purchases', 'merchant_location_id')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('merchant_location_id', 'pickup_point_id');
            });
        }
    }
};
