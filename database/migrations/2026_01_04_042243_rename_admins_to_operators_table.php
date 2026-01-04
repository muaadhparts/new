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
        Schema::rename('admins', 'operators');
        Schema::rename('admin_roles', 'operator_roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('operators', 'admins');
        Schema::rename('operator_roles', 'admin_roles');
    }
};
