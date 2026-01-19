<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename brand_qualities to quality_brands (Laravel convention)
     */
    public function up(): void
    {
        // Step 1: Rename the unique index
        Schema::table('brand_qualities', function (Blueprint $table) {
            $table->dropUnique('uq_brand_qualities_code');
        });

        // Step 2: Rename the table
        Schema::rename('brand_qualities', 'quality_brands');

        // Step 3: Add new unique index with correct name
        Schema::table('quality_brands', function (Blueprint $table) {
            $table->unique('code', 'uq_quality_brands_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop the new index
        Schema::table('quality_brands', function (Blueprint $table) {
            $table->dropUnique('uq_quality_brands_code');
        });

        // Step 2: Rename back
        Schema::rename('quality_brands', 'brand_qualities');

        // Step 3: Restore old index
        Schema::table('brand_qualities', function (Blueprint $table) {
            $table->unique('code', 'uq_brand_qualities_code');
        });
    }
};
