<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename subscriptions to membership_plans
        Schema::rename('subscriptions', 'membership_plans');

        // Rename user_subscriptions to user_membership_plans
        Schema::rename('user_subscriptions', 'user_membership_plans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('membership_plans', 'subscriptions');
        Schema::rename('user_membership_plans', 'user_subscriptions');
    }
};
