<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove breadcrumb_banner column from muaadhsettings
 *
 * The breadcrumb banner image feature is being removed.
 * All breadcrumb sections now use theme colors (theme_breadcrumb_bg) instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'breadcrumb_banner')) {
                $table->dropColumn('breadcrumb_banner');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (!Schema::hasColumn('muaadhsettings', 'breadcrumb_banner')) {
                $table->string('breadcrumb_banner')->nullable()->after('error_banner_500');
            }
        });
    }
};
