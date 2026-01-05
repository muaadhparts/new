<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renames membership tables to _old suffix for safety (data preservation).
     */
    public function up(): void
    {
        // Rename membership_plans to membership_plans_old
        if (Schema::hasTable('membership_plans') && !Schema::hasTable('membership_plans_old')) {
            Schema::rename('membership_plans', 'membership_plans_old');
        }

        // Rename user_membership_plans to user_membership_plans_old
        if (Schema::hasTable('user_membership_plans') && !Schema::hasTable('user_membership_plans_old')) {
            Schema::rename('user_membership_plans', 'user_membership_plans_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back if needed
        if (Schema::hasTable('membership_plans_old') && !Schema::hasTable('membership_plans')) {
            Schema::rename('membership_plans_old', 'membership_plans');
        }

        if (Schema::hasTable('user_membership_plans_old') && !Schema::hasTable('user_membership_plans')) {
            Schema::rename('user_membership_plans_old', 'user_membership_plans');
        }
    }
};
