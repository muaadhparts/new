<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy operator_commission column from users table.
 * Commission is now handled per-merchant in merchant_commissions table
 * and calculated per-purchase in merchant_purchases.commission_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'operator_commission')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('operator_commission');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('operator_commission', 10, 2)->default(0);
        });
    }
};
