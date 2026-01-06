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
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->decimal('commission_amount', 10, 2)->default(0)->after('price');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('commission_amount');
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('packing_cost', 10, 2)->default(0)->after('shipping_cost');
            $table->decimal('courier_fee', 10, 2)->default(0)->after('packing_cost');
            $table->decimal('net_amount', 10, 2)->default(0)->after('courier_fee');

            $table->enum('payment_type', ['merchant', 'platform'])->default('platform')->after('net_amount');
            $table->enum('shipping_type', ['merchant', 'platform', 'courier', 'pickup'])->nullable()->after('payment_type');
            $table->enum('money_received_by', ['merchant', 'platform', 'courier'])->default('platform')->after('shipping_type');

            $table->unsignedInteger('payment_gateway_id')->nullable()->after('money_received_by');
            $table->unsignedInteger('shipping_id')->nullable()->after('payment_gateway_id');
            $table->unsignedInteger('courier_id')->nullable()->after('shipping_id');
            $table->unsignedInteger('pickup_point_id')->nullable()->after('courier_id');

            // جدول merchant_purchases يحتوي على created_at بالفعل
            $table->timestamp('updated_at')->nullable()->after('pickup_point_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'commission_amount',
                'tax_amount',
                'shipping_cost',
                'packing_cost',
                'courier_fee',
                'net_amount',
                'payment_type',
                'shipping_type',
                'money_received_by',
                'payment_gateway_id',
                'shipping_id',
                'courier_id',
                'pickup_point_id',
                'updated_at'
            ]);
        });
    }
};
