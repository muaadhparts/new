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
        Schema::table('generalsettings', function (Blueprint $table) {
            // Primary Colors
            $table->string('theme_primary', 20)->default('#c3002f')->after('colors');
            $table->string('theme_primary_hover', 20)->default('#a00025')->after('theme_primary');
            $table->string('theme_primary_dark', 20)->default('#8a0020')->after('theme_primary_hover');
            $table->string('theme_primary_light', 20)->default('#fef2f4')->after('theme_primary_dark');

            // Secondary Colors
            $table->string('theme_secondary', 20)->default('#1a1a1a')->after('theme_primary_light');
            $table->string('theme_secondary_hover', 20)->default('#333333')->after('theme_secondary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generalsettings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_primary',
                'theme_primary_hover',
                'theme_primary_dark',
                'theme_primary_light',
                'theme_secondary',
                'theme_secondary_hover'
            ]);
        });
    }
};
