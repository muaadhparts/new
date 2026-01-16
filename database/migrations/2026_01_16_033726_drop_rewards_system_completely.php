<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove entire reward points system
     */
    public function up(): void
    {
        // Drop rewards table
        Schema::dropIfExists('rewards');

        // Remove reward column from users table
        if (Schema::hasColumn('users', 'reward')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('reward');
            });
        }

        // Remove reward settings from muaadhsettings table
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'is_reward')) {
                $table->dropColumn('is_reward');
            }
            if (Schema::hasColumn('muaadhsettings', 'reward_point')) {
                $table->dropColumn('reward_point');
            }
            if (Schema::hasColumn('muaadhsettings', 'reward_dolar')) {
                $table->dropColumn('reward_dolar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate rewards table
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->double('purchase_amount')->default(0);
            $table->double('reward')->default(0);
            $table->decimal('point_value', 10, 2)->default(1.00);
            $table->index('user_id', 'rewards_user_id_index');
        });

        // Add reward column back to users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reward')->default(0);
        });

        // Add reward settings back to muaadhsettings
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->tinyInteger('is_reward')->default(0);
            $table->integer('reward_point')->default(0);
            $table->double('reward_dolar')->default(0);
        });
    }
};
