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
        Schema::table('frontend_settings', function (Blueprint $table) {
            $table->renameColumn('arrival_section', 'featured_promo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frontend_settings', function (Blueprint $table) {
            $table->renameColumn('featured_promo', 'arrival_section');
        });
    }
};
