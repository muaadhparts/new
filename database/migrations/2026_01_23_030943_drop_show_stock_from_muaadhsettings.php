<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove show_stock - stock is always shown, no need to hide it
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->dropColumn('show_stock');
        });
    }

    public function down(): void
    {
        // Removed permanently
    }
};
