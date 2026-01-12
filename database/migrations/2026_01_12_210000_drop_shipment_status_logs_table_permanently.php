<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * PERMANENT DELETION - NO ROLLBACK
 *
 * This migration permanently drops the old shipment_status_logs table.
 * The new unified system uses shipment_trackings ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop old table (renamed version)
        Schema::dropIfExists('shipment_status_logs_old');

        // Drop original table if still exists
        Schema::dropIfExists('shipment_status_logs');
    }

    public function down(): void
    {
        // NO ROLLBACK - This is permanent deletion
        // The old system is completely removed
    }
};
