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
        if (Schema::hasTable('vendor_stock_updates') && !Schema::hasTable('merchant_stock_updates')) {
            Schema::rename('vendor_stock_updates', 'merchant_stock_updates');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('merchant_stock_updates') && !Schema::hasTable('vendor_stock_updates')) {
            Schema::rename('merchant_stock_updates', 'vendor_stock_updates');
        }
    }
};
