<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table stores discount codes created by vendors.
     * Previously named 'coupons' - renamed to 'discount_codes' for clarity.
     */
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->comment('Vendor ID who created the discount code');
            $table->string('code', 191)->comment('Unique discount code');
            $table->tinyInteger('type')->comment('0 = Percentage, 1 = Fixed Amount');
            $table->double('price')->comment('Discount value (percentage or fixed amount)');
            $table->string('times', 191)->nullable()->comment('Usage limit (null = unlimited)');
            $table->unsignedInteger('used')->default(0)->comment('Number of times used');
            $table->tinyInteger('status')->default(1)->comment('1 = Active, 0 = Inactive');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('apply_to', 255)->nullable()->comment('category, sub_category, child_category');
            $table->integer('category')->nullable();
            $table->integer('sub_category')->nullable();
            $table->integer('child_category')->nullable();

            $table->index('user_id');
            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
