<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename wishlist_count and wishlist_page to favorite_count and favorite_page
     */
    public function up(): void
    {
        Schema::table('generalsettings', function (Blueprint $table) {
            $table->renameColumn('wishlist_count', 'favorite_count');
            $table->renameColumn('wishlist_page', 'favorite_page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generalsettings', function (Blueprint $table) {
            $table->renameColumn('favorite_count', 'wishlist_count');
            $table->renameColumn('favorite_page', 'wishlist_page');
        });
    }
};
