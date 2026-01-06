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
        Schema::create('courier_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('courier_id');
            $table->bigInteger('delivery_courier_id')->nullable();
            $table->bigInteger('settlement_id')->nullable();
            $table->enum('type', ['fee_earned', 'cod_collected', 'settlement_paid', 'settlement_received', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_transactions');
    }
};
