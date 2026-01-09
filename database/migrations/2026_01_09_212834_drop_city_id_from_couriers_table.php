<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Drop unused city_id column from couriers table.
 *
 * The courier's service areas are tracked in courier_service_areas table,
 * which has city_id. The city_id in couriers table was intended for
 * courier's "home city" but is not used anywhere in the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Check if foreign key exists and drop it
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'couriers'
            AND COLUMN_NAME = 'city_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($foreignKeys)) {
            Schema::table('couriers', function (Blueprint $table) use ($foreignKeys) {
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // Drop the column
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('photo');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
    }
};
