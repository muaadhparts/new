<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename 'name' column to 'name' across multiple tables
 * Part of IP protection and naming standardization
 *
 * Tables affected:
 * - abuse_flags
 * - announcements
 * - featured_promos
 * - help_articles
 * - packages
 * - publications
 * - purchase_timelines
 * - shippings
 * - static_contents
 * - testimonials
 * - muaadhsettings (site_name → site_name)
 *
 * SKIPPED: merchant_payments (already has both 'name' and 'name' columns)
 */
return new class extends Migration
{
    /**
     * Tables that have 'name' column to rename to 'name'
     */
    protected array $tablesToRename = [
        'abuse_flags',
        'ad_displays',
        'announcements',
        'capabilities',
        'featured_promos',
        'help_articles',
        'packages',
        'publications',
        'purchase_timelines',
        'shippings',
        'static_contents',
        'testimonials',
    ];

    public function up(): void
    {
        // Rename 'name' to 'name' in standard tables
        foreach ($this->tablesToRename as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'name')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->renameColumn('name', 'name');
                });
            }
        }

        // Special case: muaadhsettings - rename 'name' to 'site_name'
        if (Schema::hasTable('muaadhsettings') && Schema::hasColumn('muaadhsettings', 'name')) {
            Schema::table('muaadhsettings', function (Blueprint $table) {
                $table->renameColumn('name', 'site_name');
            });
        }
    }

    public function down(): void
    {
        // Reverse: rename 'name' back to 'name'
        foreach ($this->tablesToRename as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'name')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->renameColumn('name', 'name');
                });
            }
        }

        // Special case: muaadhsettings
        if (Schema::hasTable('muaadhsettings') && Schema::hasColumn('muaadhsettings', 'site_name')) {
            Schema::table('muaadhsettings', function (Blueprint $table) {
                $table->renameColumn('site_name', 'name');
            });
        }
    }
};
