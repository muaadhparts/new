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
        Schema::table('couriers', function (Blueprint $table) {
            $table->decimal('total_collected', 12, 2)->default(0)->after('balance');
            $table->decimal('total_delivered', 12, 2)->default(0)->after('total_collected');
            $table->decimal('total_fees_earned', 12, 2)->default(0)->after('total_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['total_collected', 'total_delivered', 'total_fees_earned']);
        });
    }
};
