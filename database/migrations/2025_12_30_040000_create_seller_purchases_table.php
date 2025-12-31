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
        // Create seller_purchases table with same structure as vendor_orders
        if (Schema::hasTable('vendor_orders') && !Schema::hasTable('seller_purchases')) {
            DB::statement('CREATE TABLE seller_purchases LIKE vendor_orders');

            // Copy all data from vendor_orders to seller_purchases
            DB::statement('INSERT INTO seller_purchases SELECT * FROM vendor_orders');

            // Rename old table
            Schema::rename('vendor_orders', 'vendor_orders_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vendor_orders_old') && Schema::hasTable('seller_purchases')) {
            Schema::dropIfExists('seller_purchases');
            Schema::rename('vendor_orders_old', 'vendor_orders');
        }
    }
};
