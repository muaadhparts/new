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
        if (Schema::hasTable('seller_purchases') && !Schema::hasTable('merchant_purchases')) {
            Schema::rename('seller_purchases', 'merchant_purchases');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('merchant_purchases') && !Schema::hasTable('seller_purchases')) {
            Schema::rename('merchant_purchases', 'seller_purchases');
        }
    }
};
