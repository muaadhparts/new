<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * حذف جدول metrics - تم استبدال التتبع الداخلي بـ Google Analytics
     */
    public function up(): void
    {
        Schema::dropIfExists('metrics');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('referral')->nullable();
            $table->integer('total_count')->default(0);
            $table->integer('todays_count')->default(0);
            $table->date('today')->nullable();
            $table->string('type')->nullable();
        });
    }
};
