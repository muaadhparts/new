<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove merchant_page_count - unified to use page_count instead
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->dropColumn('merchant_page_count');
        });
    }

    public function down(): void
    {
        // Removed permanently - use page_count
    }
};
