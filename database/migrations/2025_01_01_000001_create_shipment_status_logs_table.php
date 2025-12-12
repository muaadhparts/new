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
        if (!Schema::hasTable('shipment_status_logs')) {
            Schema::create('shipment_status_logs', function (Blueprint $table) {
                $table->id();
                // Use unsignedInteger to match orders table id type
                $table->unsignedInteger('order_id')->index();
                $table->unsignedInteger('vendor_id')->nullable()->index();
                $table->string('tracking_number', 100)->index();
                $table->string('shipment_id', 100)->nullable();
                $table->string('company_name', 100)->nullable();
                $table->string('status', 50)->default('created')->index();
                $table->string('status_ar', 100)->nullable();
                $table->text('message')->nullable();
                $table->text('message_ar')->nullable();
                $table->string('location', 255)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamp('status_date')->nullable();
                $table->json('raw_data')->nullable();
                $table->timestamps();

                // Note: Not adding foreign key constraint due to potential column type mismatch
                // The order_id index is sufficient for query performance

                // Composite index for faster queries
                $table->index(['tracking_number', 'status_date']);
                $table->index(['vendor_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_status_logs');
    }
};
