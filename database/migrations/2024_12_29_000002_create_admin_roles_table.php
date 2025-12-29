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
        // Step 1: Create admin_roles table
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->nullable();
            $table->text('section')->nullable();
        });

        // Step 2: Migrate data
        DB::statement('
            INSERT INTO admin_roles (id, name, section)
            SELECT id, name, section
            FROM roles
        ');

        // Step 3: Rename old table with _old suffix
        Schema::rename('roles', 'roles_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('roles_old', 'roles');
        Schema::dropIfExists('admin_roles');
    }
};
