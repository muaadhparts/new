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
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->decimal('fixed_commission', 10, 2)->default(0)->after('is_affilate');
            $table->decimal('percentage_commission', 5, 2)->default(0)->after('fixed_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muaadhsettings', function (Blueprint $table) {
            $table->dropColumn(['fixed_commission', 'percentage_commission']);
        });
    }
};
