<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Update delivery_couriers table for new workflow
 *
 * NEW WORKFLOW:
 * 1. pending_approval - After checkout, waiting for courier approval
 * 2. approved - Courier approved, merchant preparing
 * 3. ready_for_pickup - Merchant prepared, waiting for courier to pick up
 * 4. picked_up - Courier picked up from merchant
 * 5. delivered - Courier delivered to customer
 * 6. confirmed - Customer confirmed receipt (optional)
 * 7. rejected - Courier rejected (needs reassignment)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_couriers', function (Blueprint $table) {
            // Add new timestamp columns for workflow tracking
            $table->timestamp('approved_at')->nullable()->after('delivered_at');
            $table->timestamp('ready_at')->nullable()->after('approved_at');
            $table->timestamp('picked_up_at')->nullable()->after('ready_at');
            $table->timestamp('confirmed_at')->nullable()->after('picked_up_at');

            // Customer confirmation flag (optional confirmation)
            $table->boolean('customer_confirmed')->default(false)->after('confirmed_at');

            // Rejection reason (when courier rejects)
            $table->text('rejection_reason')->nullable()->after('notes');
        });

        // Migrate existing statuses to new workflow
        // pending -> pending_approval
        // ready_for_courier_collection -> approved (since merchant already marked ready)
        // accepted -> picked_up (since courier already accepted)
        // delivered stays as delivered
        // rejected stays as rejected

        DB::statement("UPDATE delivery_couriers SET status = 'pending_approval' WHERE status = 'pending'");
        DB::statement("UPDATE delivery_couriers SET status = 'ready_for_pickup', approved_at = created_at WHERE status = 'ready_for_courier_collection'");
        DB::statement("UPDATE delivery_couriers SET status = 'picked_up', approved_at = created_at WHERE status = 'accepted'");
    }

    public function down(): void
    {
        // Revert status changes
        DB::statement("UPDATE delivery_couriers SET status = 'pending' WHERE status = 'pending_approval'");
        DB::statement("UPDATE delivery_couriers SET status = 'pending' WHERE status = 'approved'");
        DB::statement("UPDATE delivery_couriers SET status = 'ready_for_courier_collection' WHERE status = 'ready_for_pickup'");
        DB::statement("UPDATE delivery_couriers SET status = 'accepted' WHERE status = 'picked_up'");
        DB::statement("UPDATE delivery_couriers SET status = 'delivered' WHERE status = 'confirmed'");

        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'ready_at',
                'picked_up_at',
                'confirmed_at',
                'customer_confirmed',
                'rejection_reason',
            ]);
        });
    }
};
