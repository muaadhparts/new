<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates admin_roles.section field to replace 'orders' with 'purchases'
     */
    public function up(): void
    {
        // Update all admin_roles records that have 'orders' in section field
        DB::statement("UPDATE admin_roles SET section = REPLACE(section, 'orders', 'purchases') WHERE section LIKE '%orders%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'purchases' back to 'orders'
        DB::statement("UPDATE admin_roles SET section = REPLACE(section, 'purchases', 'orders') WHERE section LIKE '%purchases%'");
    }
};
