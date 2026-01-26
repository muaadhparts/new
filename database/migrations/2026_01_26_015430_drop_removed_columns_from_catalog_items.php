<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop removed columns from catalog_items table.
 *
 * These columns are architecturally incompatible with the project:
 * - tags, is_meta, meta_tag, meta_description: SEO fields removed
 * - youtube: Video URL field removed
 * - measure: Measurement unit field removed
 * - hot, latest, sale: Classification flags removed
 * - cross_items: Cross-sell feature removed
 *
 * @see CLAUDE.md - Removed Features section
 */
return new class extends Migration
{
    /**
     * Columns to be dropped.
     */
    private array $columns = [
        'tags',
        'is_meta',
        'meta_tag',
        'meta_description',
        'youtube',
        'measure',
        'hot',
        'latest',
        'sale',
        'cross_items',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('catalog_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     * NOTE: These columns should NOT be restored. This down() is for rollback safety only.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_items', 'tags')) {
                $table->string('tags', 191)->nullable();
            }
            if (!Schema::hasColumn('catalog_items', 'is_meta')) {
                $table->tinyInteger('is_meta')->default(0);
            }
            if (!Schema::hasColumn('catalog_items', 'meta_tag')) {
                $table->text('meta_tag')->nullable();
            }
            if (!Schema::hasColumn('catalog_items', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (!Schema::hasColumn('catalog_items', 'youtube')) {
                $table->string('youtube', 191)->nullable();
            }
            if (!Schema::hasColumn('catalog_items', 'measure')) {
                $table->string('measure', 191)->nullable();
            }
            if (!Schema::hasColumn('catalog_items', 'hot')) {
                $table->unsignedTinyInteger('hot')->default(0);
            }
            if (!Schema::hasColumn('catalog_items', 'latest')) {
                $table->unsignedTinyInteger('latest')->default(0);
            }
            if (!Schema::hasColumn('catalog_items', 'sale')) {
                $table->tinyInteger('sale')->default(0);
            }
            if (!Schema::hasColumn('catalog_items', 'cross_items')) {
                $table->string('cross_items', 255)->nullable();
            }
        });
    }
};
