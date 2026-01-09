<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove unused catalog item types (Digital, License, Listing)
 * Keep only Physical type as all items are Physical
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename licenses table to licenses_old (preserve data)
        if (Schema::hasTable('licenses') && !Schema::hasTable('licenses_old')) {
            Schema::rename('licenses', 'licenses_old');
        }

        // 2. Update catalog_items type enum to only allow 'Physical'
        // First ensure all items are Physical (they already are)
        DB::statement("UPDATE catalog_items SET type = 'Physical' WHERE type != 'Physical'");

        // 3. Modify the enum to only include Physical
        DB::statement("ALTER TABLE catalog_items MODIFY COLUMN type ENUM('Physical') NOT NULL DEFAULT 'Physical'");
    }

    public function down(): void
    {
        // Restore enum with all types
        DB::statement("ALTER TABLE catalog_items MODIFY COLUMN type ENUM('Physical','Digital','License','Listing') NOT NULL DEFAULT 'Physical'");

        // Restore licenses table
        if (Schema::hasTable('licenses_old') && !Schema::hasTable('licenses')) {
            Schema::rename('licenses_old', 'licenses');
        }
    }
};
