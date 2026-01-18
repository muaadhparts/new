<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove dimension columns from catalog_items.
 *
 * Shipping now uses weight only (via TryotoService).
 * These columns are no longer needed.
 *
 * Columns removed:
 * - length: Product length (not used)
 * - height: Product height (not used)
 * - width: Product width (not used)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'length')) {
                $table->dropColumn('length');
            }
            if (Schema::hasColumn('catalog_items', 'height')) {
                $table->dropColumn('height');
            }
            if (Schema::hasColumn('catalog_items', 'width')) {
                $table->dropColumn('width');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->varchar('length', 191)->nullable();
            $table->varchar('height', 191)->nullable();
            $table->decimal('width', 10, 2)->nullable()->comment('Product width in cm');
        });
    }
};
