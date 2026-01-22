<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove multiple_shipping column
 *
 * The new shipping policy is:
 * - Each merchant/branch has its own shipping
 * - Orders are separated by branch
 * - No more "single shipping for entire order" option
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'multiple_shipping')) {
                $table->dropColumn('multiple_shipping');
            }
        });
    }

    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (!Schema::hasColumn('muaadhsettings', 'multiple_shipping')) {
                $table->boolean('multiple_shipping')->default(1)->after('affilate_banner');
            }
        });
    }
};
