<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نظام التتبع الموحد الجديد
 *
 * يستبدل: shipment_status_logs + أي JSON تتبع في purchases
 * لا يؤثر على: delivery_couriers (تتبع المندوب المحلي)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->id();

            // العلاقات الأساسية (متوافقة مع أنواع الأعمدة الأصلية)
            $table->unsignedInteger('purchase_id');
            $table->unsignedInteger('merchant_id');
            $table->unsignedInteger('shipping_id')->nullable();

            // نوع التكامل (من جدول shippings)
            $table->enum('integration_type', ['api', 'manual'])->default('manual');

            // مزود الخدمة
            $table->string('provider', 50)->nullable(); // tryoto, saudi, aramex, etc.

            // بيانات التتبع الخارجي
            $table->string('tracking_number', 100)->nullable();
            $table->string('external_shipment_id', 100)->nullable(); // ID من شركة الشحن
            $table->string('company_name', 100)->nullable(); // اسم شركة الشحن الفعلية

            // الحالة
            $table->string('status', 50); // created, picked_up, in_transit, out_for_delivery, delivered, failed, returned, cancelled
            $table->string('status_ar', 100)->nullable();
            $table->string('status_en', 100)->nullable();
            $table->text('message')->nullable(); // رسالة تفصيلية
            $table->text('message_ar')->nullable();

            // الموقع
            $table->string('location', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // التوقيت
            $table->timestamp('occurred_at')->nullable(); // وقت حدوث الحالة فعلياً

            // مصدر التحديث
            $table->enum('source', ['api', 'merchant', 'system', 'operator'])->default('system');

            // البيانات الخام من API
            $table->json('raw_payload')->nullable();

            // بيانات إضافية
            $table->string('awb_url', 500)->nullable(); // رابط بوليصة الشحن
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->decimal('cod_amount', 10, 2)->nullable();

            $table->timestamps();

            // الفهارس
            $table->index('purchase_id');
            $table->index('merchant_id');
            $table->index('shipping_id');
            $table->index('tracking_number');
            $table->index('external_shipment_id');
            $table->index('status');
            $table->index('integration_type');
            $table->index('provider');
            $table->index('occurred_at');
            $table->index(['purchase_id', 'merchant_id']);
            $table->index(['tracking_number', 'status']);

            // ملاحظة: لا نستخدم foreign keys لتجنب مشاكل التوافق مع أنواع الأعمدة
            // الـ indexes كافية للأداء، والتطبيق يتحكم بالعلاقات
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_trackings');
    }
};
