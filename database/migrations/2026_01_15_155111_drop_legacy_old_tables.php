<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Drop Legacy _old Tables
 *
 * Purpose: Remove deprecated tables that were renamed with _old suffix
 * during the transition to new naming conventions.
 *
 * Tables being dropped:
 * - membership_plans_old (legacy subscription system)
 * - user_membership_plans_old (legacy user subscriptions)
 *
 * Note: These tables were preserved during the initial rename for safety.
 * Now that the new system is stable, they can be permanently removed.
 *
 * IMPORTANT: Ensure you have a database backup before running this migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop legacy membership tables
        // These were renamed to _old in migration 2026_01_05_210652
        Schema::dropIfExists('membership_plans_old');
        Schema::dropIfExists('user_membership_plans_old');

        // Note: licenses_old was already dropped in 2026_01_08_182318
        // Note: shipment_status_logs_old was already dropped in 2026_01_12_210000
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This is a destructive operation. The data cannot be recovered
     * once the tables are dropped. This down() method only recreates empty tables.
     */
    public function down(): void
    {
        // Recreate empty membership_plans_old table (structure only, no data)
        if (!Schema::hasTable('membership_plans_old')) {
            Schema::create('membership_plans_old', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->integer('duration')->default(30);
                $table->integer('catalog_item_limit')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Recreate empty user_membership_plans_old table (structure only, no data)
        if (!Schema::hasTable('user_membership_plans_old')) {
            Schema::create('user_membership_plans_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('membership_plan_id')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }
};
