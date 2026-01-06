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
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->unsignedInteger('city_id')->nullable()->after('location');
            $table->index('city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropColumn('city_id');
        });
    }
};
