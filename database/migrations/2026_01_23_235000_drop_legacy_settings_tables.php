<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop Legacy Settings Tables
 *
 * This migration drops old tables that have been replaced by the unified settings system:
 * - platform_settings (replaces: muaadhsettings, seotools, connect_configs)
 * - merchant_settings (for per-merchant config)
 * - pages (replaces: static_content for policy pages only)
 *
 * REMOVED FEATURES (tables to drop):
 * - announcements (feature removed)
 * - testimonials (feature removed)
 * - publications (feature removed)
 * - article_types (feature removed)
 * - help_articles (feature removed)
 * - typefaces (no custom fonts)
 * - static_content (replaced by pages for policies)
 *
 * KEPT TABLES (still used):
 * - muaadhsettings (backward compatibility during transition)
 * - connect_configs (backward compatibility during transition)
 * - languages (still needed for localization)
 * - home_page_themes (still needed)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop removed feature tables
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('article_types');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('typefaces');
        Schema::dropIfExists('static_content');
        Schema::dropIfExists('seotools');
    }

    public function down(): void
    {
        // These tables are intentionally not recreated
        // The features they supported have been removed from the application
        // If needed, restore from database backup
    }
};
