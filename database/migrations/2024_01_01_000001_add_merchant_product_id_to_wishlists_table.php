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
        Schema::table('wishlists', function (Blueprint $table) {
            $table->unsignedBigInteger('merchant_product_id')->nullable()->after('product_id');
            $table->index(['user_id', 'merchant_product_id']);

            // Add foreign key constraint
            $table->foreign('merchant_product_id')->references('id')->on('merchant_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropForeign(['merchant_product_id']);
            $table->dropIndex(['user_id', 'merchant_product_id']);
            $table->dropColumn('merchant_product_id');
        });
    }
};