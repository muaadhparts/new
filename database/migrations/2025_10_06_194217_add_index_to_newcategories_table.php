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
        Schema::table('newcategories', function (Blueprint $table) {
            // إضافة composite index على (level, full_code) لتحسين أداء استعلامات section callouts
            $table->index(['level', 'full_code'], 'idx_level_fullcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newcategories', function (Blueprint $table) {
            // حذف الـ index
            $table->dropIndex('idx_level_fullcode');
        });
    }
};
