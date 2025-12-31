<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to rename order-related columns to purchase-related columns
 *
 * This migration renames:
 * - order_number -> purchase_number (in purchases, merchant_purchases, support_threads)
 * - order_id -> purchase_id (in purchase_timelines, merchant_purchases, notifications, shipment_status_logs, delivery_riders)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename order_number to purchase_number in purchases table
        if (Schema::hasColumn('purchases', 'order_number') && !Schema::hasColumn('purchases', 'purchase_number')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->renameColumn('order_number', 'purchase_number');
            });
        }

        // 2. Rename columns in purchase_timelines table
        if (Schema::hasColumn('purchase_timelines', 'order_id') && !Schema::hasColumn('purchase_timelines', 'purchase_id')) {
            Schema::table('purchase_timelines', function (Blueprint $table) {
                $table->renameColumn('order_id', 'purchase_id');
            });
        }

        // 3. Rename columns in merchant_purchases table
        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_purchases', 'order_id') && !Schema::hasColumn('merchant_purchases', 'purchase_id')) {
                $table->renameColumn('order_id', 'purchase_id');
            }
        });

        Schema::table('merchant_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_purchases', 'order_number') && !Schema::hasColumn('merchant_purchases', 'purchase_number')) {
                $table->renameColumn('order_number', 'purchase_number');
            }
        });

        // 4. Rename order_id to purchase_id in notifications table
        if (Schema::hasColumn('notifications', 'order_id') && !Schema::hasColumn('notifications', 'purchase_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->renameColumn('order_id', 'purchase_id');
            });
        }

        // 5. Rename order_id to purchase_id in shipment_status_logs table
        if (Schema::hasColumn('shipment_status_logs', 'order_id') && !Schema::hasColumn('shipment_status_logs', 'purchase_id')) {
            Schema::table('shipment_status_logs', function (Blueprint $table) {
                $table->renameColumn('order_id', 'purchase_id');
            });
        }

        // 6. Rename order_id to purchase_id in delivery_riders table
        if (Schema::hasColumn('delivery_riders', 'order_id') && !Schema::hasColumn('delivery_riders', 'purchase_id')) {
            Schema::table('delivery_riders', function (Blueprint $table) {
                $table->renameColumn('order_id', 'purchase_id');
            });
        }

        // 7. Rename order_number to purchase_number in support_threads table
        if (Schema::hasColumn('support_threads', 'order_number') && !Schema::hasColumn('support_threads', 'purchase_number')) {
            Schema::table('support_threads', function (Blueprint $table) {
                $table->renameColumn('order_number', 'purchase_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse all renames

        // 1. purchases table
        if (Schema::hasColumn('purchases', 'purchase_number')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->renameColumn('purchase_number', 'order_number');
            });
        }

        // 2. purchase_timelines table
        if (Schema::hasColumn('purchase_timelines', 'purchase_id')) {
            Schema::table('purchase_timelines', function (Blueprint $table) {
                $table->renameColumn('purchase_id', 'order_id');
            });
        }

        // 3. merchant_purchases table
        if (Schema::hasColumn('merchant_purchases', 'purchase_id')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('purchase_id', 'order_id');
            });
        }
        if (Schema::hasColumn('merchant_purchases', 'purchase_number')) {
            Schema::table('merchant_purchases', function (Blueprint $table) {
                $table->renameColumn('purchase_number', 'order_number');
            });
        }

        // 4. notifications table
        if (Schema::hasColumn('notifications', 'purchase_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->renameColumn('purchase_id', 'order_id');
            });
        }

        // 5. shipment_status_logs table
        if (Schema::hasColumn('shipment_status_logs', 'purchase_id')) {
            Schema::table('shipment_status_logs', function (Blueprint $table) {
                $table->renameColumn('purchase_id', 'order_id');
            });
        }

        // 6. delivery_riders table
        if (Schema::hasColumn('delivery_riders', 'purchase_id')) {
            Schema::table('delivery_riders', function (Blueprint $table) {
                $table->renameColumn('purchase_id', 'order_id');
            });
        }

        // 7. support_threads table
        if (Schema::hasColumn('support_threads', 'purchase_number')) {
            Schema::table('support_threads', function (Blueprint $table) {
                $table->renameColumn('purchase_number', 'order_number');
            });
        }
    }
};
