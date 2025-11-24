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
        Schema::dropIfExists('admin_languages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally recreate the table structure if needed for rollback
        Schema::create('admin_languages', function (Blueprint $table) {
            $table->id();
            $table->string('language');
            $table->string('name');
            $table->string('file');
            $table->boolean('is_default')->default(0);
            $table->boolean('rtl')->default(0);
        });
    }
};
