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
        Schema::table('shipment_status_logs', function (Blueprint $table) {
            // Add provider column to track shipping source (tryoto, manual, etc.)
            if (!Schema::hasColumn('shipment_status_logs', 'provider')) {
                $table->string('provider', 50)->nullable()->after('company_name')
                    ->comment('Shipping provider: tryoto, manual, etc.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_status_logs', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};
