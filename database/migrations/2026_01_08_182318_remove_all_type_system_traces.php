<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove all traces of the multi-type system (Digital/License/Listing).
 * The system is now Physical-only by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove type-related columns from catalog_items
        Schema::table('catalog_items', function (Blueprint $table) {
            // type column - was enum for Digital/License/Listing/Physical
            if (Schema::hasColumn('catalog_items', 'type')) {
                $table->dropColumn('type');
            }
            // file - was for Digital products (downloadable file)
            if (Schema::hasColumn('catalog_items', 'file')) {
                $table->dropColumn('file');
            }
            // link - was for Digital products (external download link)
            if (Schema::hasColumn('catalog_items', 'link')) {
                $table->dropColumn('link');
            }
            // platform - was for License products
            if (Schema::hasColumn('catalog_items', 'platform')) {
                $table->dropColumn('platform');
            }
            // region - was for License products
            if (Schema::hasColumn('catalog_items', 'region')) {
                $table->dropColumn('region');
            }
        });

        // 2. Remove license-related columns from merchant_items
        Schema::table('merchant_items', function (Blueprint $table) {
            // licence_type - was for License products
            if (Schema::hasColumn('merchant_items', 'licence_type')) {
                $table->dropColumn('licence_type');
            }
            // license - was for License keys
            if (Schema::hasColumn('merchant_items', 'license')) {
                $table->dropColumn('license');
            }
            // license_qty - was for License quantity
            if (Schema::hasColumn('merchant_items', 'license_qty')) {
                $table->dropColumn('license_qty');
            }
        });

        // 3. Drop licenses_old table permanently
        Schema::dropIfExists('licenses_old');
        Schema::dropIfExists('licenses');
    }

    public function down(): void
    {
        // This migration is irreversible by design.
        // The system should not support multi-type products.
    }
};
