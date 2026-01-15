<?php
/**
 * MUAADH EPC - Multi-Merchant Auto Parts Catalog
 *
 * Migration: Rename Home Page Theme columns to match new table names
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Column rename mappings: old_name => new_name
     */
    protected array $columnRenames = [
        'show_slider' => 'show_hero_carousel',
        'order_slider' => 'order_hero_carousel',
        'show_services' => 'show_capabilities',
        'order_services' => 'order_capabilities',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('home_page_themes', function (Blueprint $table) {
            foreach ($this->columnRenames as $oldColumn => $newColumn) {
                if (Schema::hasColumn('home_page_themes', $oldColumn) && !Schema::hasColumn('home_page_themes', $newColumn)) {
                    $table->renameColumn($oldColumn, $newColumn);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_page_themes', function (Blueprint $table) {
            foreach ($this->columnRenames as $oldColumn => $newColumn) {
                if (Schema::hasColumn('home_page_themes', $newColumn) && !Schema::hasColumn('home_page_themes', $oldColumn)) {
                    $table->renameColumn($newColumn, $oldColumn);
                }
            }
        });
    }
};
