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
        // Create muaadhsettings table with same structure as generalsettings
        if (Schema::hasTable('generalsettings') && !Schema::hasTable('muaadhsettings')) {
            DB::statement('CREATE TABLE muaadhsettings LIKE generalsettings');

            // Copy all data
            DB::statement('INSERT INTO muaadhsettings SELECT * FROM generalsettings');

            // Rename old table
            Schema::rename('generalsettings', 'generalsettings_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('generalsettings_old') && Schema::hasTable('muaadhsettings')) {
            Schema::dropIfExists('muaadhsettings');
            Schema::rename('generalsettings_old', 'generalsettings');
        }
    }
};
