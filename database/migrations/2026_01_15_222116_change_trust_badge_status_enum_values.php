<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration لتغيير قيم enum في جدول trust_badges
 *
 * التغييرات:
 * - 'Verified' → 'Trusted' (موثق → معتمد)
 * - 'Declined' → 'Rejected' (مرفوض → مرفوض)
 * - 'Pending' يبقى كما هو
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Expand enum to include both old and new values
        DB::statement("ALTER TABLE trust_badges MODIFY COLUMN status ENUM('Pending', 'Verified', 'Declined', 'Trusted', 'Rejected') DEFAULT 'Pending'");

        // Step 2: Update existing data to new values
        DB::table('trust_badges')
            ->where('status', 'Verified')
            ->update(['status' => 'Trusted']);

        DB::table('trust_badges')
            ->where('status', 'Declined')
            ->update(['status' => 'Rejected']);

        // Step 3: Shrink enum to only have new values
        DB::statement("ALTER TABLE trust_badges MODIFY COLUMN status ENUM('Pending', 'Trusted', 'Rejected') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Expand enum to include both old and new values
        DB::statement("ALTER TABLE trust_badges MODIFY COLUMN status ENUM('Pending', 'Verified', 'Declined', 'Trusted', 'Rejected') DEFAULT 'Pending'");

        // Step 2: Update data back to old values
        DB::table('trust_badges')
            ->where('status', 'Trusted')
            ->update(['status' => 'Verified']);

        DB::table('trust_badges')
            ->where('status', 'Rejected')
            ->update(['status' => 'Declined']);

        // Step 3: Shrink enum to original values
        DB::statement("ALTER TABLE trust_badges MODIFY COLUMN status ENUM('Pending', 'Verified', 'Declined') DEFAULT 'Pending'");
    }
};
