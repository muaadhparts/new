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
        Schema::rename('payment_gateways', 'merchant_payments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('merchant_payments', 'payment_gateways');
    }
};
