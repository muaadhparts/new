<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create catalog_reviews table with same structure as ratings
        if (Schema::hasTable('ratings') && !Schema::hasTable('catalog_reviews')) {
            DB::statement('CREATE TABLE catalog_reviews LIKE ratings');

            // Copy all data from ratings to catalog_reviews
            DB::statement('INSERT INTO catalog_reviews SELECT * FROM ratings');

            // Rename old table
            Schema::rename('ratings', 'ratings_old');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ratings_old') && Schema::hasTable('catalog_reviews')) {
            Schema::dropIfExists('catalog_reviews');
            Schema::rename('ratings_old', 'ratings');
        }
    }
};
