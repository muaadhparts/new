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
        // Update existing wishlist entries with merchant_product_id
        // For each wishlist entry without merchant_product_id, find the first active merchant product
        DB::statement('
            UPDATE wishlists w
            SET merchant_product_id = (
                SELECT mp.id
                FROM merchant_products mp
                WHERE mp.product_id = w.product_id
                AND mp.status = 1
                ORDER BY mp.price ASC
                LIMIT 1
            )
            WHERE w.merchant_product_id IS NULL
            AND w.product_id IS NOT NULL
        ');

        // Clean up wishlist entries that have no corresponding merchant products
        DB::statement('
            DELETE FROM wishlists
            WHERE merchant_product_id IS NULL
            AND product_id NOT IN (
                SELECT DISTINCT product_id
                FROM merchant_products
                WHERE status = 1
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all merchant_product_id back to null
        DB::table('wishlists')->update(['merchant_product_id' => null]);
    }
};