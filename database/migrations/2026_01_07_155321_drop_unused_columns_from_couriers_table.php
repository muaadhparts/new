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
            $table->dropColumn(['location', 'zip', 'country', 'fax']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->string('location')->nullable()->after('address');
            $table->string('zip')->nullable()->after('fax');
            $table->string('country')->nullable()->after('email_token');
            $table->string('fax')->nullable()->after('email');
        });
    }
};
