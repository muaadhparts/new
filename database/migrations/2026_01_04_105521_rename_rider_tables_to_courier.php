<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename riders table to couriers
        Schema::rename('riders', 'couriers');

        // Rename rider_service_areas table to courier_service_areas
        Schema::rename('rider_service_areas', 'courier_service_areas');

        // Rename rider_id column in courier_service_areas table
        Schema::table('courier_service_areas', function (Blueprint $table) {
            $table->renameColumn('rider_id', 'courier_id');
        });

        // Rename delivery_riders table to delivery_couriers
        Schema::rename('delivery_riders', 'delivery_couriers');

        // Rename rider_id column in delivery_couriers table
        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->renameColumn('rider_id', 'courier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back delivery_couriers
        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->renameColumn('courier_id', 'rider_id');
        });
        Schema::rename('delivery_couriers', 'delivery_riders');

        // Rename back courier_service_areas
        Schema::table('courier_service_areas', function (Blueprint $table) {
            $table->renameColumn('courier_id', 'rider_id');
        });
        Schema::rename('courier_service_areas', 'rider_service_areas');

        // Rename back couriers
        Schema::rename('couriers', 'riders');
    }
};
