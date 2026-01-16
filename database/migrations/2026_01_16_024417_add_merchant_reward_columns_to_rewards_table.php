<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds merchant-specific reward configuration:
     * - user_id: 0 = platform default, >0 = merchant-specific rules
     * - point_value: How much 1 point is worth in currency (e.g., 1.00 SAR)
     */
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            // user_id: 0 = operator/platform default, >0 = merchant-specific
            $table->unsignedBigInteger('user_id')->default(0)->after('id');

            // Value of 1 point in currency (e.g., 1.00 = 1 point = 1 SAR)
            $table->decimal('point_value', 10, 2)->default(1.00)->after('reward');

            // Index for efficient merchant lookups
            $table->index('user_id', 'rewards_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropIndex('rewards_user_id_index');
            $table->dropColumn(['user_id', 'point_value']);
        });
    }
};
