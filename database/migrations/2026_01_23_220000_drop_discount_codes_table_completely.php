<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حذف نظام أكواد الخصم بالكامل
 *
 * تم إزالة هذه الميزة معمارياً - لا حاجة لها
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('discount_codes');
    }

    public function down(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->comment('Merchant ID who created the discount code');
            $table->string('code', 191)->comment('Unique discount code');
            $table->tinyInteger('type')->comment('0 = Percentage, 1 = Fixed Amount');
            $table->double('price')->comment('Discount value (percentage or fixed amount)');
            $table->string('times', 191)->nullable()->comment('Usage limit (null = unlimited)');
            $table->unsignedInteger('used')->default(0)->comment('Number of times used');
            $table->tinyInteger('status')->default(1)->comment('1 = Active, 0 = Inactive');
            $table->date('start_date');
            $table->date('end_date');

            $table->index('user_id');
            $table->index('code');
            $table->index('status');
        });
    }
};
