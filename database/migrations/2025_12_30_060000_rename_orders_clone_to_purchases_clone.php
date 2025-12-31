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
        if (Schema::hasTable('orders_clone') && !Schema::hasTable('purchases_clone')) {
            Schema::rename('orders_clone', 'purchases_clone');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases_clone') && !Schema::hasTable('orders_clone')) {
            Schema::rename('purchases_clone', 'orders_clone');
        }
    }
};
