<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Allow user_id = 0 for platform-level credentials
 *
 * user_id = 0 means Platform/Operator owns this credential
 * user_id > 0 means specific Merchant owns this credential
 */
return new class extends Migration
{
    public function up(): void
    {
        // Check if foreign key exists before trying to drop it
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'merchant_credentials'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = 'merchant_credentials_user_id_foreign'
        ");

        if (!empty($foreignKeys)) {
            Schema::table('merchant_credentials', function (Blueprint $table) {
                $table->dropForeign('merchant_credentials_user_id_foreign');
            });
        }

        // FK already dropped, nothing more to do
        // Index on user_id already exists
    }

    public function down(): void
    {
        // First delete any rows with user_id = 0 (platform credentials)
        DB::table('merchant_credentials')->where('user_id', 0)->delete();

        Schema::table('merchant_credentials', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
