<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة حقول SEO المتقدمة
     */
    public function up(): void
    {
        Schema::table('seotools', function (Blueprint $table) {
            $table->string('search_console_verification')->nullable()->after('gtm_id');
            $table->string('bing_verification')->nullable()->after('search_console_verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seotools', function (Blueprint $table) {
            $table->dropColumn(['search_console_verification', 'bing_verification']);
        });
    }
};
