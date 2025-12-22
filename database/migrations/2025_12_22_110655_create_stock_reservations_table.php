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
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_product_id')->index();
            $table->string('size', 50)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->string('cart_key', 255)->index();
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('merchant_product_id')->references('id')->on('merchant_products')->onDelete('cascade');

            // Unique constraint to prevent duplicate reservations
            $table->unique(['session_id', 'merchant_product_id', 'cart_key'], 'unique_reservation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
