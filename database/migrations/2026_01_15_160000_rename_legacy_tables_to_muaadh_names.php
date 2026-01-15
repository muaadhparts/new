<?php
/**
 * MUAADH EPC - Multi-Merchant Auto Parts Catalog
 *
 * Migration: Rename Legacy Tables to MUAADH Names
 *
 * This migration renames foundational tables to use MUAADH-specific naming
 * convention, establishing unique identity for intellectual property purposes.
 *
 * @package    MUAADH\Migrations
 * @author     MUAADH Development Team
 * @copyright  2024-2026 MUAADH EPC
 * @license    Proprietary
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table rename mappings: old_name => new_name
     *
     * Complete identity transformation for MUAADH EPC platform.
     * Note: 'languages' table kept as standard Laravel convention.
     *
     * @var array<string, string>
     */
    protected array $tableRenames = [
        'currencies'        => 'monetary_units',
        'email_templates'   => 'comms_blueprints',
        'social_links'      => 'network_presences',
        'socialsettings'    => 'connect_configs',
        'featured_banners'  => 'ad_displays',
        'featured_links'    => 'nav_shortcuts',
        'fonts'             => 'typefaces',
        'services'          => 'capabilities',
        'sliders'           => 'hero_carousels',
        'verifications'     => 'trust_badges',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tableRenames as $oldTable => $newTable) {
            if (Schema::hasTable($oldTable) && !Schema::hasTable($newTable)) {
                Schema::rename($oldTable, $newTable);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the renames
        foreach ($this->tableRenames as $oldTable => $newTable) {
            if (Schema::hasTable($newTable) && !Schema::hasTable($oldTable)) {
                Schema::rename($newTable, $oldTable);
            }
        }
    }
};
