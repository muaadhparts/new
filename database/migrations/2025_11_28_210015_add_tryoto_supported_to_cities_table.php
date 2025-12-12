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
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('tryoto_supported')->default(0)->after('longitude');
            $table->index(['country_id', 'tryoto_supported']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['country_id', 'tryoto_supported']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn('tryoto_supported');
        });
    }
};
