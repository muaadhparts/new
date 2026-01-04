<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename galleries table to merchant_photos
     */
    public function up(): void
    {
        Schema::rename('galleries', 'merchant_photos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('merchant_photos', 'galleries');
    }
};
