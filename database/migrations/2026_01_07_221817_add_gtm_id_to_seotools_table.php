<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة حقل Google Tag Manager ID
     */
    public function up(): void
    {
        Schema::table('seotools', function (Blueprint $table) {
            $table->string('gtm_id')->nullable()->after('google_analytics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seotools', function (Blueprint $table) {
            $table->dropColumn('gtm_id');
        });
    }
};
