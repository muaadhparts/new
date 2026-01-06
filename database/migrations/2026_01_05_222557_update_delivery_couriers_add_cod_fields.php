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
        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('status');
            $table->decimal('cod_amount', 10, 2)->default(0)->after('delivery_fee');
            $table->decimal('order_amount', 10, 2)->default(0)->after('cod_amount');
            $table->enum('payment_method', ['online', 'cod'])->default('cod')->after('order_amount');
            $table->enum('fee_status', ['pending', 'paid', 'collected'])->default('pending')->after('payment_method');
            $table->enum('settlement_status', ['pending', 'settled'])->default('pending')->after('fee_status');
            $table->timestamp('delivered_at')->nullable()->after('settlement_status');
            $table->timestamp('settled_at')->nullable()->after('delivered_at');
            $table->text('notes')->nullable()->after('settled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_couriers', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_fee',
                'cod_amount',
                'order_amount',
                'payment_method',
                'fee_status',
                'settlement_status',
                'delivered_at',
                'settled_at',
                'notes'
            ]);
        });
    }
};
