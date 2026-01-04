<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('pagesettings', 'frontend_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('frontend_settings', 'pagesettings');
    }
};
