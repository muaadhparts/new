<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename tables to _old suffix for safety (following project safety rules)
        if (Schema::hasTable('wallet_logs') && !Schema::hasTable('wallet_logs_old')) {
            Schema::rename('wallet_logs', 'wallet_logs_old');
        }

        if (Schema::hasTable('top_ups') && !Schema::hasTable('top_ups_old')) {
            Schema::rename('top_ups', 'top_ups_old');
        }

        // Remove current_balance column from users table
        if (Schema::hasColumn('users', 'current_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('current_balance');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore tables from _old suffix
        if (Schema::hasTable('wallet_logs_old') && !Schema::hasTable('wallet_logs')) {
            Schema::rename('wallet_logs_old', 'wallet_logs');
        }

        if (Schema::hasTable('top_ups_old') && !Schema::hasTable('top_ups')) {
            Schema::rename('top_ups_old', 'top_ups');
        }

        // Restore current_balance column to users table
        if (!Schema::hasColumn('users', 'current_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('reward');
            });
        }
    }
};
