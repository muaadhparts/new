<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove item_page - unused setting (was for page size dropdown options)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->dropColumn('item_page');
        });
    }

    public function down(): void
    {
        // Removed permanently
    }
};
