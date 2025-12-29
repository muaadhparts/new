<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename social_providers to oauth_accounts
     */
    public function up(): void
    {
        // Create new oauth_accounts table
        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('provider_id', 191);
            $table->string('provider', 191);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['provider', 'provider_id']);
        });

        // Migrate data from social_providers if it exists
        if (Schema::hasTable('social_providers')) {
            DB::statement('INSERT INTO oauth_accounts (id, user_id, provider_id, provider, created_at, updated_at)
                SELECT id, user_id, provider_id, provider, created_at, updated_at FROM social_providers');

            // Rename old table (never delete)
            Schema::rename('social_providers', 'social_providers_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old table if exists
        if (Schema::hasTable('social_providers_old')) {
            Schema::rename('social_providers_old', 'social_providers');
        }

        Schema::dropIfExists('oauth_accounts');
    }
};
