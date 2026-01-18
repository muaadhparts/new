<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove color and size variation columns from merchant_items.
 *
 * This project is for auto parts (قطع غيار سيارات) and does not need
 * color or size variations. These columns are legacy from a general
 * e-commerce template.
 *
 * Columns removed:
 * - color_all: comma-separated hex colors
 * - color_price: comma-separated prices per color
 * - colors: legacy color column
 * - size: comma-separated sizes
 * - size_qty: comma-separated quantities per size
 * - size_price: comma-separated prices per size
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Drop color columns
            if (Schema::hasColumn('merchant_items', 'color_all')) {
                $table->dropColumn('color_all');
            }
            if (Schema::hasColumn('merchant_items', 'color_price')) {
                $table->dropColumn('color_price');
            }
            if (Schema::hasColumn('merchant_items', 'colors')) {
                $table->dropColumn('colors');
            }

            // Drop size columns
            if (Schema::hasColumn('merchant_items', 'size')) {
                $table->dropColumn('size');
            }
            if (Schema::hasColumn('merchant_items', 'size_qty')) {
                $table->dropColumn('size_qty');
            }
            if (Schema::hasColumn('merchant_items', 'size_price')) {
                $table->dropColumn('size_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            // Restore color columns
            $table->text('color_all')->nullable();
            $table->text('color_price')->nullable();
            $table->text('colors')->nullable();

            // Restore size columns
            $table->text('size')->nullable();
            $table->text('size_qty')->nullable();
            $table->text('size_price')->nullable();
        });
    }
};
