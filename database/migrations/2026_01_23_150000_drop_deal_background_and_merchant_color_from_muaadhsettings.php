<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove orphaned columns from muaadhsettings
 *
 * - deal_background: Deal feature was removed, this column is orphaned
 * - merchant_color: Theme Builder now handles all colors
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'deal_background')) {
                $table->dropColumn('deal_background');
            }
            if (Schema::hasColumn('muaadhsettings', 'merchant_color')) {
                $table->dropColumn('merchant_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (!Schema::hasColumn('muaadhsettings', 'deal_background')) {
                $table->string('deal_background', 500)->nullable();
            }
            if (!Schema::hasColumn('muaadhsettings', 'merchant_color')) {
                $table->string('merchant_color', 191)->nullable();
            }
        });
    }
};
