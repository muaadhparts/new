<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create purchases table with same structure as orders
        if (Schema::hasTable('orders') && !Schema::hasTable('purchases')) {
            DB::statement('CREATE TABLE purchases LIKE orders');

            // Copy all data from orders to purchases
            DB::statement('INSERT INTO purchases SELECT * FROM orders');

            // Rename old table
            Schema::rename('orders', 'orders_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders_old') && Schema::hasTable('purchases')) {
            Schema::dropIfExists('purchases');
            Schema::rename('orders_old', 'orders');
        }
    }
};
