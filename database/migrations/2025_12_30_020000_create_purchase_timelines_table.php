<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create purchase_timelines table with same structure as order_tracks
        if (Schema::hasTable('order_tracks') && !Schema::hasTable('purchase_timelines')) {
            DB::statement('CREATE TABLE purchase_timelines LIKE order_tracks');

            // Copy all data from order_tracks to purchase_timelines
            DB::statement('INSERT INTO purchase_timelines SELECT * FROM order_tracks');

            // Rename old table
            Schema::rename('order_tracks', 'order_tracks_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_tracks_old') && Schema::hasTable('purchase_timelines')) {
            Schema::dropIfExists('purchase_timelines');
            Schema::rename('order_tracks_old', 'order_tracks');
        }
    }
};
