<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * هذه الهجرة تضيف أعمدة لحفظ بيانات شركة الشحن المختارة من العميل
     * عند checkout، بحيث يستطيع البائع معرفة اختيار العميل
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // بيانات شركة الشحن المختارة من العميل (JSON لدعم التجار المتعددين)
            // مثال: {"vendor_id": {"provider": "tryoto", "company": "FedEx", "option_id": "123", "price": 5000}}
            $table->json('customer_shipping_choice')->nullable()->after('shipping_title')
                  ->comment('Customer selected shipping company data per vendor');

            // حالة الشحن لكل تاجر (JSON)
            // مثال: {"vendor_id": {"status": "pending", "tracking": "TRK123", "shipment_id": "SHP456"}}
            $table->json('shipping_status')->nullable()->after('customer_shipping_choice')
                  ->comment('Shipping status per vendor with tracking info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_shipping_choice', 'shipping_status']);
        });
    }
};
