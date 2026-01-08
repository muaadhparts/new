<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename legacy column names to match new terminology:
 * - order_* → purchase_*
 * - riders → couriers
 * - admin_commission → operator_commission
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. purchases: order_note → purchase_note, riders → couriers
        Schema::table('purchases', function (Blueprint $table) {
            $table->renameColumn('order_note', 'purchase_note');
            $table->renameColumn('riders', 'couriers');
        });

        // 2. delivery_couriers: order_amount → purchase_amount
        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->renameColumn('order_amount', 'purchase_amount');
        });

        // 3. rewards: order_amount → purchase_amount
        Schema::table('rewards', function (Blueprint $table) {
            $table->renameColumn('order_amount', 'purchase_amount');
        });

        // 4. users: admin_commission → operator_commission
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('admin_commission', 'operator_commission');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->renameColumn('purchase_note', 'order_note');
            $table->renameColumn('couriers', 'riders');
        });

        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->renameColumn('purchase_amount', 'order_amount');
        });

        Schema::table('rewards', function (Blueprint $table) {
            $table->renameColumn('purchase_amount', 'order_amount');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('operator_commission', 'admin_commission');
        });
    }
};
