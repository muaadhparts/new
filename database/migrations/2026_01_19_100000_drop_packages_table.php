<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop packages table - packaging feature removed.
 * The system no longer supports packaging options.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('packages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('operator')->nullable();
            $table->string('name');
            $table->string('subname')->nullable();
            $table->decimal('price', 10, 2)->default(0);
        });
    }
};
