<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Rename merchant_locations to merchant_branches
 *
 * This migration transforms the location-based system to a branch-based system.
 * Each merchant branch is now a complete operational entity.
 *
 * Changes:
 * 1. Rename table: merchant_locations -> merchant_branches
 * 2. Rename columns in related tables:
 *    - delivery_couriers: merchant_location_id -> merchant_branch_id
 *    - merchant_purchases: merchant_location_id -> merchant_branch_id (if exists)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Rename the main table
        Schema::rename('merchant_locations', 'merchant_branches');

        // Step 2: Rename column in delivery_couriers table
        if (Schema::hasColumn('delivery_couriers', 'merchant_location_id')) {
            Schema::table('delivery_couriers', function (Blueprint $table) {
                $table->renameColumn('merchant_location_id', 'merchant_branch_id');
            });
        }

        // Step 3: Rename column in merchant_purchases table (if exists)
        if (Schema::hasColumn('merchant_purchases', 'merchant_location_id')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('merchant_location_id', 'merchant_branch_id');
            });
        }

        // Step 4: Rename any indexes that reference the old name
        // Note: MySQL will automatically update foreign key constraints when renaming tables
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Rename columns back in related tables
        if (Schema::hasColumn('delivery_couriers', 'merchant_branch_id')) {
            Schema::table('delivery_couriers', function (Blueprint $table) {
                $table->renameColumn('merchant_branch_id', 'merchant_location_id');
            });
        }

        if (Schema::hasColumn('merchant_purchases', 'merchant_branch_id')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('merchant_branch_id', 'merchant_location_id');
            });
        }

        // Step 2: Rename the main table back
        Schema::rename('merchant_branches', 'merchant_locations');
    }
};
