<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename 'currency' column to 'monetary_unit_code' in accounting_ledger table
     */
    public function up(): void
    {
        Schema::table('accounting_ledger', function (Blueprint $table) {
            $table->renameColumn('currency', 'monetary_unit_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_ledger', function (Blueprint $table) {
            $table->renameColumn('monetary_unit_code', 'currency');
        });
    }
};
