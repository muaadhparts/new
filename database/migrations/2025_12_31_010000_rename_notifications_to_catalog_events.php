<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to rename notification tables to catalog_events:
 * - notifications → catalog_events
 * - user_notifications → user_catalog_events
 *
 * Also renames order_number → purchase_number in user_catalog_events
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename notifications → catalog_events
        if (Schema::hasTable('notifications') && !Schema::hasTable('catalog_events')) {
            Schema::rename('notifications', 'catalog_events');
        }

        // 2. Rename user_notifications → user_catalog_events
        if (Schema::hasTable('user_notifications') && !Schema::hasTable('user_catalog_events')) {
            Schema::rename('user_notifications', 'user_catalog_events');
        }

        // 3. Rename order_number → purchase_number in user_catalog_events
        if (Schema::hasTable('user_catalog_events') && Schema::hasColumn('user_catalog_events', 'order_number')) {
            Schema::table('user_catalog_events', function (Blueprint $table) {
                $table->renameColumn('order_number', 'purchase_number');
            });
        }

        // 4. Add catalog_item_id to catalog_events if missing
        if (Schema::hasTable('catalog_events') && !Schema::hasColumn('catalog_events', 'catalog_item_id')) {
            Schema::table('catalog_events', function (Blueprint $table) {
                $table->integer('catalog_item_id')->nullable()->after('merchant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rename purchase_number → order_number in user_catalog_events
        if (Schema::hasTable('user_catalog_events') && Schema::hasColumn('user_catalog_events', 'purchase_number')) {
            Schema::table('user_catalog_events', function (Blueprint $table) {
                $table->renameColumn('purchase_number', 'order_number');
            });
        }

        // 2. Rename user_catalog_events → user_notifications
        if (Schema::hasTable('user_catalog_events') && !Schema::hasTable('user_notifications')) {
            Schema::rename('user_catalog_events', 'user_notifications');
        }

        // 3. Rename catalog_events → notifications
        if (Schema::hasTable('catalog_events') && !Schema::hasTable('notifications')) {
            Schema::rename('catalog_events', 'notifications');
        }
    }
};
