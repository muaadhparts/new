<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add latitude/longitude columns for coordinate-based courier search
 *
 * Purpose:
 * - Enable fallback search when city name matching fails
 * - Find couriers within X km radius using Haversine formula
 * - Similar to Uber Eats, DoorDash, Deliveroo approach
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add coordinates to pickup_points (merchant warehouses)
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('city_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('service_radius_km')->default(20)->after('longitude')
                  ->comment('Service radius in kilometers');
        });

        // Add coordinates to courier_service_areas
        Schema::table('courier_service_areas', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('city_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('service_radius_km')->default(20)->after('longitude')
                  ->comment('Service radius in kilometers');
        });

        // Add index for faster coordinate queries
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'pickup_points_coordinates_index');
        });

        Schema::table('courier_service_areas', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'courier_service_areas_coordinates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->dropIndex('pickup_points_coordinates_index');
            $table->dropColumn(['latitude', 'longitude', 'service_radius_km']);
        });

        Schema::table('courier_service_areas', function (Blueprint $table) {
            $table->dropIndex('courier_service_areas_coordinates_index');
            $table->dropColumn(['latitude', 'longitude', 'service_radius_km']);
        });
    }
};
