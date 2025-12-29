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
        // Step 1: Create favorites table
        Schema::create('favorites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('merchant_product_id')->unsigned()->nullable();
        });

        // Step 2: Migrate data
        DB::statement('
            INSERT INTO favorites (id, user_id, product_id, merchant_product_id)
            SELECT id, user_id, product_id, merchant_product_id
            FROM wishlists
        ');

        // Step 3: Rename old table with _old suffix
        Schema::rename('wishlists', 'wishlists_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('wishlists_old', 'wishlists');
        Schema::dropIfExists('favorites');
    }
};
